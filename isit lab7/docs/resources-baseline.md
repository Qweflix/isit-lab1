# Шаг 1. Ресурсы примера до переноса

| Ресурс | URL | Методы | Назначение | Поля JSON |
| --- | --- | --- | --- | --- |
| Задачи | `/api/tasks`, `/api/tasks/{id}` | GET, POST, PUT, PATCH, DELETE | CRUD задач, фильтры status/project_id | id, project_id, title, status, priority, due_date |
| Проекты | `/api/projects`, `/api/projects/{id}`, `/api/projects/{id}/tasks` | GET, POST, PUT, PATCH, DELETE | CRUD проектов, связанные задачи | id, title, description |
| Участники | `/api/users`, `/api/users/{id}` | GET, POST, PUT, PATCH, DELETE | CRUD участников команды | id, name, email, role |

PUT и PATCH в учебной работе обновляют только присланные поля. Удаление проекта с задачами запрещено (409).
