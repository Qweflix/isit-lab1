-- Дополнительная возможность: представление отчёта руководителя.
CREATE OR REPLACE VIEW supervisor_report AS
SELECT c.id AS category_id, c.title AS category_title,
       count(t.id) AS total,
       count(t.id) FILTER (WHERE t.status = 'new') AS new_count,
       count(t.id) FILTER (WHERE t.status = 'in_progress') AS in_progress_count,
       count(t.id) FILTER (WHERE t.status = 'done') AS resolved_count,
       count(t.id) FILTER (WHERE t.assignee_id IS NULL) AS unassigned_count
FROM categories c LEFT JOIN tickets t ON t.category_id = c.id
GROUP BY c.id, c.title;

SELECT * FROM supervisor_report ORDER BY category_id;
