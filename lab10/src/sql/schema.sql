-- Учебная БД: повторный запуск удаляет и заново создаёт перечисленные таблицы.
BEGIN;
DROP TABLE IF EXISTS auth_tokens, users;
DROP VIEW IF EXISTS supervisor_report;
DROP TABLE IF EXISTS ticket_tags, comments, staff_profiles, tickets, categories, staff, tags;

CREATE TABLE staff (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL CHECK (char_length(btrim(name)) >= 3),
    email VARCHAR(255) NOT NULL UNIQUE
        CHECK (email ~* '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$'),
    role VARCHAR(100) NOT NULL DEFAULT 'Инженер поддержки' CHECK (char_length(btrim(role)) > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff_profiles (
    staff_id INTEGER PRIMARY KEY REFERENCES staff(id) ON DELETE CASCADE,
    phone VARCHAR(30),
    telegram VARCHAR(50) UNIQUE
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL UNIQUE CHECK (char_length(btrim(title)) >= 3),
    description TEXT,
    owner_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE RESTRICT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tickets (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    assignee_id INTEGER REFERENCES staff(id) ON DELETE SET NULL,
    title VARCHAR(200) NOT NULL CHECK (char_length(btrim(title)) >= 3),
    description TEXT NOT NULL CHECK (char_length(btrim(description)) > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'new'
        CHECK (status IN ('new', 'in_progress', 'done')),
    priority VARCHAR(10) NOT NULL DEFAULT 'medium'
        CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    due_date DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    author_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE RESTRICT,
    body TEXT NOT NULL CHECK (char_length(btrim(body)) > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE CHECK (char_length(btrim(name)) > 0)
);

CREATE TABLE ticket_tags (
    ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (ticket_id, tag_id)
);
COMMIT;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    staff_id INTEGER UNIQUE REFERENCES staff(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE CHECK (email = lower(email)),
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('manager','operator'))
);
CREATE TABLE auth_tokens (
    token_hash CHAR(64) PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expires_at TIMESTAMPTZ NOT NULL
);
CREATE INDEX idx_auth_tokens_user ON auth_tokens(user_id);
CREATE INDEX idx_tickets_category ON tickets(category_id);
CREATE INDEX idx_tickets_assignee_status ON tickets(assignee_id,status);
CREATE INDEX idx_comments_ticket_created ON comments(ticket_id,created_at);
CREATE INDEX idx_tickets_status_due ON tickets(status,due_date);
