# Происхождение скриншотов

Снимки получены захватом экрана настоящего XTerm в Xvfb. В терминале выполнялся psql на PostgreSQL 17.11. Данные и ответы не подменялись; отдельная БД для съёмки возвращена к исходным данным после демонстрации.

| Проверка | Скриншот | Вывод psql |
| --- | --- | --- |
| Структура таблиц и внешние ключи | [PNG](../images/01-structure.png) | [TXT](screenshots/01-structure.txt) |
| Проверка ограничений: ожидаемые SQLSTATE | [PNG](../images/02-integrity.png) | [TXT](screenshots/02-integrity.txt) |
| CRUD заявки: INSERT / SELECT / UPDATE / DELETE | [PNG](../images/03-crud.png) | [TXT](screenshots/03-crud.txt) |
| JOIN, LEFT JOIN, N:M и отчёт руководителя | [PNG](../images/04-joins-report.png) | [TXT](screenshots/04-joins-report.txt) |
| EXPLAIN ANALYZE: фильтр по категории, до и после | [PNG](../images/05-index-category.png) | [TXT](screenshots/05-index-category.txt) |
| EXPLAIN ANALYZE: статус, сортировка и LIMIT | [PNG](../images/06-index-status-due.png) | [TXT](screenshots/06-index-status-due.txt) |

Планы на снимках — отдельный запуск для демонстрации, поэтому времена могут отличаться от основной таблицы измерений из final/06_optimization.log. Перед сравнением использован нагрузочный набор из 200 000 заявок и 400 000 комментариев.
