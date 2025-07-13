# GroupScholar Deadline Beacon

Deadline Beacon is a PHP CLI for tracking scholarship deadlines, logging outreach reminders, and generating quick pipeline reports. It is built for Group Scholar operations so deadlines stay visible and notifications are auditable.

## Features
- Track deadlines with status, timezone, and notes
- Update deadline details as requirements change
- Log notification events per deadline
- Surface deadlines that need a fresh outreach nudge
- Highlight overdue open deadlines
- Quick reporting for upcoming windows and recent outreach activity
- Database-backed storage (PostgreSQL or SQLite)

## Tech
- PHP 8+
- PostgreSQL (production) or SQLite (local/testing)
- Plain SQL migrations

## Setup

### Environment
Set one of the following:
- `DEADLINE_BEACON_DSN` (preferred)
- `DATABASE_URL` (Postgres URL)
- `DEADLINE_BEACON_SQLITE_PATH` (local fallback)

Optional for Postgres:
- `DEADLINE_BEACON_DB_USER`
- `DEADLINE_BEACON_DB_PASS`

### Migrations
Run the migration SQL for your database:
- Postgres: `migrations/001_init.sql`
- SQLite: `migrations/001_init_sqlite.sql`

### Seed data
Run the seed SQL for your database:
- Postgres: `scripts/seed.sql`
- SQLite: `scripts/seed_sqlite.sql`

### Production bootstrap
```bash
DEADLINE_BEACON_DSN="pgsql:host=db-acupinir.groupscholar.com;port=23947;dbname=postgres" \
DEADLINE_BEACON_DB_USER="YOUR_USER" \
DEADLINE_BEACON_DB_PASS="YOUR_PASS" \
php scripts/bootstrap_db.php
```

## Usage
```bash
bin/deadline-beacon.php list --within=45 --status=open
bin/deadline-beacon.php overdue --days=30 --status=open
bin/deadline-beacon.php nudge --within=45 --stale-days=14 --status=open
bin/deadline-beacon.php add --title="Scholarship" --date=2026-03-01 --org="Org" --url="https://example.org" --tz="America/New_York"
bin/deadline-beacon.php update --id=1 --status=paused --notes="Waiting on revised guidelines"
bin/deadline-beacon.php close --id=1 --status=closed
bin/deadline-beacon.php log-notification --id=1 --channel=slack --message="Reminder sent"
bin/deadline-beacon.php report --within=90
```

## Tests
```bash
php tests/test_cli.php
```
