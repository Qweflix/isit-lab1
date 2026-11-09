-- Отдельный нагрузочный набор: заменяет учебные записи синтетическими.
-- Для возврата к исходным данным выполните 01_schema.sql и 02_seed.sql.
BEGIN;
TRUNCATE ticket_tags, comments, staff_profiles, tickets, categories, staff, tags RESTART IDENTITY;
INSERT INTO staff(name, email)
SELECT 'Сотрудник ' || g, 'person' || g || '@example.test' FROM generate_series(1, 2000) g;
INSERT INTO staff_profiles(staff_id, phone)
SELECT g, '+7 900 ' || lpad(g::TEXT, 7, '0') FROM generate_series(1, 3) g;
INSERT INTO categories(title, owner_id)
SELECT 'Группа ' || g, g FROM generate_series(1, 2000) g;
INSERT INTO tags(name) VALUES ('срочно'), ('оборудование'), ('консультация'), ('проверка');
INSERT INTO tickets(category_id, assignee_id, title , description, status, priority, due_date, created_at)
SELECT 1 + (g - 1) % 2000,
       CASE WHEN g % 10 = 0 THEN NULL ELSE 1 + (g * 17) % 2000 END,
       'Тестовая заявка ' || g , 'Синтетическое обращение для измерения запросов',
       CASE g % 3 WHEN 0 THEN 'done' WHEN 1 THEN 'new' ELSE 'in_progress' END,
       CASE g % 3 WHEN 0 THEN 'low' WHEN 1 THEN 'medium' ELSE 'high' END,
       DATE '2026-01-01' + (g % 365),
       TIMESTAMPTZ '2026-01-01 00:00:00+00' + g * INTERVAL '1 second'
FROM generate_series(1, 200000) g;
INSERT INTO comments(ticket_id, author_id, body)
SELECT 1 + (g - 1) % 200000, 1 + (g * 19) % 2000, 'Нагрузочный комментарий ' || g
FROM generate_series(1, 400000) g;
INSERT INTO ticket_tags(ticket_id, tag_id)
SELECT g, 1 + g % 4 FROM generate_series(1, 100) g;
COMMIT;
ANALYZE;
SELECT (SELECT count(*) FROM tickets) AS main_rows,
       (SELECT count(*) FROM comments) AS comment_rows;
