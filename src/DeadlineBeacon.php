<?php
namespace DeadlineBeacon;

use PDO;
use RuntimeException;

class App
{
    private Db $db;

    public function __construct(?Db $db = null)
    {
        $this->db = $db ?? Db::fromEnv();
    }

    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);
        $options = $this->parseOptions($args);

        switch ($command) {
            case 'list':
                $this->listDeadlines($options);
                break;
            case 'overdue':
                $this->overdueDeadlines($options);
                break;
            case 'nudge':
                $this->nudgeDeadlines($options);
                break;
            case 'add':
                $this->addDeadline($options);
                break;
            case 'close':
                $this->closeDeadline($options);
                break;
            case 'log-notification':
                $this->logNotification($options);
                break;
            case 'report':
                $this->report($options);
                break;
            case 'help':
            default:
                $this->printHelp();
                break;
        }
    }

    private function parseOptions(array $args): array
    {
        $options = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $options[$key] = $value;
            }
        }
        return $options;
    }

    private function listDeadlines(array $options): void
    {
        $within = (int)($options['within'] ?? 45);
        $status = $options['status'] ?? 'open';
        $cutoff = (new \DateTimeImmutable('today'))
            ->modify('+' . $within . ' days')
            ->format('Y-m-d');

        $rows = $this->db->fetchAll(
            'SELECT id, title, organization, deadline_date, timezone, status, application_url
             FROM deadline_beacon_deadlines
             WHERE status = :status
             AND deadline_date <= :cutoff
             ORDER BY deadline_date ASC',
            [
                ':status' => $status,
                ':cutoff' => $cutoff,
            ]
        );

        if (!$rows) {
            echo "No deadlines found.\n";
            return;
        }

        foreach ($rows as $row) {
            $url = $row['application_url'] ? " ({$row['application_url']})" : '';
            echo sprintf(
                "#%d %s — %s (%s)%s\n",
                $row['id'],
                $row['title'],
                $row['deadline_date'],
                $row['timezone'],
                $url
            );
        }
    }

    private function addDeadline(array $options): void
    {
        $required = ['title', 'date'];
        foreach ($required as $key) {
            if (empty($options[$key])) {
                throw new RuntimeException("Missing required option --{$key}");
            }
        }

        $this->db->execute(
            'INSERT INTO deadline_beacon_deadlines
                (title, organization, application_url, deadline_date, timezone, status, notes, created_at, updated_at)
             VALUES
                (:title, :organization, :application_url, :deadline_date, :timezone, :status, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
            [
                ':title' => $options['title'],
                ':organization' => $options['org'] ?? null,
                ':application_url' => $options['url'] ?? null,
                ':deadline_date' => $options['date'],
                ':timezone' => $options['tz'] ?? 'UTC',
                ':status' => $options['status'] ?? 'open',
                ':notes' => $options['notes'] ?? null,
            ]
        );

        echo "Deadline added.\n";
    }

    private function nudgeDeadlines(array $options): void
    {
        $within = (int)($options['within'] ?? 45);
        $staleDays = (int)($options['stale-days'] ?? 14);
        $status = $options['status'] ?? 'open';

        $cutoff = (new \DateTimeImmutable('today'))
            ->modify('+' . $within . ' days')
            ->format('Y-m-d');
        $staleCutoff = (new \DateTimeImmutable('now'))
            ->modify('-' . $staleDays . ' days')
            ->format('Y-m-d H:i:s');

        $rows = $this->db->fetchAll(
            'SELECT d.id, d.title, d.organization, d.deadline_date, d.timezone, d.application_url,
                    MAX(n.sent_at) AS last_sent
             FROM deadline_beacon_deadlines d
             LEFT JOIN deadline_beacon_notifications n
                ON n.deadline_id = d.id
             WHERE d.status = :status
               AND d.deadline_date <= :cutoff
             GROUP BY d.id, d.title, d.organization, d.deadline_date, d.timezone, d.application_url
             HAVING MAX(n.sent_at) IS NULL OR MAX(n.sent_at) < :stale_cutoff
             ORDER BY d.deadline_date ASC',
            [
                ':status' => $status,
                ':cutoff' => $cutoff,
                ':stale_cutoff' => $staleCutoff,
            ]
        );

        if (!$rows) {
            echo "No nudges needed.\n";
            return;
        }

        foreach ($rows as $row) {
            $url = $row['application_url'] ? " ({$row['application_url']})" : '';
            $lastSent = $row['last_sent'] ? $row['last_sent'] : 'never';
            echo sprintf(
                "#%d %s — %s (%s)%s | last notification: %s\n",
                $row['id'],
                $row['title'],
                $row['deadline_date'],
                $row['timezone'],
                $url,
                $lastSent
            );
        }
    }

    private function overdueDeadlines(array $options): void
    {
        $days = (int)($options['days'] ?? 30);
        $status = $options['status'] ?? 'open';
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $since = (new \DateTimeImmutable('today'))
            ->modify('-' . $days . ' days')
            ->format('Y-m-d');

        $rows = $this->db->fetchAll(
            'SELECT id, title, organization, deadline_date, timezone, status, application_url
             FROM deadline_beacon_deadlines
             WHERE status = :status
               AND deadline_date < :today
               AND deadline_date >= :since
             ORDER BY deadline_date ASC',
            [
                ':status' => $status,
                ':today' => $today,
                ':since' => $since,
            ]
        );

        if (!$rows) {
            echo "No overdue deadlines found.\n";
            return;
        }

        foreach ($rows as $row) {
            $url = $row['application_url'] ? " ({$row['application_url']})" : '';
            echo sprintf(
                "#%d %s — %s (%s)%s\n",
                $row['id'],
                $row['title'],
                $row['deadline_date'],
                $row['timezone'],
                $url
            );
        }
    }

    private function closeDeadline(array $options): void
    {
        if (empty($options['id'])) {
            throw new RuntimeException('Missing required option --id');
        }

        $this->db->execute(
            'UPDATE deadline_beacon_deadlines
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                ':status' => $options['status'] ?? 'closed',
                ':id' => (int)$options['id'],
            ]
        );

        echo "Deadline updated.\n";
    }

    private function logNotification(array $options): void
    {
        $required = ['id', 'channel'];
        foreach ($required as $key) {
            if (empty($options[$key])) {
                throw new RuntimeException("Missing required option --{$key}");
            }
        }

        $this->db->execute(
            'INSERT INTO deadline_beacon_notifications
                (deadline_id, channel, sent_at, message)
             VALUES
                (:deadline_id, :channel, CURRENT_TIMESTAMP, :message)',
            [
                ':deadline_id' => (int)$options['id'],
                ':channel' => $options['channel'],
                ':message' => $options['message'] ?? null,
            ]
        );

        echo "Notification logged.\n";
    }

    private function report(array $options): void
    {
        $window = (int)($options['within'] ?? 90);
        $cutoff = (new \DateTimeImmutable('today'))
            ->modify('+' . $window . ' days')
            ->format('Y-m-d');
        $since = (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');

        $total = $this->db->fetchValue(
            'SELECT COUNT(*) FROM deadline_beacon_deadlines WHERE deadline_date <= :cutoff',
            [':cutoff' => $cutoff]
        );

        $open = $this->db->fetchValue(
            'SELECT COUNT(*) FROM deadline_beacon_deadlines WHERE status = :status',
            [':status' => 'open']
        );

        $recentNotifications = $this->db->fetchValue(
            'SELECT COUNT(*) FROM deadline_beacon_notifications WHERE sent_at >= :since',
            [':since' => $since]
        );

        $overdueOpen = $this->db->fetchValue(
            'SELECT COUNT(*) FROM deadline_beacon_deadlines
             WHERE status = :status AND deadline_date < :today',
            [
                ':status' => 'open',
                ':today' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
            ]
        );

        echo "Deadlines in next {$window} days: {$total}\n";
        echo "Open deadlines: {$open}\n";
        echo "Notifications logged (30 days): {$recentNotifications}\n";
        echo "Overdue open deadlines: {$overdueOpen}\n";
    }

    private function printHelp(): void
    {
        echo "Deadline Beacon CLI\n\n";
        echo "Commands:\n";
        echo "  list --within=45 --status=open\n";
        echo "  overdue --days=30 --status=open\n";
        echo "  nudge --within=45 --stale-days=14 --status=open\n";
        echo "  add --title=\"Scholarship\" --date=YYYY-MM-DD [--org=Org] [--url=URL] [--tz=UTC] [--notes=Text]\n";
        echo "  close --id=1 [--status=closed]\n";
        echo "  log-notification --id=1 --channel=slack [--message=Text]\n";
        echo "  report --within=90\n";
    }
}

class Db
{
    private PDO $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function fromEnv(): self
    {
        $dsn = getenv('DEADLINE_BEACON_DSN');
        if (!$dsn) {
            $databaseUrl = getenv('DATABASE_URL');
            if ($databaseUrl) {
                $dsn = self::dsnFromUrl($databaseUrl);
            }
        }

        if (!$dsn) {
            $sqlitePath = getenv('DEADLINE_BEACON_SQLITE_PATH');
            if ($sqlitePath) {
                $dsn = "sqlite:" . $sqlitePath;
            }
        }

        if (!$dsn) {
            throw new RuntimeException('Set DEADLINE_BEACON_DSN or DATABASE_URL');
        }

        $user = getenv('DEADLINE_BEACON_DB_USER') ?: null;
        $pass = getenv('DEADLINE_BEACON_DB_PASS') ?: null;

        return self::fromDsn($dsn, $user, $pass);
    }

    public static function fromDsn(string $dsn, ?string $user = null, ?string $pass = null): self
    {
        return new self(new PDO($dsn, $user, $pass));
    }

    private static function dsnFromUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            throw new RuntimeException('Invalid DATABASE_URL');
        }

        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? 5432;
        $db = ltrim($parts['path'] ?? '', '/');

        return "pgsql:host={$host};port={$port};dbname={$db}";
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchValue(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function execute(string $sql, array $params = []): void
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function execSql(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
