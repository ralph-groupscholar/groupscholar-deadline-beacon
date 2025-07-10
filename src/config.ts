export type DbConfig = {
  connectionString: string;
};

export const DEFAULT_DB_NAME = 'postgres';

export function loadDbConfig(): DbConfig {
  const url = process.env.GS_DB_URL?.trim();
  if (url) {
    return { connectionString: url };
  }

  const host = process.env.GS_DB_HOST?.trim();
  const port = process.env.GS_DB_PORT?.trim();
  const user = process.env.GS_DB_USER?.trim();
  const password = process.env.GS_DB_PASSWORD?.trim();
  const name = process.env.GS_DB_NAME?.trim() || DEFAULT_DB_NAME;

  const missing = [
    ['GS_DB_HOST', host],
    ['GS_DB_PORT', port],
    ['GS_DB_USER', user],
    ['GS_DB_PASSWORD', password]
  ].filter(([, value]) => !value);

  if (missing.length) {
    const names = missing.map(([key]) => key).join(', ');
    throw new Error(
      `Missing database environment variables: ${names}. Set GS_DB_URL or the full connection details.`
    );
  }

  return {
    connectionString: `postgresql://${encodeURIComponent(user!)}:${encodeURIComponent(
      password!
    )}@${host!}:${port!}/${name}`
  };
}
