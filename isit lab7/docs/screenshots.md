# Скриншоты терминала

Все PNG получены захватом экрана настоящего XTerm в Xvfb. В каждом терминале выполнялся curl на отдельном встроенном сервере PHP с временной копией src. Ответы не подменялись, исходные JSON не изменялись. Текст каждого ответа сохранён рядом в docs.

| Метод и адрес | Код | Скриншот | Ответ curl |
| --- | --- | --- | --- |
| `GET /api/tickets/3` | 200 | [PNG](../images/01-get.png) | [TXT](01-get.txt) |
| `POST /api/tickets` | 201 | [PNG](../images/02-post.png) | [TXT](02-post.txt) |
| `PATCH /api/tickets/7` | 200 | [PNG](../images/03-patch.png) | [TXT](03-patch.txt) |
| `DELETE /api/tickets/7` | 204 | [PNG](../images/04-delete.png) | [TXT](04-delete.txt) |
| `POST /api/tickets` | 400 | [PNG](../images/05-error-400.png) | [TXT](05-error-400.txt) |
| `GET /api/tickets/99999` | 404 | [PNG](../images/06-error-404.png) | [TXT](06-error-404.txt) |
| `DELETE /api/categories` | 405 | [PNG](../images/07-error-405.png) | [TXT](07-error-405.txt) |
| `POST /api/tickets` | 422 | [PNG](../images/08-error-422.png) | [TXT](08-error-422.txt) |
| `DELETE /api/categories/1` | 409 | [PNG](../images/09-error-409.png) | [TXT](09-error-409.txt) |

Видны метод, URL, тело запроса (где оно есть), заголовки, тело ответа и HTTP-код. Для 204 тело ответа отсутствует. Postman не использовался: методичка допускает curl.
