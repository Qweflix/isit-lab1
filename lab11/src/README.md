# Приложение ЛР11

Этот каталог запускается отдельно от предыдущих лабораторных. Система учёта заявок использует PHP-интерфейс и API ЛР7, схему PostgreSQL ЛР8 и новую очередь Redis.

```bash
docker compose up -d --build db redis app
docker compose exec app php bin/queue-check.php
docker compose exec app php tests/api-tests.php
docker compose up -d worker
docker compose logs -f worker
```

Приложение открывается по адресу `http://localhost:8088/`. Учебная авторизация для операций очереди: `alexey@example.test:lab11-demo`. Полные команды, результаты и ограничения находятся в [отчёте](../README.md) и [техническом описании](../docs/queue.md).
