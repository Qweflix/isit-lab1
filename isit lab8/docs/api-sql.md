# Соответствие REST API и SQL

Адреса взяты из выполненной ЛР7. SQL-примеры находятся в [04_crud.sql](../sql/04_crud.sql). HTTP-сервер к БД в ЛР8 не подключается: здесь подготовлены операции для следующей работы.

| Метод | Адрес | SQL-операция |
| --- | --- | --- |
| GET | `/api/tickets` | SELECT из tickets, список |
| GET | `/api/tickets/{id}` | SELECT из tickets WHERE id = … |
| POST | `/api/tickets` | INSERT INTO tickets … RETURNING id |
| PUT | `/api/tickets/{id}` | UPDATE tickets SET … WHERE id = … |
| PATCH | `/api/tickets/{id}` | UPDATE tickets SET … WHERE id = … |
| DELETE | `/api/tickets/{id}` | DELETE FROM tickets WHERE id = … |
| GET | `/api/categories` | SELECT из categories, список |
| GET | `/api/categories/{id}` | SELECT из categories WHERE id = … |
| POST | `/api/categories` | INSERT INTO categories … RETURNING id |
| PUT | `/api/categories/{id}` | UPDATE categories SET … WHERE id = … |
| PATCH | `/api/categories/{id}` | UPDATE categories SET … WHERE id = … |
| DELETE | `/api/categories/{id}` | DELETE FROM categories WHERE id = … |
| GET | `/api/staff` | SELECT из staff, список |
| GET | `/api/staff/{id}` | SELECT из staff WHERE id = … |
| POST | `/api/staff` | INSERT INTO staff … RETURNING id |
| PUT | `/api/staff/{id}` | UPDATE staff SET … WHERE id = … |
| PATCH | `/api/staff/{id}` | UPDATE staff SET … WHERE id = … |
| DELETE | `/api/staff/{id}` | DELETE FROM staff WHERE id = … |
| GET | `/api/categories/{id}/tickets` | SELECT tickets с category_id и JOIN categories |

Фильтр status/category_id представлен WHERE; порядок — ORDER BY due_date NULLS LAST, id; поиск q — ILIKE. Для необязательного исполнителя используется LEFT JOIN staff. Связь с тегами и отчёт GROUP BY добавлены как операции реляционной модели.

В базе категория дополнена обязательным owner_id, заявки — необязательным assignee_id и due_date и временем создания. При подключении API на следующем этапе необходимо согласовать новые поля и их валидацию. В демонстрационном CRUD ответственный сотрудник задан как существующий staff.id=1. PUT/PATCH сохраняют учебную семантику частичного обновления.
