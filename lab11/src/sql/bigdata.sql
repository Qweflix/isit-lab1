-- Дополнительные заявки для измерения построения CSV. Выполнять один раз после reset-db.
INSERT INTO tickets (category_id, assignee_id, title, description, status, priority)
SELECT (n % 3) + 1, (n % 3) + 1, 'Заявка для измерения №' || n,
       'Учебная нагрузка для проверки фонового отчёта',
       (ARRAY['new', 'in_progress', 'done'])[(n % 3) + 1],
       (ARRAY['low', 'medium', 'high'])[(n % 3) + 1]
FROM generate_series(1, 200000) AS n;
