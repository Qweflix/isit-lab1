-- Запускать после 05_test_data.sql. Сравниваются реальные планы и время одного выполнения.
\timing on
DROP INDEX IF EXISTS idx_tasks_project_id;
DROP INDEX IF EXISTS idx_tasks_assignee_status;
DROP INDEX IF EXISTS idx_comments_task_created;
DROP INDEX IF EXISTS idx_tasks_status_due;
ANALYZE;
\echo === N1 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE title ILIKE '%12345%';
\echo === N2 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE status IN ('new', 'in_progress', 'done');
\echo === Q1 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE project_id = 1500;
CREATE INDEX idx_tasks_project_id ON tasks (project_id);
ANALYZE;
\echo === Q1 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE project_id = 1500;
\echo === Q2 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE assignee_id = 18 AND status = 'new';
CREATE INDEX idx_tasks_assignee_status ON tasks (assignee_id, status);
ANALYZE;
\echo === Q2 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE assignee_id = 18 AND status = 'new';
\echo === Q3 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, body, created_at FROM comments WHERE task_id = 12345 ORDER BY created_at;
CREATE INDEX idx_comments_task_created ON comments (task_id, created_at);
ANALYZE;
\echo === Q3 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, body, created_at FROM comments WHERE task_id = 12345 ORDER BY created_at;
\echo === Q4 BEFORE ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tasks WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
CREATE INDEX idx_tasks_status_due ON tasks (status, due_date, id);
ANALYZE;
\echo === Q4 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tasks WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
\echo === N1 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE title ILIKE '%12345%';
\echo === N2 AFTER ===
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tasks WHERE status IN ('new', 'in_progress', 'done');
\timing off
