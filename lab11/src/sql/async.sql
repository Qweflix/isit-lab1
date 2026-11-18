CREATE EXTENSION IF NOT EXISTS pgcrypto;
ALTER TABLE staff ADD COLUMN password_hash TEXT;
UPDATE staff SET password_hash = crypt('lab11-demo', gen_salt('bf'));
ALTER TABLE staff ALTER COLUMN password_hash SET NOT NULL;
INSERT INTO staff (name, email, role, password_hash)
VALUES ('Оператор Тестов', 'operator@example.test', 'Оператор', crypt('lab11-demo', gen_salt('bf')));
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    job_id CHAR(16) NOT NULL,
    staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE CASCADE,
    ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    body TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (job_id, staff_id)
);
CREATE TABLE reports (
    id BIGSERIAL PRIMARY KEY,
    requester_id INTEGER NOT NULL REFERENCES staff(id),
    status VARCHAR(20) NOT NULL DEFAULT 'queued' CHECK (status IN ('queued','processing','done','failed')),
    path TEXT,
    error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMPTZ
);
