-- Начальные записи. Перед повторным заполнением выполните 01_schema.sql.
BEGIN;
INSERT INTO users (id, name, email, role) VALUES
    (1, 'Алексей Смирнов', 'alex@example.test', 'admin'),
    (2, 'Мария Десяк', 'maria@example.test', 'manager'),
    (3, 'Дмитрий Кузнецов', 'dmitry@example.test', 'developer');

INSERT INTO user_profiles (user_id, phone, telegram) VALUES
    (1, '+7 900 000-00-01', 'support_alex'),
    (2, NULL, 'support_maria'),
    (3, '+7 900 000-00-03', NULL);

INSERT INTO projects (id, title, description, owner_id) VALUES
    (1, 'Серверная часть', 'Разработка PHP и REST API.', 1),
    (2, 'Интерфейс', 'Клиентская часть приложения.', 2),
    (3, 'Тестирование', NULL, 1);

INSERT INTO tasks (id, title, status, priority, due_date, project_id, assignee_id) VALUES
    (1, 'Спроектировать схему маршрутизации MVC', 'done', 'high', '2026-10-20', 1, 1),
    (2, 'Сверстать макет списка задач и профиля участника', 'in_progress', 'high', '2026-10-26', 2, 2),
    (3, 'Провести нагрузочное тестирование роутера', 'new', 'medium', '2026-10-28', 3, 3),
    (4, 'Добавить стили для отображения статусов и приоритетов', 'done', 'low', '2026-10-22', 2, NULL),
    (5, 'Реализовать сохранение сущностей в JSON-хранилище', 'in_progress', 'high', '2026-10-27', 1, NULL),
    (6, 'Обновить документацию API', 'new', 'low', NULL, 2, 2),
    (7, 'Подготовить демонстрацию', 'done', 'medium', NULL, 3, 3);

INSERT INTO tags (id, name) VALUES
    (1, 'срочно'),
    (2, 'backend'),
    (3, 'frontend'),
    (4, 'проверка');

INSERT INTO comments (id, task_id, author_id, body) VALUES
    (1, 1, 1, 'Работа запланирована.'),
    (2, 2, 2, 'Информация уточнена.'),
    (3, 3, 3, 'Проверка выполнена.'),
    (4, 4, 1, 'Ожидается дополнительная информация.'),
    (5, 6, 2, 'Результат подтверждён.');

INSERT INTO task_tags (task_id, tag_id) VALUES
    (1, 1),
    (1, 2),
    (2, 3),
    (3, 4),
    (4, 2);

SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT max(id) FROM users), true);
SELECT setval(pg_get_serial_sequence('projects', 'id'), (SELECT max(id) FROM projects), true);
SELECT setval(pg_get_serial_sequence('tasks', 'id'), (SELECT max(id) FROM tasks), true);
SELECT setval(pg_get_serial_sequence('tags', 'id'), (SELECT max(id) FROM tags), true);
SELECT setval(pg_get_serial_sequence('comments', 'id'), (SELECT max(id) FROM comments), true);
COMMIT;
SELECT count(*) AS initial_record_count FROM tasks;
