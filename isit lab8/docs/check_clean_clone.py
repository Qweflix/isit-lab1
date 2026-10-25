#!/usr/bin/env python3
"""Проверить все SQL из чистого клона GitHub в новой временной PostgreSQL-базе."""
import argparse
from datetime import datetime,timezone
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import uuid

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--repository',default='https://github.com/Qweflix/isit-lab1.git')
args=parser.parse_args()
lab=Path(__file__).resolve().parents[1]
createdb=os.environ.get('CREATEDB_BIN','createdb')
dropdb=os.environ.get('DROPDB_BIN','dropdb')
name='lab8_clone_'+uuid.uuid4().hex[:12]
with tempfile.TemporaryDirectory(prefix='lab8-clone-') as tmp:
    repo=Path(tmp)/'repository'
    subprocess.run(['git','clone','--no-local','--single-branch','--branch','isit-8',args.repository,str(repo)],check=True,capture_output=True,text=True)
    commit=subprocess.check_output(['git','-C',str(repo),'rev-parse','HEAD'],text=True).strip()
    assert not subprocess.check_output(['git','-C',str(repo),'status','--porcelain'])
    subprocess.run([createdb,name],check=True,capture_output=True,text=True)
    try:
        run=subprocess.run([sys.executable,str(repo/'isit lab8/docs/check_database.py'),'--database',name],stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True)
        (lab/'docs/clean-clone.log').write_text(run.stdout)
        assert run.returncode==0,run.stdout
        assert '07_report.sql: OK' in run.stdout
        assert '13 проверок ограничений и ON DELETE' in run.stdout
    finally:
        subprocess.run([dropdb,name],check=True,capture_output=True,text=True)
    (lab/'docs/clean-clone.md').write_text(f'''# Проверка чистого клона

Дата UTC: {datetime.now(timezone.utc).isoformat(timespec='seconds')}.

Источник: `{args.repository}`, ветка `isit-8`, коммит `{commit}`.

Создан отдельный чистый клон Git-объектов и новая временная база PostgreSQL. В ней запущен check_database.py именно из клона: выполнены все SQL 01–06, проверены ключи, ограничения, CRUD, 200 000 заявок и 400 000 комментариев, четыре пары планов и два контрпримера. Затем восстановлены начальные данные и выполнен 07_report.sql. Все проверки пройдены.

Незакоммиченные файлы исходной рабочей папки не копировались. После проверки временная база удалена, основная учебная supportdb не изменялась. Время повторного запуска может отличаться от основной таблицы; его вывод сохранён отдельно.

[Полный вывод проверки](clean-clone.log). Для повторения: python3 docs/check_clean_clone.py, PostgreSQL 16+ с правом создавать учебную БД, psql/createdb/dropdb, Git и Python 3. Параметры подключения задаются обычными PGHOST/PGPORT/PGUSER.
''')
    print('Чистый клон '+commit+': все SQL 01–07 и проверки пройдены.')
