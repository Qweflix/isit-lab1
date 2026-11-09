DROP INDEX IF EXISTS idx_tickets_status_due;
ANALYZE tickets;
\echo ДО ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tickets
WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
CREATE INDEX idx_tickets_status_due ON tickets(status, due_date, id);
ANALYZE tickets;
\echo ПОСЛЕ ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tickets
WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
