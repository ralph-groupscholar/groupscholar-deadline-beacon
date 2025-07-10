import { describe, expect, it } from 'vitest';
import { formatDigest, formatScholarshipTable } from '../src/format.js';
import type { Scholarship } from '../src/types.js';

const rows: Scholarship[] = [
  {
    scholarshipId: 1,
    name: 'Future Leaders',
    sponsor: 'Civic Trust',
    deadlineDate: '2026-02-12',
    cycle: 'Spring 2026',
    awardAmountUsd: 3000,
    region: 'Midwest',
    url: null,
    notes: null,
    createdAt: '2026-02-01T10:00:00Z'
  },
  {
    scholarshipId: 2,
    name: 'STEM Catalyst Scholars',
    sponsor: null,
    deadlineDate: '2026-02-17',
    cycle: 'Spring 2026',
    awardAmountUsd: 5000,
    region: 'National',
    url: null,
    notes: null,
    createdAt: '2026-02-01T10:00:00Z'
  }
];

describe('formatScholarshipTable', () => {
  it('prints a header and rows', () => {
    const output = formatScholarshipTable(rows);
    expect(output).toContain('Deadline | Scholarship | Sponsor | Amount | Region');
    expect(output).toContain('Future Leaders');
    expect(output).toContain('STEM Catalyst Scholars');
  });
});

describe('formatDigest', () => {
  it('groups rows into a digest', () => {
    const output = formatDigest(rows, 30);
    expect(output).toContain('Deadline Digest (next 30 days)');
    expect(output).toContain('Future Leaders');
    expect(output).toContain('STEM Catalyst Scholars');
  });
});
