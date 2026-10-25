# Проверка примера до переноса

Дата UTC: 2026-10-25T11:32:18+00:00. PHP 8.4.24 (cli) (built: Jul 31 2026 05:11:11) (NTS).

Проверены 26 PHP-файлов (`php -l`) и 82 HTTP-сценариев. Все API-запросы отправлены настоящим curl на встроенный сервер PHP.

| № | Запрос / сценарий | Ожидаемый код | Фактический код |
| --- | --- | --- | --- |
| 1 | `GET /api/tasks` | 200 | 200 |
| 2 | `GET /api/tasks/1` | 200 | 200 |
| 3 | `POST /api/tasks` | 201 | 201 |
| 4 | `GET /api/tasks/6` | 200 | 200 |
| 5 | `PATCH /api/tasks/6` | 200 | 200 |
| 6 | `PUT /api/tasks/6` | 200 | 200 |
| 7 | `DELETE /api/tasks/6` | 204 | 204 |
| 8 | `GET после удаления tasks` | 404 | 404 |
| 9 | `Повторное удаление tasks` | 404 | 404 |
| 10 | `Пустые поля tasks` | 422 | 422 |
| 11 | `Некорректный JSON tasks` | 400 | 400 |
| 12 | `GET /api/tasks/99999` | 404 | 404 |
| 13 | `DELETE /api/tasks` | 405 | 405 |
| 14 | `POST /api/tasks/1` | 405 | 405 |
| 15 | `PATCH /api/tasks/99999` | 404 | 404 |
| 16 | `GET /api/projects` | 200 | 200 |
| 17 | `GET /api/projects/1` | 200 | 200 |
| 18 | `POST /api/projects` | 201 | 201 |
| 19 | `GET /api/projects/4` | 200 | 200 |
| 20 | `PATCH /api/projects/4` | 200 | 200 |
| 21 | `PUT /api/projects/4` | 200 | 200 |
| 22 | `DELETE /api/projects/4` | 204 | 204 |
| 23 | `GET после удаления projects` | 404 | 404 |
| 24 | `Повторное удаление projects` | 404 | 404 |
| 25 | `Пустые поля projects` | 422 | 422 |
| 26 | `Некорректный JSON projects` | 400 | 400 |
| 27 | `GET /api/projects/99999` | 404 | 404 |
| 28 | `DELETE /api/projects` | 405 | 405 |
| 29 | `POST /api/projects/1` | 405 | 405 |
| 30 | `PATCH /api/projects/99999` | 404 | 404 |
| 31 | `GET /api/users` | 200 | 200 |
| 32 | `GET /api/users/1` | 200 | 200 |
| 33 | `POST /api/users` | 201 | 201 |
| 34 | `GET /api/users/4` | 200 | 200 |
| 35 | `PATCH /api/users/4` | 200 | 200 |
| 36 | `PUT /api/users/4` | 200 | 200 |
| 37 | `DELETE /api/users/4` | 204 | 204 |
| 38 | `GET после удаления users` | 404 | 404 |
| 39 | `Повторное удаление users` | 404 | 404 |
| 40 | `Пустые поля users` | 422 | 422 |
| 41 | `Некорректный JSON users` | 400 | 400 |
| 42 | `GET /api/users/99999` | 404 | 404 |
| 43 | `DELETE /api/users` | 405 | 405 |
| 44 | `POST /api/users/1` | 405 | 405 |
| 45 | `PATCH /api/users/99999` | 404 | 404 |
| 46 | `GET /api/tasks?status=done` | 200 | 200 |
| 47 | `GET /api/tasks?project_id=2` | 200 | 200 |
| 48 | `GET /api/tasks?status=done&project_id=2` | 200 | 200 |
| 49 | `GET /api/tasks?project_id=99999` | 200 | 200 |
| 50 | `GET /api/projects/2/tasks` | 200 | 200 |
| 51 | `GET /api/projects/99999/tasks` | 404 | 404 |
| 52 | `DELETE /api/projects/1` | 409 | 409 |
| 53 | `Неверное поле title = Да` | 422 | 422 |
| 54 | `Неверное поле project_id = 1` | 422 | 422 |
| 55 | `Неверное поле project_id = 99999` | 422 | 422 |
| 56 | `Неверное поле status = unknown` | 422 | 422 |
| 57 | `Неверное поле priority = ['high']` | 422 | 422 |
| 58 | `Неверное поле title = None` | 422 | 422 |
| 59 | `Неверное поле due_date = bad-date` | 422 | 422 |
| 60 | `POST /api/users` | 422 | 422 |
| 61 | `PATCH /api/projects/1` | 422 | 422 |
| 62 | `Неверное тело: '{"title":'` | 400 | 400 |
| 63 | `Неверное тело: '[]'` | 400 | 400 |
| 64 | `Неверное тело: 'null'` | 400 | 400 |
| 65 | `Неверное тело: '"строка"'` | 400 | 400 |
| 66 | `Неверное тело: '1'` | 400 | 400 |
| 67 | `Неверное тело: ''` | 400 | 400 |
| 68 | `GET /api/missing` | 404 | 404 |
| 69 | `GET /api` | 404 | 404 |
| 70 | `GET /api/tasks/abc` | 404 | 404 |
| 71 | `GET /api/tasks/99999999999999999999999999999` | 404 | 404 |
| 72 | `GET /api/tasks?status=unknown` | 422 | 422 |
| 73 | `GET /api/tasks?project_id=abc` | 422 | 422 |
| 74 | `GET /api/tasks?status[]=done` | 422 | 422 |
| 75 | `HTML GET /` | 200 | 200 |
| 76 | `HTML GET /tasks` | 200 | 200 |
| 77 | `HTML GET /projects` | 200 | 200 |
| 78 | `HTML GET /tasks/create` | 200 | 200 |
| 79 | `HTML GET /nonexistent` | 404 | 404 |
| 80 | `HTML GET /storage/tasks.json` | 404 | 404 |
| 81 | `Ошибка чтения хранилища` | 500 | 500 |
| 82 | `Работа после восстановления хранилища` | 200 | 200 |

Проверены тела JSON, Content-Type, Location при 201, Allow при 405, пустое тело при 204, обновление только присланных полей, запрет изменения id, фильтры, вложенный список, ошибки проверки данных, запрет удаления связанной записи и сохранение JSON.

Тесты работают во временной копии src: исходные данные не меняются. В журнале есть одно намеренно вызванное исключение JsonException для проверки ответа 500; предупреждений, фатальных ошибок и непредвиденных исключений нет.

Полный протокол запросов и ответов: [baseline-transcript.json](baseline-transcript.json). Журнал сервера: [baseline-server.log](baseline-server.log).
