#!/usr/bin/env python3
"""Запустить учебные SQL-скрипты в выделенной БД. 01 и 05 заменяют её учебные данные."""
import argparse
from datetime import datetime,timezone
import json
import os
from pathlib import Path
import re
import subprocess

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--demo',action='store_true')
parser.add_argument('--database',required=True,help='Отдельная учебная БД; не используйте рабочую БД приложения')
args=parser.parse_args()
lab=Path(__file__).resolve().parents[1]
psql=os.environ.get('PSQL_BIN','psql')
cmd=[psql,'-X','-v','ON_ERROR_STOP=1','-P','pager=off','-d',args.database]
prefix='baseline' if args.demo else 'final'
main,parent,person,profile,link=('tasks','projects','users','user_profiles','task_tags') if args.demo else ('tickets','categories','staff','staff_profiles','ticket_tags')
seed_size=7 if args.demo else 6
stress_size=500000 if args.demo else 200000
logs=lab/'docs'/prefix;logs.mkdir(exist_ok=True)
checks=[]
def sql(statement):
 run=subprocess.run(cmd+['-At','-c',statement],capture_output=True,text=True)
 assert run.returncode==0,run.stdout+run.stderr
 return run.stdout.strip()
def file(name):
 run=subprocess.run(cmd+['-f',str(lab/'sql'/name)],stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True)
 (logs/(name.removesuffix('.sql')+'.log')).write_text(run.stdout)
 assert run.returncode==0,(name,run.stdout[-6000:])
 print(name+': OK',flush=True)
 return run.stdout
def count(table):return int(sql('SELECT count(*) FROM '+table))
def check(label,result):
 assert result,label
 checks.append(label)
def counts():return {table:count(table) for table in [person,profile,parent,main,'comments','tags',link]}
version=sql('SELECT version()')
check('PostgreSQL 16+',int(sql('SHOW server_version_num'))>=160000)
file('01_schema.sql');file('02_seed.sql')
check('7 таблиц',int(sql("SELECT count(*) FROM pg_tables WHERE schemaname='public'"))==7)
check('7 первичных ключей',int(sql("SELECT count(*) FROM pg_constraint c JOIN pg_namespace n ON n.oid=c.connamespace WHERE n.nspname='public' AND contype='p'"))==7)
check('8 внешних ключей',int(sql("SELECT count(*) FROM pg_constraint c JOIN pg_namespace n ON n.oid=c.connamespace WHERE n.nspname='public' AND contype='f'"))==8)
initial=counts()
check('Начальные данные',initial=={person:3,profile:3,parent:3,main:seed_size,'comments':5,'tags':4,link:5})
check('Есть необязательные NULL',count(main+' WHERE assignee_id IS NULL')>0 and count(main+' WHERE due_date IS NULL')>0)
# Подлинный вывод psql для структуры БД и подготовки к защите.
structure=subprocess.run(cmd+['-c','\\dt','-c','\\d '+main,'-c','\\d '+profile,'-c','\\d '+link],capture_output=True,text=True,check=True).stdout
(logs/'structure.txt').write_text(structure)
column_json=sql("SELECT json_agg(c ORDER BY table_name, ordinal_position) FROM (SELECT table_name, column_name, data_type, character_maximum_length, is_nullable, column_default, ordinal_position FROM information_schema.columns WHERE table_schema='public') c")
constraint_json=sql("SELECT json_agg(c ORDER BY table_name, constraint_name) FROM (SELECT c.conrelid::regclass::text AS table_name, c.conname AS constraint_name, c.contype, pg_get_constraintdef(c.oid) AS definition FROM pg_constraint c JOIN pg_namespace n ON n.oid=c.connamespace WHERE n.nspname='public') c")
(logs/'schema.json').write_text(json.dumps({'columns':json.loads(column_json),'constraints':json.loads(constraint_json)},ensure_ascii=False,indent=2)+'\n')
integrity=file('03_integrity.sql')
check('13 проверок ограничений и ON DELETE',integrity.count('PASS ')==13)
check('После проверок данные не изменены',counts()==initial)
file('04_crud.sql')
check('CRUD выполнен и изменения откатились',counts()==initial)
file('05_test_data.sql')
check('Нагрузочный объём основной таблицы',count(main)==stress_size)
check('Два комментария на запись',count('comments')==stress_size*2)
plan=file('06_optimization.sql')
labels={'Q1':('Фильтр по категории' if not args.demo else 'Фильтр по проекту',f'idx_{main}_{"project_id" if args.demo else "category_id"}'),
        'Q2':('Исполнитель и статус',f'idx_{main}_assignee_status'),
        'Q3':('Комментарии записи по времени',f'idx_comments_{"task" if args.demo else "ticket"}_created'),
        'Q4':('Статус и ближайшие сроки',f'idx_{main}_status_due'),
        'N1':('Подстрока ILIKE','B-tree не поддерживает ведущий %'),
        'N2':('Выборка всех статусов','Читается вся таблица')}
measurements=[]
for label,(description,index) in labels.items():
 row={'label':label,'query':description,'index':index}
 for phase in ['BEFORE','AFTER']:
  block=re.search(r'(?ms)^=== '+label+' '+phase+r' ===\s*\n(.*?)(?=^=== |\Z)',plan)
  assert block,(label,phase)
  content=block[1]
  timing=re.search(r'Execution Time: ([0-9.]+) ms',content)
  assert timing,(label,phase,content)
  row[phase.lower()+'_ms']=float(timing[1])
  operators=list(dict.fromkeys(re.findall(r'Parallel Seq Scan|Bitmap Heap Scan|Bitmap Index Scan|Index Only Scan|Index Scan|Seq Scan|Gather Merge|Sort',content)))
  row[phase.lower()+'_plan']=' → '.join(operators)
  # Вырезается полный фактический план до Execution Time, без последующих команд.
  excerpt=content[:timing.end()]+'\n'
  (logs/(label.lower()+'-'+phase.lower()+'.txt')).write_text(excerpt)
 if label.startswith('Q'):
  check(label+': после используется индекс','Index' in row['after_plan'])
 else:
  check(label+': оба плана с последовательным чтением','Seq Scan' in row['before_plan'] and 'Seq Scan' in row['after_plan'])
 row['speedup']=row['before_ms']/row['after_ms'] if row['after_ms'] else None
 measurements.append(row)
check('EXPLAIN не изменил нагрузочные данные',count(main)==stress_size and count('comments')==stress_size*2)
(logs/'optimization.json').write_text(json.dumps(measurements,ensure_ascii=False,indent=2)+'\n')
# Возвращаем учебный набор: результаты измерений уже сохранены отдельно.
file('01_schema.sql');file('02_seed.sql')
check('Исходные данные восстановлены после нагрузки',counts()==initial)
if not args.demo and (lab/'sql/07_report.sql').exists():
 file('07_report.sql')
 check('Представление отчёта по трём категориям',int(sql('SELECT count(*) FROM supervisor_report'))==3 and int(sql('SELECT sum(total) FROM supervisor_report'))==seed_size)
report='# '+('Проверка примера до переноса' if args.demo else 'Проверка базы системы учёта заявок')+'\n\n'
report+=f'Дата UTC: {datetime.now(timezone.utc).isoformat(timespec="seconds")}.\n\n{version}\n\n'
report+=f'Все SQL-файлы 01–06 выполнены по порядку с `ON_ERROR_STOP=1`. Основная таблица: {seed_size} начальных записей; нагрузка: {stress_size:,} записей и {stress_size*2:,} комментариев. После измерений исходный набор восстановлен.\n\n'
report+='| Проверка | Результат |\n| --- | --- |\n'+''.join(f'| {label} | Пройдено |\n' for label in checks)
report+='\nОжидаемые нарушения перехватываются только при правильном SQLSTATE; любая другая ошибка останавливает выполнение. Проверены FK, CHECK, NOT NULL, UNIQUE, 1:1, N:M, RESTRICT, CASCADE, SET NULL. SERIAL может увеличиваться после отката — это штатное поведение PostgreSQL.\n\n'
report+='## Реальные результаты EXPLAIN ANALYZE\n\n'
report+='| Запрос | Индекс / причина | План до | План после | До, мс | После, мс | До / после |\n| --- | --- | --- | --- | --- | --- | --- |\n'
for row in measurements:
 report+=f"| {row['label']}: {row['query']} | `{row['index']}` | {row['before_plan']} | {row['after_plan']} | {row['before_ms']:.3f} | {row['after_ms']:.3f} | {row['speedup']:.2f}× |\n"
report+='\nЭто один фактический запуск каждого запроса до и после; время зависит от оборудования, кэша и нагрузки. Методы планировщика не отключались; после генерации данных и индексов выполнен ANALYZE. Для N1/N2 изменение времени без смены Seq Scan не считается доказательством пользы индекса.\n\n'
report+=f'Полные журналы, отдельные планы и метаданные схемы находятся в [{prefix}/]({prefix}). PostgreSQL запущен локально; Docker Compose описывает альтернативный запуск PostgreSQL 16. PHP к БД не подключается.\n'
(lab/'docs'/('baseline-testing.md' if args.demo else 'testing.md')).write_text(report)
print(report,flush=True)
