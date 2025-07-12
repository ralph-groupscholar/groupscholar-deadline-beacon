CREATE TABLE IF NOT EXISTS deadline_beacon_deadlines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    organization TEXT,
    application_url TEXT,
    deadline_date TEXT NOT NULL,
    timezone TEXT NOT NULL DEFAULT 'UTC',
    status TEXT NOT NULL DEFAULT 'open',
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS deadline_beacon_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    deadline_id INTEGER NOT NULL REFERENCES deadline_beacon_deadlines(id) ON DELETE CASCADE,
    channel TEXT NOT NULL,
    sent_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message TEXT
);

CREATE INDEX IF NOT EXISTS deadline_beacon_deadlines_status_idx
    ON deadline_beacon_deadlines(status);

CREATE INDEX IF NOT EXISTS deadline_beacon_deadlines_date_idx
    ON deadline_beacon_deadlines(deadline_date);
