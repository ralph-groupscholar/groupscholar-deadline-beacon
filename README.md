# Group Scholar Deadline Beacon

Deadline Beacon is a Group Scholar CLI that tracks scholarship deadlines, keeps sponsor context, and produces a weekly outreach digest. It is designed for ops teams who need a lightweight command-line workflow with real data persistence in Postgres.

## Features

- Initialize and seed the production database schema.
- Add scholarship deadlines with sponsor, cycle, and award metadata.
- List and filter upcoming deadlines.
- Generate a weekly digest grouped by week.

## Tech Stack

- TypeScript (Node.js)
- Postgres (pg)
- Commander for CLI parsing
- Vitest for unit tests

## Getting Started

1. Install dependencies:

```bash
npm install
```

2. Configure database access by exporting either `GS_DB_URL` or the individual `GS_DB_*` environment variables (see `.env.example`).

3. Initialize and seed the database:

```bash
npm run db:init
npm run db:seed
```

4. Run the CLI:

```bash
npm run dev -- list
npm run dev -- upcoming --days 45
npm run dev -- digest --days 30
npm run dev -- add --name "Community Innovators" --deadline 2026-04-20 --cycle "Spring 2026" --amount 4000
```

## Testing

```bash
npm test
```
