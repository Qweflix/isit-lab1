#!/usr/bin/env python3
"""Проверить запуск API из отдельного чистого клона isit-7."""
import argparse
from datetime import datetime,timezone
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import time

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--repository',default='https://github.com/Qweflix/isit-lab1.git')
args=parser.parse_args()
lab=Path(__file__).resolve().parents[1]
php=os.environ.get('PHP_BIN','php')
with tempfile.TemporaryDirectory(prefix='lab7-clean-clone-') as tmp:
    copied=Path(tmp)/'repository'
    command=['git','clone','--no-local','--single-branch','--branch','isit-7',args.repository,str(copied)]
    subprocess.run(command,check=True,capture_output=True,text=True)
    commit=subprocess.check_output(['git','-C',str(copied),'rev-parse','HEAD'],text=True).strip()
    assert not subprocess.check_output(['git','-C',str(copied),'status','--porcelain'])
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    root=copied/'isit lab7/src'
    records=[]
    with (lab/'docs/clean-clone-server.log').open('w') as log:
        server=subprocess.Popen([php,'-S',f'127.0.0.1:{port}','-t',str(root/'public')],stdout=log,stderr=log)
        try:
            for _ in range(80):
                with socket.socket() as sock:
                    if sock.connect_ex(('127.0.0.1',port))==0:break
                time.sleep(.05)
            def call(method,path,expected,data=None):
                body=Path(tmp)/'body'
                cmd=['curl','--noproxy','*','-sS','--max-time','10','-X',method,f'http://127.0.0.1:{port}'+path,'-o',str(body),'-w','%{http_code}']
                if data is not None:cmd+=['-H','Content-Type: application/json','--data',json.dumps(data,ensure_ascii=False)]
                run=subprocess.run(cmd,capture_output=True,text=True,check=True)
                assert int(run.stdout)==expected,(method,path,run.stdout,body.read_text())
                records.append(f'| {method} `{path}` | {expected} | {run.stdout} |')
                if path=='/':assert '<!doctype html>' in body.read_text()
                elif expected==204:assert body.read_bytes()==b''
                else:return json.loads(body.read_text())
            call('GET','/',200)
            assert len(call('GET','/api/tickets',200))==6
            assert len(call('GET','/api/categories',200))==3
            assert len(call('GET','/api/staff',200))==3
            created=call('POST','/api/staff',201,{'name':'Анна Иванова','email':'anna@example.test'})
            assert created['id']==4
            call('GET','/api/staff/4',200)
            updated=call('PATCH','/api/staff/4',200,{'role':'Старший инженер'})
            assert updated['role']=='Старший инженер'
            call('DELETE','/api/staff/4',204)
            call('GET','/api/staff/4',404)
        finally:
            server.terminate();server.wait(timeout=5)
    text=f'''# Запуск из чистого клона

Дата UTC: {datetime.now(timezone.utc).isoformat(timespec='seconds')}.

Источник клонирования: `{args.repository}`. Ветка `isit-7`, коммит `{commit}`.

Выполнен `git clone --no-local --single-branch --branch isit-7`: приложение проверено в отдельном клоне Git-объектов, без копирования незакоммиченных файлов рабочей папки. Запущен встроенный сервер PHP с корнем `isit lab7/src/public`; JSON и исходные файлы берутся только из этого клона. Временная копия удалена после проверки.

| Запрос | Ожидаемый код | Фактический код |
| --- | --- | --- |
'''+ '\n'.join(records)+'''

Начальные данные — 6/3/3 записей. Создание, чтение, изменение и удаление сотрудника выполнены настоящими запросами curl; корректность JSON и пустое тело 204 проверены. Для запуска использован доступный PHP 8.4.24 с json и mbstring; файлы приложения не зависят от путей `/tmp` или локальных модулей Python.

[Журнал запуска](clean-clone-server.log). Для повторения: `python3 docs/check_clean_clone.py` из каталога ЛР7 (нужны Git, curl, Python 3 и PHP 8.2+).
'''
    (lab/'docs/clean-clone.md').write_text(text)
    print(text)
