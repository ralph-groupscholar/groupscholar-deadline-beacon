import pg from 'pg';
import { loadDbConfig } from './config.js';
import type { Scholarship, ScholarshipInput } from './types.js';

const { Pool } = pg;
const SCHEMA = 'groupscholar_deadline_beacon';

let pool: pg.Pool | null = null;

function getPool(): pg.Pool {
  if (!pool) {
    const config = loadDbConfig();
    pool = new Pool({ connectionString: config.connectionString, max: 5 });
  }
  return pool;
}

export async function closePool(): Promise<void> {
  if (pool) {
    await pool.end();
    pool = null;
  }
}

export async function initDb(): Promise<void> {
  const client = await getPool().connect();
  try {
    await client.query('BEGIN');
    await client.query(`CREATE SCHEMA IF NOT EXISTS ${SCHEMA}`);
    await client.query(`
      CREATE TABLE IF NOT EXISTS ${SCHEMA}.scholarships (
        scholarship_id SERIAL PRIMARY KEY,
        name TEXT NOT NULL,
        sponsor TEXT,
        deadline_date DATE NOT NULL,
        cycle TEXT NOT NULL,
        award_amount_usd INTEGER,
        region TEXT,
        url TEXT,
        notes TEXT,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);
    await client.query('COMMIT');
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}

export async function seedDb(): Promise<number> {
  const seedRows: ScholarshipInput[] = [
    {
      name: 'STEM Catalyst Scholars',
      sponsor: 'Catalyst Foundation',
      deadlineDate: '2026-03-15',
      cycle: 'Spring 2026',
      awardAmountUsd: 5000,
      region: 'National',
      url: 'https://example.org/stem-catalyst',
      notes: 'Requires community impact statement.'
    },
    {
      name: 'Urban Leaders Tuition Grant',
      sponsor: 'Metro Civic Trust',
      deadlineDate: '2026-04-05',
      cycle: 'Spring 2026',
      awardAmountUsd: 3000,
      region: 'Midwest',
      url: 'https://example.org/urban-leaders',
      notes: 'Priority for first-generation students.'
    },
    {
      name: 'Future Educators Fellowship',
      sponsor: 'TeachForward Alliance',
      deadlineDate: '2026-02-28',
      cycle: 'Spring 2026',
      awardAmountUsd: 4500,
      region: 'South',
      url: 'https://example.org/future-educators',
      notes: 'Requires recommendation letter from faculty.'
    },
    {
      name: 'Green Horizons Grant',
      sponsor: 'EcoPath Initiative',
      deadlineDate: '2026-05-01',
      cycle: 'Summer 2026',
      awardAmountUsd: 6000,
      region: 'West Coast',
      url: 'https://example.org/green-horizons',
      notes: 'Open to environmental science majors.'
    },
    {
      name: 'Digital Equity Scholarship',
      sponsor: 'Access Now Fund',
      deadlineDate: '2026-03-30',
      cycle: 'Spring 2026',
      awardAmountUsd: 3500,
      region: 'Northeast',
      url: 'https://example.org/digital-equity',
      notes: 'Submit portfolio of community tech work.'
    }
  ];

  const client = await getPool().connect();
  try {
    await client.query('BEGIN');
    const insertText = `
      INSERT INTO ${SCHEMA}.scholarships
      (name, sponsor, deadline_date, cycle, award_amount_usd, region, url, notes)
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
    `;
    for (const row of seedRows) {
      await client.query(insertText, [
        row.name,
        row.sponsor ?? null,
        row.deadlineDate,
        row.cycle,
        row.awardAmountUsd ?? null,
        row.region ?? null,
        row.url ?? null,
        row.notes ?? null
      ]);
    }
    await client.query('COMMIT');
    return seedRows.length;
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}

export async function addScholarship(input: ScholarshipInput): Promise<Scholarship> {
  const client = await getPool().connect();
  try {
    const result = await client.query(
      `
        INSERT INTO ${SCHEMA}.scholarships
        (name, sponsor, deadline_date, cycle, award_amount_usd, region, url, notes)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
        RETURNING
          scholarship_id AS "scholarshipId",
          name,
          sponsor,
          deadline_date AS "deadlineDate",
          cycle,
          award_amount_usd AS "awardAmountUsd",
          region,
          url,
          notes,
          created_at AS "createdAt"
      `,
      [
        input.name,
        input.sponsor ?? null,
        input.deadlineDate,
        input.cycle,
        input.awardAmountUsd ?? null,
        input.region ?? null,
        input.url ?? null,
        input.notes ?? null
      ]
    );
    return result.rows[0];
  } finally {
    client.release();
  }
}

export async function listScholarships(): Promise<Scholarship[]> {
  const result = await getPool().query(
    `
      SELECT
        scholarship_id AS "scholarshipId",
        name,
        sponsor,
        deadline_date AS "deadlineDate",
        cycle,
        award_amount_usd AS "awardAmountUsd",
        region,
        url,
        notes,
        created_at AS "createdAt"
      FROM ${SCHEMA}.scholarships
      ORDER BY deadline_date ASC
    `
  );
  return result.rows;
}

export async function upcomingScholarships(days: number): Promise<Scholarship[]> {
  const result = await getPool().query(
    `
      SELECT
        scholarship_id AS "scholarshipId",
        name,
        sponsor,
        deadline_date AS "deadlineDate",
        cycle,
        award_amount_usd AS "awardAmountUsd",
        region,
        url,
        notes,
        created_at AS "createdAt"
      FROM ${SCHEMA}.scholarships
      WHERE deadline_date BETWEEN CURRENT_DATE AND CURRENT_DATE + $1::INT
      ORDER BY deadline_date ASC
    `,
    [days]
  );
  return result.rows;
}
