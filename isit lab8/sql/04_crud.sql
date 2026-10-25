-- Демонстрация всех методов трёх API-ресурсов. Изменения откатываются в конце.
-- Значения SERIAL могут иметь пропуски: ID берутся из RETURNING, а не угадываются.
BEGIN;
-- POST /api/projects
INSERT INTO projects(title, description, owner_id)
VALUES ('Временная запись для CRUD', 'Проверка создания', 1) RETURNING id AS new_project_id \gset
-- POST /api/users
INSERT INTO users(name, email) VALUES ('Анна Иванова', 'anna@example.test') RETURNING id AS new_user_id \gset
-- POST /api/tasks
INSERT INTO tasks(project_id, assignee_id, title )
VALUES (:new_project_id, :new_user_id, 'Проверка CRUD') RETURNING id AS new_task_id \gset

-- Добавление тегов к созданной записи.
INSERT INTO task_tags(task_id, tag_id) VALUES (:new_task_id, 1), (:new_task_id, 4);
-- GET /api/tasks?status=new&project_id=... + сортировка.
SELECT id, title, status, due_date FROM tasks
WHERE status = 'new' AND project_id = :new_project_id ORDER BY due_date NULLS LAST, id;
-- GET /api/tasks/{id}
SELECT * FROM tasks WHERE id = :new_task_id;
-- GET /api/tasks?q=... (поиск по названию из ЛР7).
SELECT id, title FROM tasks WHERE title ILIKE '%CRUD%' ORDER BY id;
-- GET /api/projects/{id}/tasks; LEFT JOIN сохраняет записи без исполнителя.
SELECT t.id, t.title, p.title AS related_title, u.name AS assignee_name
FROM tasks t JOIN projects p ON p.id = t.project_id
LEFT JOIN users u ON u.id = t.assignee_id
WHERE t.project_id = 1 ORDER BY t.id;
-- Связь N:M: записи с тегом «срочно».
SELECT t.id, t.title, g.name AS tag
FROM tasks t JOIN task_tags tt ON tt.task_id = t.id
JOIN tags g ON g.id = tt.tag_id WHERE g.name = 'срочно' ORDER BY t.id;
-- GROUP BY: отчёт по связанной сущности, включая группы без записей.
SELECT p.id, p.title, count(t.id) AS total,
       count(t.id) FILTER (WHERE t.status = 'done') AS resolved
FROM projects p LEFT JOIN tasks t ON t.project_id = p.id GROUP BY p.id, p.title ORDER BY p.id;
-- GET /api/tasks: список; GET /api/projects и /api/projects/{id}.
SELECT id, title, status FROM tasks ORDER BY id;
SELECT * FROM projects ORDER BY id;
SELECT * FROM projects WHERE id = :new_project_id;
-- GET /api/users и /api/users/{id}.
SELECT * FROM users ORDER BY id;
SELECT * FROM users WHERE id = :new_user_id;

-- PATCH/PUT /api/tasks/{id}; предварительный SELECT с тем же WHERE.
SELECT id, status FROM tasks WHERE id = :new_task_id;
UPDATE tasks SET status = 'done' WHERE id = :new_task_id RETURNING id, title, status;
-- PATCH/PUT /api/projects/{id}
UPDATE projects SET description = 'Описание обновлено' WHERE id = :new_project_id RETURNING id, description;
-- PATCH/PUT /api/users/{id}
UPDATE users SET name = 'Анна Петрова' WHERE id = :new_user_id RETURNING id, name;
-- DELETE /api/tasks/{id}; зависимые комментарии/теги удаляются автоматически.
SELECT id FROM tasks WHERE id = :new_task_id;
DELETE FROM tasks WHERE id = :new_task_id RETURNING id;
-- DELETE /api/projects/{id} и DELETE /api/users/{id}: теперь ссылок нет.
DELETE FROM projects WHERE id = :new_project_id RETURNING id;
DELETE FROM users WHERE id = :new_user_id RETURNING id;
ROLLBACK;
