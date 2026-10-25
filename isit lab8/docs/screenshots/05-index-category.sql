DROP INDEX IF EXISTS idx_tickets_category_id;
ANALYZE tickets;
\echo ДО ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tickets WHERE category_id = 1500;
CREATE INDEX idx_tickets_category_id ON tickets(category_id);
ANALYZE tickets;
\echo ПОСЛЕ ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tickets WHERE category_id = 1500;
