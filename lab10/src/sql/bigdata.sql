-- Только отдельная учебная БД после reset-db.php. Итог: ровно 500 000 заявок.
-- Предметная область и существующие шесть заявок сохраняются.
INSERT INTO tickets (id,category_id,assignee_id,title,description,status,priority,due_date)
SELECT n, 1+(n%3), CASE WHEN n%4=0 THEN NULL ELSE 1+(n%3) END,
    CASE WHEN n%101=0 THEN 'Проверка сетевого подключения ' ELSE 'Обращение в поддержку ' END || n,
    'Нагрузочная учебная заявка №' || n,
    (ARRAY['new','in_progress','done'])[1+(n%3)],
    (ARRAY['low','medium','high','critical'])[1+(n%4)],
    DATE '2026-11-01' + (n%60)
FROM generate_series(7,500000) AS n
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('tickets','id'),(SELECT max(id) FROM tickets),true);
ANALYZE tickets;
