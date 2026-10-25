#!/usr/bin/env python3
"""Настоящие снимки XTerm с psql. Использовать отдельную учебную БД: таблицы заменяются."""
import argparse
import os
from pathlib import Path
import shlex
import subprocess
import tempfile
import time

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--database',required=True)
parser.add_argument('--only',help='Повторить только указанный снимок, например 02-integrity')
args=parser.parse_args()
lab=Path(__file__).resolve().parents[1]
images=lab/'images';images.mkdir(exist_ok=True)
evidence=lab/'docs/screenshots';evidence.mkdir(exist_ok=True)
psql=os.environ.get('PSQL_BIN','psql')
cmd=[psql,'-X','-P','pager=off','-v','ON_ERROR_STOP=1','-d',args.database]
xvfb=os.environ.get('XVFB_BIN','Xvfb');xterm=os.environ.get('XTERM_BIN','xterm')
display=os.environ.get('CAPTURE_DISPLAY',':96')
scenarios=[
 ('01-structure','Структура таблиц и внешние ключи',r'''\dt
SELECT conrelid::regclass AS child, pg_get_constraintdef(oid) AS foreign_key
FROM pg_constraint WHERE contype = 'f' AND connamespace = 'public'::regnamespace
ORDER BY conrelid::regclass::text, conname;
'''),
 ('02-integrity','Проверка ограничений: ожидаемые SQLSTATE',None),
 ('03-crud','CRUD заявки: INSERT / SELECT / UPDATE / DELETE',r'''BEGIN;
INSERT INTO tickets(category_id, title, description)
VALUES (1, 'Проверка CRUD в терминале', 'Оборудование не работает.') RETURNING id AS new_id \gset
SELECT id, title, status FROM tickets WHERE id = :new_id;
UPDATE tickets SET status = 'done' WHERE id = :new_id RETURNING id, status;
DELETE FROM tickets WHERE id = :new_id RETURNING id;
ROLLBACK;
'''),
 ('04-joins-report','JOIN, LEFT JOIN, N:M и отчёт руководителя',r'''SELECT t.id, c.title AS category, s.name AS assignee
FROM tickets t JOIN categories c ON c.id = t.category_id
LEFT JOIN staff s ON s.id = t.assignee_id ORDER BY t.id;
SELECT t.id, t.title, g.name AS tag FROM tickets t
JOIN ticket_tags tt ON tt.ticket_id = t.id JOIN tags g ON g.id = tt.tag_id
WHERE g.name = 'срочно' ORDER BY t.id;
SELECT category_id, total, new_count, in_progress_count, resolved_count
FROM supervisor_report ORDER BY category_id;
'''),
 ('05-index-category','EXPLAIN ANALYZE: фильтр по категории, до и после',r'''DROP INDEX IF EXISTS idx_tickets_category_id;
ANALYZE tickets;
\echo ДО ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tickets WHERE category_id = 1500;
CREATE INDEX idx_tickets_category_id ON tickets(category_id);
ANALYZE tickets;
\echo ПОСЛЕ ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title FROM tickets WHERE category_id = 1500;
'''),
 ('06-index-status-due','EXPLAIN ANALYZE: статус, сортировка и LIMIT',r'''DROP INDEX IF EXISTS idx_tickets_status_due;
ANALYZE tickets;
\echo ДО ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tickets
WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
CREATE INDEX idx_tickets_status_due ON tickets(status, due_date, id);
ANALYZE tickets;
\echo ПОСЛЕ ИНДЕКСА
EXPLAIN (ANALYZE, BUFFERS) SELECT id, title, due_date FROM tickets
WHERE status = 'new' ORDER BY due_date, id LIMIT 20;
''')]
with tempfile.TemporaryDirectory(prefix='lab8-capture-') as temp:
    tmp=Path(temp);(tmp/'cache').mkdir()
    env=dict(os.environ,DISPLAY=display,LC_ALL='C.UTF-8',XDG_CACHE_HOME=str(tmp/'cache'))
    with (tmp/'xterm.log').open('w') as xlog:
        server=subprocess.Popen([xvfb,display,'-screen','0','1440x1200x24','-ac','-nolisten','tcp','-noreset'],env=env,stdout=xlog,stderr=xlog)
        def run_file(name):
            run=subprocess.run(cmd+['-f',str(lab/'sql'/name)],stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True)
            (evidence/('setup-'+name+'.log')).write_text(run.stdout)
            assert run.returncode==0,run.stdout
        try:
            for _ in range(100):
                if server.poll() is not None:raise RuntimeError('Не запустился Xvfb')
                if Path('/tmp/.X11-unix/X'+display.lstrip(':')).exists():break
                time.sleep(.05)
            run_file('01_schema.sql');run_file('02_seed.sql');run_file('07_report.sql')
            benchmark_loaded = False
            for i,(name,title,statement) in enumerate(scenarios):
                if args.only and args.only != name:
                    continue
                if i >= 4 and not benchmark_loaded:
                    run_file('05_test_data.sql')
                    benchmark_loaded = True
                if statement is None:
                    command=cmd+['-qAt','-f',str(lab/'sql/03_integrity.sql')]
                else:
                    path=evidence/(name+'.sql');path.write_text(statement)
                    command=cmd+['-q','-f',str(path)]
                ready=tmp/(name+'.ready');output=evidence/(name+'.txt');script=tmp/(name+'.sh')
                pretty=shlex.join(command)
                script.write_text('set -euo pipefail\n'+
                    'printf "%s\\n\\n" '+shlex.quote('ЛР8 · PostgreSQL · '+title)+'\n'+
                    'printf "$ %s\\n\\n" '+shlex.quote(pretty)+'\n'+
                    pretty+' 2>&1 | tee '+shlex.quote(str(output))+'\n'+
                    'printf "\\nВыполнено успешно.\\n"\n'+
                    'touch '+shlex.quote(str(ready))+'\n')
                terminal=subprocess.Popen([xterm,'-hold','-geometry','140x56+0+0','-fa','DejaVu Sans Mono','-fs','12',
                    '-bg','#101827','-fg','#e6edf3','-e','bash',str(script)],env=env,stdout=xlog,stderr=xlog)
                try:
                    for _ in range(400):
                        if ready.exists():break
                        if terminal.poll() is not None:raise RuntimeError('XTerm завершился')
                        time.sleep(.05)
                    else:raise RuntimeError('Не завершён запрос psql: '+name)
                    text=output.read_text()
                    assert 'ERROR:' not in text,text
                    if i==1:assert text.count('PASS ')==13
                    if i>=4:assert 'Seq Scan' in text and 'Index' in text
                    time.sleep(.2)
                    subprocess.run(['import','-display',display,'-window','root',str(images/(name+'.png'))],env=env,check=True)
                finally:
                    terminal.terminate();terminal.wait(timeout=5)
                print(name+': screenshot OK',flush=True)
            run_file('01_schema.sql');run_file('02_seed.sql');run_file('07_report.sql')
        finally:
            server.terminate();server.wait(timeout=5)
(lab/'docs/screenshots.md').write_text('# Происхождение скриншотов\n\n'
    'Снимки получены захватом экрана настоящего XTerm в Xvfb. В терминале выполнялся psql '
    'на PostgreSQL 17.11. Данные и ответы не подменялись; отдельная БД для съёмки возвращена '
    'к исходным данным после демонстрации.\n\n'
    '| Проверка | Скриншот | Вывод psql |\n| --- | --- | --- |\n'+
    ''.join(f'| {title} | [PNG](../images/{name}.png) | [TXT](screenshots/{name}.txt) |\n' for name,title,_ in scenarios)+
    '\nПланы на снимках — отдельный запуск для демонстрации, поэтому времена могут отличаться '
    'от основной таблицы измерений из final/06_optimization.log. Перед сравнением использован '
    'нагрузочный набор из 200 000 заявок и 400 000 комментариев.\n')
