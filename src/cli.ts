import { Command } from 'commander';
import {
  addScholarship,
  closePool,
  initDb,
  listScholarships,
  seedDb,
  upcomingScholarships
} from './db.js';
import { formatDigest, formatScholarshipTable } from './format.js';
import type { ScholarshipInput } from './types.js';

const program = new Command();

program
  .name('deadline-beacon')
  .description('Track scholarship deadlines and generate outreach digests for Group Scholar.')
  .version('1.0.0');

program
  .command('db-init')
  .description('Create database schema and tables.')
  .action(async () => {
    await initDb();
    console.log('Database initialized.');
    await closePool();
  });

program
  .command('db-seed')
  .description('Insert starter scholarship deadlines.')
  .action(async () => {
    const count = await seedDb();
    console.log(`Seeded ${count} scholarships.`);
    await closePool();
  });

program
  .command('add')
  .description('Add a scholarship deadline.')
  .requiredOption('--name <name>', 'Scholarship name')
  .requiredOption('--deadline <date>', 'Deadline date (YYYY-MM-DD)')
  .requiredOption('--cycle <cycle>', 'Cycle label')
  .option('--sponsor <sponsor>', 'Sponsor name')
  .option('--amount <amount>', 'Award amount in USD')
  .option('--region <region>', 'Region or coverage')
  .option('--url <url>', 'Scholarship URL')
  .option('--notes <notes>', 'Internal notes')
  .action(async (options) => {
    const input = parseScholarshipInput(options);
    const record = await addScholarship(input);
    console.log('Added scholarship:');
    console.log(formatScholarshipTable([record]));
    await closePool();
  });

program
  .command('list')
  .description('List all scholarships by deadline.')
  .action(async () => {
    const rows = await listScholarships();
    console.log(formatScholarshipTable(rows));
    await closePool();
  });

program
  .command('upcoming')
  .description('List upcoming deadlines within a window of days.')
  .option('--days <days>', 'Number of days to look ahead', '30')
  .action(async (options) => {
    const days = parsePositiveInt(options.days, 'days');
    const rows = await upcomingScholarships(days);
    console.log(formatScholarshipTable(rows));
    await closePool();
  });

program
  .command('digest')
  .description('Generate a digest grouped by week.')
  .option('--days <days>', 'Number of days to look ahead', '30')
  .action(async (options) => {
    const days = parsePositiveInt(options.days, 'days');
    const rows = await upcomingScholarships(days);
    console.log(formatDigest(rows, days));
    await closePool();
  });

program.parseAsync(process.argv).catch((error) => {
  console.error(error instanceof Error ? error.message : error);
  process.exit(1);
});

function parsePositiveInt(value: string, label: string): number {
  const parsed = Number.parseInt(value, 10);
  if (!Number.isFinite(parsed) || parsed <= 0) {
    throw new Error(`${label} must be a positive integer.`);
  }
  return parsed;
}

function parseScholarshipInput(options: Record<string, string>): ScholarshipInput {
  const deadline = new Date(options.deadline);
  if (Number.isNaN(deadline.getTime())) {
    throw new Error('Deadline must be a valid date in YYYY-MM-DD format.');
  }

  const amount = options.amount ? Number.parseInt(options.amount, 10) : undefined;
  if (options.amount && (!Number.isFinite(amount) || amount <= 0)) {
    throw new Error('Amount must be a positive integer.');
  }

  return {
    name: options.name,
    sponsor: options.sponsor,
    deadlineDate: options.deadline,
    cycle: options.cycle,
    awardAmountUsd: amount,
    region: options.region,
    url: options.url,
    notes: options.notes
  };
}
