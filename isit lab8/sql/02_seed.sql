-- Начальные записи. Перед повторным заполнением выполните 01_schema.sql.
BEGIN;
INSERT INTO staff (id, name, email, role) VALUES
    (1, 'Алексей Смирнов', 'alexey@example.test', 'Инженер поддержки'),
    (2, 'Мария Десяк', 'maria@example.test', 'Специалист по программному обеспечению'),
    (3, 'Дмитрий Кузнецов', 'dmitry@example.test', 'Системный администратор');

INSERT INTO staff_profiles (staff_id, phone, telegram) VALUES
    (1, '+7 900 000-00-01', 'support_alex'),
    (2, NULL, 'support_maria'),
    (3, '+7 900 000-00-03', NULL);

INSERT INTO categories (id, title, description, owner_id) VALUES
    (1, 'Оборудование', 'Компьютеры, принтеры и периферийные устройства.', 1),
    (2, 'Программное обеспечение', 'Установка, обновление и ошибки приложений.', 2),
    (3, 'Сеть и доступы', 'Сетевые подключения, учётные записи и права доступа.', 3);

INSERT INTO tickets (id, category_id, title, description, status, priority, assignee_id, due_date) VALUES
    (1, 1, 'Не включается рабочий компьютер', 'После нажатия кнопки питания индикаторы не загораются.', 'new', 'high', 1, '2026-10-28'),
    (2, 2, 'Ошибка запуска офисного приложения', 'При открытии документа появляется сообщение об ошибке лицензии.', 'in_progress', 'medium', 2, '2026-10-29'),
    (3, 3, 'Восстановление доступа к почте', 'После смены пароля настроен вход в корпоративную почту.', 'done', 'high', 3, NULL),
    (4, 1, 'Принтер не печатает документы', 'Документы остаются в очереди печати на втором этаже.', 'in_progress', 'medium', NULL, '2026-10-30'),
    (5, 3, 'Нет подключения к сети', 'На рабочем месте пропало подключение к внутренним ресурсам.', 'new', 'critical', NULL, NULL),
    (6, 2, 'Обновление антивируса', 'Установлены обновления и выполнена проверка компьютера.', 'done', 'low', 2, '2026-10-26');

INSERT INTO tags (id, name) VALUES
    (1, 'срочно'),
    (2, 'оборудование'),
    (3, 'консультация'),
    (4, 'проверка');

INSERT INTO comments (id, ticket_id, author_id, body) VALUES
    (1, 1, 1, 'Обращение зарегистрировано.'),
    (2, 2, 2, 'Информация уточнена.'),
    (3, 3, 3, 'Проверка выполнена.'),
    (4, 4, 1, 'Ожидается дополнительная информация.'),
    (5, 6, 2, 'Результат подтверждён.');

INSERT INTO ticket_tags (ticket_id, tag_id) VALUES
    (1, 1),
    (1, 2),
    (2, 3),
    (3, 4),
    (4, 2);

SELECT setval(pg_get_serial_sequence('staff', 'id'), (SELECT max(id) FROM staff), true);
SELECT setval(pg_get_serial_sequence('categories', 'id'), (SELECT max(id) FROM categories), true);
SELECT setval(pg_get_serial_sequence('tickets', 'id'), (SELECT max(id) FROM tickets), true);
SELECT setval(pg_get_serial_sequence('tags', 'id'), (SELECT max(id) FROM tags), true);
SELECT setval(pg_get_serial_sequence('comments', 'id'), (SELECT max(id) FROM comments), true);
COMMIT;
SELECT count(*) AS initial_record_count FROM tickets;
