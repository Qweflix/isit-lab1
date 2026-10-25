# Изменённые файлы и перенос в isit-6

Исходная ветка — существующая `origin/isit-6`; рабочая ветка — `lab6-completion`. Изменения ограничены каталогом `isit lab6/`. `main` и предыдущие лабораторные не изменялись. PR и слияние в `main` не создавались. На GitHub изменения пока не отправлены.

В исходной ЛР6 были README, два JSON-файла и два пустых маркера. Добавлены серверное приложение MVC, данные заявок и категорий, HTTP-проверки, восемь скриншотов и отчёт. Исходные README и JSON побайтово сохранены в `docs/original`; удалены только их прежние действующие копии и пустые маркеры. Пять исходных командных задач использованы при проверке примера до переноса.

## Список относительно origin/isit-6

`A` — добавлен, `M` — изменён, `D` — удалён с прежнего пути (JSON сохранены в архиве).

```text
M	isit lab6/README.md
A	isit lab6/docs/baseline-http.log
A	isit lab6/docs/baseline-http.md
A	isit lab6/docs/browser-results.md
A	isit lab6/docs/browser-server.log
A	isit lab6/docs/browser.har
A	isit lab6/docs/capture_browser.py
A	isit lab6/docs/check_http.py
A	isit lab6/docs/defense.md
A	isit lab6/docs/final-http.log
A	isit lab6/docs/final-http.md
A	isit lab6/docs/http-headers.txt
A	isit lab6/docs/original/README.md
A	isit lab6/docs/original/members.json
A	isit lab6/docs/original/tasks.json
A	isit lab6/docs/requirements.md
A	isit lab6/images/01-home.png
A	isit lab6/images/02-tickets.png
A	isit lab6/images/03-ticket.png
A	isit lab6/images/04-create-errors.png
A	isit lab6/images/05-404.png
A	isit lab6/images/06-category.png
A	isit lab6/images/07-create.png
A	isit lab6/images/08-http.png
D	isit lab6/images/im
A	isit lab6/src/app/Controllers/CategoryController.php
A	isit lab6/src/app/Controllers/HomeController.php
A	isit lab6/src/app/Controllers/TicketController.php
A	isit lab6/src/app/Core/Router.php
A	isit lab6/src/app/Models/CategoryRepository.php
A	isit lab6/src/app/Models/JsonStorage.php
A	isit lab6/src/app/Models/TicketRepository.php
A	isit lab6/src/app/Views/categories/index.php
A	isit lab6/src/app/Views/categories/show.php
A	isit lab6/src/app/Views/errors/404.php
A	isit lab6/src/app/Views/errors/500.php
A	isit lab6/src/app/Views/home.php
A	isit lab6/src/app/Views/layout/footer.php
A	isit lab6/src/app/Views/layout/header.php
A	isit lab6/src/app/Views/tickets/create.php
A	isit lab6/src/app/Views/tickets/index.php
A	isit lab6/src/app/Views/tickets/show.php
A	isit lab6/src/app/autoload.php
A	isit lab6/src/app/helpers.php
A	isit lab6/src/config/app.php
D	isit lab6/src/del
A	isit lab6/src/public/css/style.css
A	isit lab6/src/public/index.php
A	isit lab6/src/routes/web.php
A	isit lab6/src/storage/categories.json
D	isit lab6/src/storage/members.json
D	isit lab6/src/storage/tasks.json
A	isit lab6/src/storage/tickets.json
A	isit lab6/docs/changes.md
```

## Сохранение результата в целевой ветке

Подготовлены два коммита: пример до переноса (`cff411f`) и итоговая система заявок с отчётом. Рабочая ветка продолжает историю `isit-6`, поэтому её можно сохранить в целевой ветке обычным push без принуждения:

```bash
git push origin lab6-completion:isit-6
```

Эта команда приведена как инструкция и не выполнялась автоматически. Она не затрагивает `main`. Если удалённая `isit-6` получила новые коммиты, обычный push отклонит несовместимое обновление; сначала следует сопоставить новые изменения. `reset --hard`, принудительный push и необоснованное слияние `main` не применялись.
