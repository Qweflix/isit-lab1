BEGIN;
INSERT INTO tickets(category_id, title, description)
VALUES (1, 'Проверка CRUD в терминале', 'Оборудование не работает.') RETURNING id AS new_id \gset
SELECT id, title, status FROM tickets WHERE id = :new_id;
UPDATE tickets SET status = 'done' WHERE id = :new_id RETURNING id, status;
DELETE FROM tickets WHERE id = :new_id RETURNING id;
ROLLBACK;
