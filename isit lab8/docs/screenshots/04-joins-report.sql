SELECT t.id, c.title AS category, s.name AS assignee
FROM tickets t JOIN categories c ON c.id = t.category_id
LEFT JOIN staff s ON s.id = t.assignee_id ORDER BY t.id;
SELECT t.id, t.title, g.name AS tag FROM tickets t
JOIN ticket_tags tt ON tt.ticket_id = t.id JOIN tags g ON g.id = tt.tag_id
WHERE g.name = 'срочно' ORDER BY t.id;
SELECT category_id, total, new_count, in_progress_count, resolved_count
FROM supervisor_report ORDER BY category_id;
