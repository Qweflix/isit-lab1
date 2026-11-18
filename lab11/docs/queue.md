# Очередь ЛР11: устройство и проверка

## 1. Состав системы

`lab11/src` содержит копию PHP-интерфейса и REST API ЛР7, SQL-модель заявок из ЛР8 и новые компоненты очереди. В ЛР8 PHP-приложения ещё не было: там были схема PostgreSQL, данные и SQL-проверки. ЛР9 и ЛР10 в полученной ветке отсутствуют. Поэтому `Cache.php`, клиент `/clients/` и тесты на 114 пунктов из шаблона преподавателя здесь отсутствуют; пустые копии этих файлов не создавались.

Сервисы: PostgreSQL 16 (`db`), Redis 7 (`redis`), PHP 8.3 HTTP (`app`), отдельный PHP-процесс (`worker`). Порты хоста: 5433, 6380 и 8088. Внутри Compose используются имена `db` и `redis`, поэтому приложение и воркер гарантированно обращаются к одним сервисам.

## 2. Ключи и формат задания

| Ключ Redis | Тип | Назначение |
| --- | --- | --- |
| `queue:default` | list | Ожидающие задания |
| `queue:default:processing` | list | Взятые, но не подтверждённые задания |
| `queue:default:delayed` | sorted set | Отложенные повторы; score — UNIX-время |
| `queue:default:dead` | list | Задания после исчерпания попыток |

Сообщение — JSON: `id` (16 шестнадцатеричных символов), `type`, `payload`, `attempts`, `created_at`; после ошибки добавляется `error`. Для уведомления payload содержит только `ticket_id`, `status`, `actor_id`; для отчёта — `report_id`. Полные записи не копируются в Redis.

`Queue::push()` делает `LPUSH`, а `Queue::reserve()` — блокирующий `BLMOVE queue:default queue:default:processing RIGHT LEFT 2`. Поэтому старая задача берётся первой (FIFO), а взятая задача остаётся в Redis до `ack()` (`LREM` одного точного JSON-сообщения). Очередь обеспечивает выполнение *как минимум один раз*, а не строго один раз.

## 3. Ошибки, повторы, восстановление

Воркер выбирает класс по типу через `Jobs::handle()`. При исключении `Queue::fail()` атомарным Lua-скриптом удаляет исходное сообщение из `processing` и добавляет новую версию в `delayed` либо `dead`. После первого отказа задержка 2 с, после второго — 4 с, после третьего задача попадает в `dead`. В теоретическом описании упомянут ряд 2/4/8 с; сценарий проверки шага 3.2 требует **три попытки** и отправку в `dead` после третьего отказа, поэтому ожидания 8 с здесь нет.

`releaseDelayed()` переносит просроченные записи из sorted set в обычную очередь атомарным Lua-скриптом. При запуске воркер вызывает `recover()` и возвращает все записи из `processing` в очередь. После `SIGKILL` испытание показало `processing = 1`, следующий воркер вывел «Восстановлено в очередь: 1» и выполнил задачу. `SIGTERM` и `SIGINT` перехватываются через `pcntl`: текущий обработчик заканчивает, выполняется `ack`, затем цикл завершается.

Текущий алгоритм `recover()` рассчитан на **один активный воркер**. Одновременный запуск второго воркера во время выполнения первого может повторно выдать его задачу. Для нескольких воркеров потребовались бы индивидуальные processing-списки или lease с отметкой времени. Также изменение статуса в PostgreSQL и `LPUSH` в Redis не образуют общую транзакцию: при отказе Redis между ними уведомление может не попасть в очередь. Для критичных событий нужен transactional outbox. В учебной системе эти ограничения сохранены и явно указаны.

## 4. Идемпотентность

`notifications` имеет уникальную пару `(job_id, staff_id)`; `NotificationJob` вставляет записи через `ON CONFLICT (job_id, staff_id) DO NOTHING`. Повтор того же задания не создаёт дубли. Автор изменения исключается. Получатели — сотрудники, назначенные владельцами категорий в модели ЛР8.

`ReportJob` проверяет статус `done`: уже сформированный отчёт повторно не строится. CSV пишется во временный файл и переименовывается в `report-{id}.csv` после успешной записи. При ошибке временный файл удаляется, `reports.status` становится `failed`, текст причины сохраняется в `reports.error`. При повторе статус переходит в `processing`, затем в `done` или снова `failed`.

## 5. Запуск и наблюдение

Из `lab11/src`:

```bash
docker compose up -d --build db redis app
docker compose ps
docker compose exec app php bin/redis-check.php
docker compose exec app php bin/queue-check.php
docker compose up -d worker
docker compose exec app php bin/queue-stats.php --dead
docker compose exec redis redis-cli LRANGE queue:default 0 -1
docker compose exec redis redis-cli LRANGE queue:default:processing 0 -1
docker compose exec redis redis-cli ZRANGE queue:default:delayed 0 -1 WITHSCORES
docker compose logs -f worker
```

Для управляемых опытов сначала `docker compose stop worker`, затем поставить задания через `bin/queue-demo.php` и запустить `docker compose run --rm --no-deps worker php bin/worker.php --drain`. Для сброса **только учебной БД и четырёх ключей этой очереди**: `docker compose exec app php bin/reset-db.php`. Сброс удаляет учебные данные и не применяется к рабочей БД.

Проверка HTTP:

```bash
curl -i -u alexey@example.test:lab11-demo -X PATCH http://localhost:8088/api/tickets/1 \
  -H 'Content-Type: application/json' --data '{"status":"done"}'
curl -i -u alexey@example.test:lab11-demo -X POST http://localhost:8088/api/reports \
  -H 'Content-Type: application/json' --data '{}'
curl -u alexey@example.test:lab11-demo http://localhost:8088/api/reports/1
curl -u alexey@example.test:lab11-demo -o report.csv http://localhost:8088/api/reports/1/download
```

Учебные учётные записи заданы в `sql/async.sql`: `alexey@example.test`, `maria@example.test`, `dmitry@example.test`, `operator@example.test`, пароль `lab11-demo`. Basic Auth используется для локальной лабораторной среды; при удалённом доступе нужен HTTPS.

## 6. Проверки

`tests/api-tests.php` содержит 26 проверенных утверждений: PostgreSQL и Redis, прежний API, авторизация, смена статуса, права, уведомления и отсутствие дублей, `BLMOVE`/`ack`, отчёт и CSV, статус `failed` с причиной, синхронный режим, FIFO. Дополнительно выполнены ручные опыты с `--fail`, `--flaky`, `--slow`, `SIGKILL`, `SIGTERM` и 10 измерений отчёта. Фактические журналы находятся в `docs/evidence/`.
