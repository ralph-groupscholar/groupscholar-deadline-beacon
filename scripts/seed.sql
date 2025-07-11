INSERT INTO deadline_beacon_deadlines
    (title, organization, application_url, deadline_date, timezone, status, notes, created_at, updated_at)
VALUES
    ('STEM Bridge Scholars', 'STEM Bridge Fund', 'https://example.org/stem-bridge', '2026-03-01', 'America/New_York', 'open', 'Requires two letters of recommendation.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('First-Gen Pathways Grant', 'Pathways Collective', 'https://example.org/first-gen', '2026-03-15', 'America/Chicago', 'open', 'Essay prompt focuses on community leadership.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Community Leadership Award', 'Neighborhood Impact Council', 'https://example.org/leadership-award', '2026-04-01', 'America/Los_Angeles', 'open', 'Short video submission optional.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Health Equity Fellows', 'Health Equity Network', 'https://example.org/health-equity', '2026-02-20', 'America/New_York', 'open', 'Needs transcript upload.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO deadline_beacon_notifications
    (deadline_id, channel, sent_at, message)
VALUES
    (1, 'slack', CURRENT_TIMESTAMP - INTERVAL '2 days', 'Initial reminder scheduled for STEM Bridge Scholars.'),
    (2, 'email', CURRENT_TIMESTAMP - INTERVAL '5 days', 'First-Gen Pathways Grant: upcoming deadline reminder.');
