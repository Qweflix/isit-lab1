-- Каждая ошибка должна иметь ожидаемый SQLSTATE; неожиданная ошибка останавливает psql.
CREATE OR REPLACE FUNCTION pg_temp.expect_error(label TEXT, expected_state TEXT, statement TEXT, expected_constraint TEXT DEFAULT NULL)
RETURNS VOID LANGUAGE plpgsql AS $$
DECLARE actual_constraint TEXT;
BEGIN
    BEGIN
        EXECUTE statement;
    EXCEPTION WHEN OTHERS THEN
        GET STACKED DIAGNOSTICS actual_constraint = CONSTRAINT_NAME;
        IF SQLSTATE = expected_state AND (expected_constraint IS NULL OR actual_constraint = expected_constraint) THEN
            RAISE NOTICE 'PASS %: SQLSTATE %, ограничение %, %', label, SQLSTATE, actual_constraint, SQLERRM;
            RETURN;
        END IF;
        RAISE;
    END;
    RAISE EXCEPTION 'FAIL %: ожидалась ошибка %, но запрос выполнен', label, expected_state;
END;
$$;
SELECT pg_temp.expect_error('FK отсутствующая связанная запись', '23503',
    $q$INSERT INTO tickets(category_id, title , description) VALUES (999999, 'Без связи', 'Описание проверки')$q$, 'tickets_category_id_fkey');
SELECT pg_temp.expect_error('CHECK неизвестный статус', '23514',
    $q$INSERT INTO tickets(category_id, title, status , description) VALUES (1, 'Проверка статуса', 'unknown', 'Описание проверки')$q$, 'tickets_status_check');
SELECT pg_temp.expect_error('NOT NULL название', '23502',
    $q$INSERT INTO tickets(category_id, title , description) VALUES (1, NULL, 'Описание проверки')$q$);
SELECT pg_temp.expect_error('CHECK короткое название', '23514',
    $q$INSERT INTO tickets(category_id, title , description) VALUES (1, 'Да', 'Описание проверки')$q$, 'tickets_title_check');
SELECT pg_temp.expect_error('UNIQUE email', '23505',
    $q$INSERT INTO staff(name, email) SELECT 'Дубликат сотрудника', email FROM staff WHERE id = 1$q$, 'staff_email_key');
SELECT pg_temp.expect_error('1:1 повторный профиль', '23505',
    $q$INSERT INTO staff_profiles(staff_id, phone) VALUES (1, 'duplicate')$q$, 'staff_profiles_pkey');
SELECT pg_temp.expect_error('N:M повторная пара', '23505',
    $q$INSERT INTO ticket_tags(ticket_id, tag_id) VALUES (1, 1)$q$, 'ticket_tags_pkey');
SELECT pg_temp.expect_error('UNIQUE имя тега', '23505',
    $q$INSERT INTO tags(name) SELECT name FROM tags WHERE id = 1$q$, 'tags_name_key');
SELECT pg_temp.expect_error('RESTRICT связанная запись с обращениями', '23503',
    $q$DELETE FROM categories WHERE id = 1$q$, 'tickets_category_id_fkey');
SELECT pg_temp.expect_error('RESTRICT автор существующего комментария', '23503',
    $q$DO $test$
    DECLARE person_id INTEGER;
    BEGIN
        INSERT INTO staff(name, email) VALUES ('Автор для проверки', 'author-check@example.test')
            RETURNING id INTO person_id;
        INSERT INTO comments(ticket_id, author_id, body) VALUES (1, person_id, 'Проверка запрета удаления автора');
        DELETE FROM staff WHERE id = person_id;
    END;
    $test$;$q$, 'comments_author_id_fkey');

BEGIN;
DO $$
DECLARE new_id INTEGER;
BEGIN
    INSERT INTO tickets(category_id, title , description) VALUES (1, 'Проверка каскадного удаления', 'Описание проверки')
        RETURNING id INTO new_id;
    INSERT INTO comments(ticket_id, author_id, body) VALUES (new_id, 1, 'Каскадный комментарий');
    INSERT INTO ticket_tags(ticket_id, tag_id) VALUES (new_id, 1);
    DELETE FROM tickets WHERE id = new_id;
    IF EXISTS (SELECT 1 FROM comments WHERE ticket_id = new_id)
       OR EXISTS (SELECT 1 FROM ticket_tags WHERE ticket_id = new_id) THEN
        RAISE EXCEPTION 'FAIL CASCADE';
    END IF;
    RAISE NOTICE 'PASS CASCADE: комментарии и связи удалены вместе с основной записью';
END;
$$;
ROLLBACK;

BEGIN;
DO $$
DECLARE person_id INTEGER; record_id INTEGER;
BEGIN
    INSERT INTO staff(name, email) VALUES ('Временный сотрудник', 'temporary@example.test')
        RETURNING id INTO person_id;
    INSERT INTO staff_profiles(staff_id, phone) VALUES (person_id, 'test');
    INSERT INTO tickets(category_id, assignee_id, title , description)
        VALUES (1, person_id, 'Проверка SET NULL', 'Описание проверки') RETURNING id INTO record_id;
    DELETE FROM staff WHERE id = person_id;
    IF NOT EXISTS (SELECT 1 FROM tickets WHERE id = record_id AND assignee_id IS NULL)
       OR EXISTS (SELECT 1 FROM staff_profiles WHERE staff_id = person_id) THEN
        RAISE EXCEPTION 'FAIL SET NULL или CASCADE профиля';
    END IF;
    RAISE NOTICE 'PASS SET NULL: основная запись сохранена без исполнителя; профиль удалён CASCADE';
END;
$$;
ROLLBACK;

BEGIN;
DO $$
BEGIN
    DELETE FROM tags WHERE id = 1;
    IF EXISTS (SELECT 1 FROM ticket_tags WHERE tag_id = 1)
       OR NOT EXISTS (SELECT 1 FROM tickets WHERE id = 1) THEN
        RAISE EXCEPTION 'FAIL CASCADE связи N:M';
    END IF;
    RAISE NOTICE 'PASS CASCADE: удаление тега удаляет только связи, основная запись сохранена';
END;
$$;
ROLLBACK;
