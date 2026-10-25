-- Демонстрация всех методов трёх API-ресурсов. Изменения откатываются в конце.
-- Значения SERIAL могут иметь пропуски: ID берутся из RETURNING, а не угадываются.
BEGIN;
-- POST /api/categories
INSERT INTO categories(title, description, owner_id)
VALUES ('Временная запись для CRUD', 'Проверка создания', 1) RETURNING id AS new_category_id \gset
-- POST /api/staff
INSERT INTO staff(name, email) VALUES ('Анна Иванова', 'anna@example.test') RETURNING id AS new_staff_id \gset
-- POST /api/tickets
INSERT INTO tickets(category_id, assignee_id, title , description)
VALUES (:new_category_id, :new_staff_id, 'Проверка CRUD', 'Описание проверки') RETURNING id AS new_ticket_id \gset

-- Добавление тегов к созданной записи.
INSERT INTO ticket_tags(ticket_id, tag_id) VALUES (:new_ticket_id, 1), (:new_ticket_id, 4);
-- GET /api/tickets?status=new&category_id=... + сортировка.
SELECT id, title, status, due_date FROM tickets
WHERE status = 'new' AND category_id = :new_category_id ORDER BY due_date NULLS LAST, id;
-- GET /api/tickets/{id}
SELECT * FROM tickets WHERE id = :new_ticket_id;
-- GET /api/tickets?q=... (поиск по названию из ЛР7).
SELECT id, title FROM tickets WHERE title ILIKE '%CRUD%' ORDER BY id;
-- GET /api/categories/{id}/tickets; LEFT JOIN сохраняет записи без исполнителя.
SELECT t.id, t.title, p.title AS related_title, u.name AS assignee_name
FROM tickets t JOIN categories p ON p.id = t.category_id
LEFT JOIN staff u ON u.id = t.assignee_id
WHERE t.category_id = 1 ORDER BY t.id;
-- Связь N:M: записи с тегом «срочно».
SELECT t.id, t.title, g.name AS tag
FROM tickets t JOIN ticket_tags tt ON tt.ticket_id = t.id
JOIN tags g ON g.id = tt.tag_id WHERE g.name = 'срочно' ORDER BY t.id;
-- GROUP BY: отчёт по связанной сущности, включая группы без записей.
SELECT p.id, p.title, count(t.id) AS total,
       count(t.id) FILTER (WHERE t.status = 'done') AS resolved
FROM categories p LEFT JOIN tickets t ON t.category_id = p.id GROUP BY p.id, p.title ORDER BY p.id;
-- GET /api/tickets: список; GET /api/categories и /api/categories/{id}.
SELECT id, title, status FROM tickets ORDER BY id;
SELECT * FROM categories ORDER BY id;
SELECT * FROM categories WHERE id = :new_category_id;
-- GET /api/staff и /api/staff/{id}.
SELECT * FROM staff ORDER BY id;
SELECT * FROM staff WHERE id = :new_staff_id;

-- PATCH/PUT /api/tickets/{id}; предварительный SELECT с тем же WHERE.
SELECT id, status FROM tickets WHERE id = :new_ticket_id;
UPDATE tickets SET status = 'done' WHERE id = :new_ticket_id RETURNING id, title, status;
-- PATCH/PUT /api/categories/{id}
UPDATE categories SET description = 'Описание обновлено' WHERE id = :new_category_id RETURNING id, description;
-- PATCH/PUT /api/staff/{id}
UPDATE staff SET name = 'Анна Петрова' WHERE id = :new_staff_id RETURNING id, name;
-- DELETE /api/tickets/{id}; зависимые комментарии/теги удаляются автоматически.
SELECT id FROM tickets WHERE id = :new_ticket_id;
DELETE FROM tickets WHERE id = :new_ticket_id RETURNING id;
-- DELETE /api/categories/{id} и DELETE /api/staff/{id}: теперь ссылок нет.
DELETE FROM categories WHERE id = :new_category_id RETURNING id;
DELETE FROM staff WHERE id = :new_staff_id RETURNING id;
ROLLBACK;
