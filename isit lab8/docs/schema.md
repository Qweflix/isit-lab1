# Таблицы, поля и ограничения

Описание получено из information_schema и pg_constraint работающей PostgreSQL 17.11 после создания схемы. Источник — [final/schema.json](final/schema.json).

## staff — Сотрудники поддержки

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| id | integer | нет | `nextval('staff_id_seq'::regclass)` |
| name | character varying(100) | нет | `—` |
| email | character varying(255) | нет | `—` |
| role | character varying(100) | нет | `'Инженер поддержки'::character varying` |
| created_at | timestamp with time zone | нет | `CURRENT_TIMESTAMP` |

Ограничения таблицы:

- `staff_email_check`: `CHECK (((email)::text ~* '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$'::text))`.
- `staff_email_key`: `UNIQUE (email)`.
- `staff_name_check`: `CHECK ((char_length(btrim((name)::text)) >= 3))`.
- `staff_pkey`: `PRIMARY KEY (id)`.
- `staff_role_check`: `CHECK ((char_length(btrim((role)::text)) > 0))`.

## staff_profiles — Профили сотрудников

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| staff_id | integer | нет | `—` |
| phone | character varying(30) | да | `—` |
| telegram | character varying(50) | да | `—` |

Ограничения таблицы:

- `staff_profiles_pkey`: `PRIMARY KEY (staff_id)`.
- `staff_profiles_staff_id_fkey`: `FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE`.
- `staff_profiles_telegram_key`: `UNIQUE (telegram)`.

## categories — Категории обращений

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| id | integer | нет | `nextval('categories_id_seq'::regclass)` |
| title | character varying(150) | нет | `—` |
| description | text | да | `—` |
| owner_id | integer | нет | `—` |
| created_at | timestamp with time zone | нет | `CURRENT_TIMESTAMP` |

Ограничения таблицы:

- `categories_owner_id_fkey`: `FOREIGN KEY (owner_id) REFERENCES staff(id) ON DELETE RESTRICT`.
- `categories_pkey`: `PRIMARY KEY (id)`.
- `categories_title_check`: `CHECK ((char_length(btrim((title)::text)) >= 3))`.
- `categories_title_key`: `UNIQUE (title)`.

## tickets — Заявки

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| id | integer | нет | `nextval('tickets_id_seq'::regclass)` |
| category_id | integer | нет | `—` |
| assignee_id | integer | да | `—` |
| title | character varying(200) | нет | `—` |
| description | text | нет | `—` |
| status | character varying(20) | нет | `'new'::character varying` |
| priority | character varying(10) | нет | `'medium'::character varying` |
| due_date | date | да | `—` |
| created_at | timestamp with time zone | нет | `CURRENT_TIMESTAMP` |

Ограничения таблицы:

- `tickets_assignee_id_fkey`: `FOREIGN KEY (assignee_id) REFERENCES staff(id) ON DELETE SET NULL`.
- `tickets_category_id_fkey`: `FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT`.
- `tickets_description_check`: `CHECK ((char_length(btrim(description)) > 0))`.
- `tickets_pkey`: `PRIMARY KEY (id)`.
- `tickets_priority_check`: `CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'critical'::character varying])::text[])))`.
- `tickets_status_check`: `CHECK (((status)::text = ANY ((ARRAY['new'::character varying, 'in_progress'::character varying, 'done'::character varying])::text[])))`.
- `tickets_title_check`: `CHECK ((char_length(btrim((title)::text)) >= 3))`.

## comments — Комментарии

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| id | integer | нет | `nextval('comments_id_seq'::regclass)` |
| ticket_id | integer | нет | `—` |
| author_id | integer | нет | `—` |
| body | text | нет | `—` |
| created_at | timestamp with time zone | нет | `CURRENT_TIMESTAMP` |

Ограничения таблицы:

- `comments_author_id_fkey`: `FOREIGN KEY (author_id) REFERENCES staff(id) ON DELETE RESTRICT`.
- `comments_body_check`: `CHECK ((char_length(btrim(body)) > 0))`.
- `comments_pkey`: `PRIMARY KEY (id)`.
- `comments_ticket_id_fkey`: `FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE`.

## tags — Теги

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| id | integer | нет | `nextval('tags_id_seq'::regclass)` |
| name | character varying(50) | нет | `—` |

Ограничения таблицы:

- `tags_name_check`: `CHECK ((char_length(btrim((name)::text)) > 0))`.
- `tags_name_key`: `UNIQUE (name)`.
- `tags_pkey`: `PRIMARY KEY (id)`.

## ticket_tags — Связующая таблица заявок и тегов

| Поле | Тип | NULL | Значение по умолчанию |
| --- | --- | --- | --- |
| ticket_id | integer | нет | `—` |
| tag_id | integer | нет | `—` |

Ограничения таблицы:

- `ticket_tags_pkey`: `PRIMARY KEY (ticket_id, tag_id)`.
- `ticket_tags_tag_id_fkey`: `FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE`.
- `ticket_tags_ticket_id_fkey`: `FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE`.

## Обоснование нормализации

Имена и email сотрудников хранятся только в staff; в заявке и комментарии находятся их ID. Название категории хранится только в categories, теги — отдельно в tags. Одна пара ticket_id/tag_id хранится один раз благодаря составному PK, списков тегов через запятую нет. Необязательный профиль вынесен в отдельную таблицу с общим первичным ключом staff_id. Все неключевые поля описывают сущность своей таблицы.
