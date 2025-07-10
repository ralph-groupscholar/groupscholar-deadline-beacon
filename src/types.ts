export type Scholarship = {
  scholarshipId: number;
  name: string;
  sponsor: string | null;
  deadlineDate: string;
  cycle: string;
  awardAmountUsd: number | null;
  region: string | null;
  url: string | null;
  notes: string | null;
  createdAt: string;
};

export type ScholarshipInput = {
  name: string;
  sponsor?: string;
  deadlineDate: string;
  cycle: string;
  awardAmountUsd?: number;
  region?: string;
  url?: string;
  notes?: string;
};
