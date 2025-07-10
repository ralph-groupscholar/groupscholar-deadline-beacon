import type { Scholarship } from './types.js';

const formatter = new Intl.DateTimeFormat('en-US', {
  month: 'short',
  day: '2-digit',
  year: 'numeric'
});

export function formatDate(value: string): string {
  return formatter.format(new Date(value));
}

export function formatCurrency(amount: number | null): string {
  if (!amount) return '—';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  }).format(amount);
}

export function formatScholarshipTable(rows: Scholarship[]): string {
  if (!rows.length) return 'No scholarships found.';
  const lines = rows.map((row) => {
    const sponsor = row.sponsor ?? 'Independent';
    return `${formatDate(row.deadlineDate)} | ${row.name} | ${sponsor} | ${formatCurrency(
      row.awardAmountUsd
    )} | ${row.region ?? '—'}`;
  });
  return ['Deadline | Scholarship | Sponsor | Amount | Region', ...lines].join('\n');
}

export function startOfWeek(date: Date): Date {
  const result = new Date(date);
  const day = result.getDay();
  const diff = (day === 0 ? -6 : 1) - day; // Monday start
  result.setDate(result.getDate() + diff);
  result.setHours(0, 0, 0, 0);
  return result;
}

export function formatDigest(rows: Scholarship[], days: number): string {
  if (!rows.length) {
    return `No deadlines in the next ${days} days.`;
  }

  const groups = new Map<string, Scholarship[]>();
  for (const row of rows) {
    const weekKey = startOfWeek(new Date(row.deadlineDate)).toISOString().slice(0, 10);
    if (!groups.has(weekKey)) {
      groups.set(weekKey, []);
    }
    groups.get(weekKey)!.push(row);
  }

  const orderedWeeks = Array.from(groups.keys()).sort();
  const sections = orderedWeeks.map((weekKey) => {
    const label = formatter.format(new Date(weekKey));
    const items = groups.get(weekKey) ?? [];
    const lines = items.map((row) => {
      const sponsor = row.sponsor ?? 'Independent';
      return `- ${formatDate(row.deadlineDate)}: ${row.name} (${sponsor})`; // keep simple
    });
    return [`Week of ${label}`, ...lines].join('\n');
  });

  return [`Deadline Digest (next ${days} days)`, ...sections].join('\n\n');
}
