-- Каждая ошибка должна иметь ожидаемый SQLSTATE; неожиданная ошибка останавливает psql.
CREATE OR REPLACE FUNCTION pg_temp.expect_error(label TEXT, expected_state TEXT, statement TEXT)
RETURNS VOID LANGUAGE plpgsql AS $$
BEGIN
    BEGIN
        EXECUTE statement;
    EXCEPTION WHEN OTHERS THEN
        IF SQLSTATE = expected_state THEN
            RAISE NOTICE 'PASS %: SQLSTATE %, %', label, SQLSTATE, SQLERRM;
            RETURN;
        END IF;
        RAISE;
    END;
    RAISE EXCEPTION 'FAIL %: ожидалась ошибка %, но запрос выполнен', label, expected_state;
END;
$$;
SELECT pg_temp.expect_error('FK отсутствующая связанная запись', '23503',
    $q$INSERT INTO tasks(project_id, title ) VALUES (999999, 'Без связи')$q$);
SELECT pg_temp.expect_error('CHECK неизвестный статус', '23514',
    $q$INSERT INTO tasks(project_id, title, status ) VALUES (1, 'Проверка статуса', 'unknown')$q$);
SELECT pg_temp.expect_error('NOT NULL название', '23502',
    $q$INSERT INTO tasks(project_id, title ) VALUES (1, NULL)$q$);
SELECT pg_temp.expect_error('CHECK короткое название', '23514',
    $q$INSERT INTO tasks(project_id, title ) VALUES (1, 'Да')$q$);
SELECT pg_temp.expect_error('UNIQUE email', '23505',
    $q$INSERT INTO users(name, email) SELECT 'Дубликат сотрудника', email FROM users WHERE id = 1$q$);
SELECT pg_temp.expect_error('1:1 повторный профиль', '23505',
    $q$INSERT INTO user_profiles(user_id, phone) VALUES (1, 'duplicate')$q$);
SELECT pg_temp.expect_error('N:M повторная пара', '23505',
    $q$INSERT INTO task_tags(task_id, tag_id) VALUES (1, 1)$q$);
SELECT pg_temp.expect_error('UNIQUE имя тега', '23505',
    $q$INSERT INTO tags(name) SELECT name FROM tags WHERE id = 1$q$);
SELECT pg_temp.expect_error('RESTRICT связанная запись с обращениями', '23503',
    $q$DELETE FROM projects WHERE id = 1$q$);
SELECT pg_temp.expect_error('RESTRICT автор существующего комментария', '23503',
    $q$DELETE FROM users WHERE id = 3$q$);

BEGIN;
DO $$
DECLARE new_id INTEGER;
BEGIN
    INSERT INTO tasks(project_id, title ) VALUES (1, 'Проверка каскадного удаления')
        RETURNING id INTO new_id;
    INSERT INTO comments(task_id, author_id, body) VALUES (new_id, 1, 'Каскадный комментарий');
    INSERT INTO task_tags(task_id, tag_id) VALUES (new_id, 1);
    DELETE FROM tasks WHERE id = new_id;
    IF EXISTS (SELECT 1 FROM comments WHERE task_id = new_id)
       OR EXISTS (SELECT 1 FROM task_tags WHERE task_id = new_id) THEN
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
    INSERT INTO users(name, email) VALUES ('Временный сотрудник', 'temporary@example.test')
        RETURNING id INTO person_id;
    INSERT INTO user_profiles(user_id, phone) VALUES (person_id, 'test');
    INSERT INTO tasks(project_id, assignee_id, title )
        VALUES (1, person_id, 'Проверка SET NULL') RETURNING id INTO record_id;
    DELETE FROM users WHERE id = person_id;
    IF NOT EXISTS (SELECT 1 FROM tasks WHERE id = record_id AND assignee_id IS NULL)
       OR EXISTS (SELECT 1 FROM user_profiles WHERE user_id = person_id) THEN
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
    IF EXISTS (SELECT 1 FROM task_tags WHERE tag_id = 1)
       OR NOT EXISTS (SELECT 1 FROM tasks WHERE id = 1) THEN
        RAISE EXCEPTION 'FAIL CASCADE связи N:M';
    END IF;
    RAISE NOTICE 'PASS CASCADE: удаление тега удаляет только связи, основная запись сохранена';
END;
$$;
ROLLBACK;
