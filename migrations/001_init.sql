CREATE TABLE IF NOT EXISTS deadline_beacon_deadlines (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    organization TEXT,
    application_url TEXT,
    deadline_date DATE NOT NULL,
    timezone TEXT NOT NULL DEFAULT 'UTC',
    status TEXT NOT NULL DEFAULT 'open',
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS deadline_beacon_notifications (
    id SERIAL PRIMARY KEY,
    deadline_id INTEGER NOT NULL REFERENCES deadline_beacon_deadlines(id) ON DELETE CASCADE,
    channel TEXT NOT NULL,
    sent_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message TEXT
);

CREATE INDEX IF NOT EXISTS deadline_beacon_deadlines_status_idx
    ON deadline_beacon_deadlines(status);

CREATE INDEX IF NOT EXISTS deadline_beacon_deadlines_date_idx
    ON deadline_beacon_deadlines(deadline_date);
