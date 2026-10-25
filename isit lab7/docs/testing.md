# Результаты тестирования REST API

Дата UTC: 2026-10-25T11:32:53+00:00. PHP 8.4.24 (cli) (built: Jul 31 2026 05:11:11) (NTS).

Проверены 26 PHP-файлов (`php -l`) и 90 HTTP-сценариев. Все API-запросы отправлены настоящим curl на встроенный сервер PHP.

| № | Запрос / сценарий | Ожидаемый код | Фактический код |
| --- | --- | --- | --- |
| 1 | `GET /api/tickets` | 200 | 200 |
| 2 | `GET /api/tickets/1` | 200 | 200 |
| 3 | `POST /api/tickets` | 201 | 201 |
| 4 | `GET /api/tickets/7` | 200 | 200 |
| 5 | `PATCH /api/tickets/7` | 200 | 200 |
| 6 | `PUT /api/tickets/7` | 200 | 200 |
| 7 | `DELETE /api/tickets/7` | 204 | 204 |
| 8 | `GET после удаления tickets` | 404 | 404 |
| 9 | `Повторное удаление tickets` | 404 | 404 |
| 10 | `Пустые поля tickets` | 422 | 422 |
| 11 | `Некорректный JSON tickets` | 400 | 400 |
| 12 | `GET /api/tickets/99999` | 404 | 404 |
| 13 | `DELETE /api/tickets` | 405 | 405 |
| 14 | `POST /api/tickets/1` | 405 | 405 |
| 15 | `PATCH /api/tickets/99999` | 404 | 404 |
| 16 | `GET /api/categories` | 200 | 200 |
| 17 | `GET /api/categories/1` | 200 | 200 |
| 18 | `POST /api/categories` | 201 | 201 |
| 19 | `GET /api/categories/4` | 200 | 200 |
| 20 | `PATCH /api/categories/4` | 200 | 200 |
| 21 | `PUT /api/categories/4` | 200 | 200 |
| 22 | `DELETE /api/categories/4` | 204 | 204 |
| 23 | `GET после удаления categories` | 404 | 404 |
| 24 | `Повторное удаление categories` | 404 | 404 |
| 25 | `Пустые поля categories` | 422 | 422 |
| 26 | `Некорректный JSON categories` | 400 | 400 |
| 27 | `GET /api/categories/99999` | 404 | 404 |
| 28 | `DELETE /api/categories` | 405 | 405 |
| 29 | `POST /api/categories/1` | 405 | 405 |
| 30 | `PATCH /api/categories/99999` | 404 | 404 |
| 31 | `GET /api/staff` | 200 | 200 |
| 32 | `GET /api/staff/1` | 200 | 200 |
| 33 | `POST /api/staff` | 201 | 201 |
| 34 | `GET /api/staff/4` | 200 | 200 |
| 35 | `PATCH /api/staff/4` | 200 | 200 |
| 36 | `PUT /api/staff/4` | 200 | 200 |
| 37 | `DELETE /api/staff/4` | 204 | 204 |
| 38 | `GET после удаления staff` | 404 | 404 |
| 39 | `Повторное удаление staff` | 404 | 404 |
| 40 | `Пустые поля staff` | 422 | 422 |
| 41 | `Некорректный JSON staff` | 400 | 400 |
| 42 | `GET /api/staff/99999` | 404 | 404 |
| 43 | `DELETE /api/staff` | 405 | 405 |
| 44 | `POST /api/staff/1` | 405 | 405 |
| 45 | `PATCH /api/staff/99999` | 404 | 404 |
| 46 | `GET /api/tickets?status=done` | 200 | 200 |
| 47 | `GET /api/tickets?category_id=2` | 200 | 200 |
| 48 | `GET /api/tickets?status=done&category_id=2` | 200 | 200 |
| 49 | `GET /api/tickets?category_id=99999` | 200 | 200 |
| 50 | `GET /api/categories/2/tickets` | 200 | 200 |
| 51 | `GET /api/categories/99999/tickets` | 404 | 404 |
| 52 | `DELETE /api/categories/1` | 409 | 409 |
| 53 | `Неверное поле title = Да` | 422 | 422 |
| 54 | `Неверное поле category_id = 1` | 422 | 422 |
| 55 | `Неверное поле category_id = 99999` | 422 | 422 |
| 56 | `Неверное поле status = unknown` | 422 | 422 |
| 57 | `Неверное поле priority = ['high']` | 422 | 422 |
| 58 | `Неверное поле title = None` | 422 | 422 |
| 59 | `Неверное поле description = ` | 422 | 422 |
| 60 | `POST /api/staff` | 422 | 422 |
| 61 | `PATCH /api/categories/1` | 422 | 422 |
| 62 | `Неверное тело: '{"title":'` | 400 | 400 |
| 63 | `Неверное тело: '[]'` | 400 | 400 |
| 64 | `Неверное тело: 'null'` | 400 | 400 |
| 65 | `Неверное тело: '"строка"'` | 400 | 400 |
| 66 | `Неверное тело: '1'` | 400 | 400 |
| 67 | `Неверное тело: ''` | 400 | 400 |
| 68 | `GET /api/missing` | 404 | 404 |
| 69 | `GET /api` | 404 | 404 |
| 70 | `GET /api/tickets/abc` | 404 | 404 |
| 71 | `GET /api/tickets/99999999999999999999999999999` | 404 | 404 |
| 72 | `GET /api/tickets?status=unknown` | 422 | 422 |
| 73 | `GET /api/tickets?category_id=abc` | 422 | 422 |
| 74 | `GET /api/tickets?status[]=done` | 422 | 422 |
| 75 | `HTML GET /` | 200 | 200 |
| 76 | `HTML GET /tickets` | 200 | 200 |
| 77 | `HTML GET /categories` | 200 | 200 |
| 78 | `HTML GET /tickets/create` | 200 | 200 |
| 79 | `HTML GET /nonexistent` | 404 | 404 |
| 80 | `HTML GET /storage/tickets.json` | 404 | 404 |
| 81 | `GET /api/tickets?q=%D0%9A%D0%9E%D0%9C%D0%9F%D0%AC%D0%AE%D0%A2%D0%95%D0%A0` | 200 | 200 |
| 82 | `GET /api/tickets?q=%D0%BD%D0%B5%D0%B8%D0%B7%D0%B2%D0%B5%D1%81%D1%82%D0%BD%D0%B0%D1%8F%20%D1%81%D1%82%D1%80%D0%BE%D0%BA%D0%B0` | 200 | 200 |
| 83 | `GET /api/tickets?q[]=x` | 422 | 422 |
| 84 | `Старый адрес /api/tasks` | 404 | 404 |
| 85 | `Старый адрес /api/projects` | 404 | 404 |
| 86 | `Старый адрес /api/users` | 404 | 404 |
| 87 | `Старый адрес /api/tasks/1` | 404 | 404 |
| 88 | `Старый адрес /api/projects/2/tasks` | 404 | 404 |
| 89 | `Ошибка чтения хранилища` | 500 | 500 |
| 90 | `Работа после восстановления хранилища` | 200 | 200 |

Проверены тела JSON, Content-Type, Location при 201, Allow при 405, пустое тело при 204, обновление только присланных полей, запрет изменения id, фильтры, вложенный список, ошибки проверки данных, запрет удаления связанной записи и сохранение JSON.

Тесты работают во временной копии src: исходные данные не меняются. В журнале есть одно намеренно вызванное исключение JsonException для проверки ответа 500; предупреждений, фатальных ошибок и непредвиденных исключений нет.

Полный протокол запросов и ответов: [final-transcript.json](final-transcript.json). Журнал сервера: [final-server.log](final-server.log).
