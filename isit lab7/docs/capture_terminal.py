#!/usr/bin/env python3
"""Настоящие снимки XTerm: curl выполняется в терминале, захват экрана ImageMagick."""
import json
import os
from pathlib import Path
import shlex
import shutil
import socket
import subprocess
import tempfile
import time

lab=Path(__file__).resolve().parents[1]
images=lab/'images';images.mkdir(exist_ok=True)
php=os.environ.get('PHP_BIN','php')
xvfb=os.environ.get('XVFB_BIN','Xvfb')
xterm=os.environ.get('XTERM_BIN','xterm')
display=os.environ.get('CAPTURE_DISPLAY',':97')
env=dict(os.environ,DISPLAY=display,LC_ALL='C.UTF-8')
with tempfile.TemporaryDirectory(prefix='lab7-terminal-') as tmp:
    tmp=Path(tmp);copied=tmp/'src';shutil.copytree(lab/'src',copied)
    env['XDG_CACHE_HOME'] = str(tmp / 'cache')
    (tmp / 'cache').mkdir()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    base=f'http://127.0.0.1:{port}'
    xlog=(tmp/'display.log').open('w')
    xv=subprocess.Popen([xvfb,display,'-screen','0','1400x650x24','-ac','-nolisten','tcp','-noreset'],env=env,stdout=xlog,stderr=xlog)
    plog=(lab/'docs/terminal-server.log').open('w')
    server=subprocess.Popen([php,'-S',f'127.0.0.1:{port}','-t',str(copied/'public')],stdout=plog,stderr=plog)
    try:
        for _ in range(100):
            if xv.poll() is not None:raise RuntimeError('Xvfb завершился: см. terminal-display.log')
            with socket.socket() as sock:
                ready=sock.connect_ex(('127.0.0.1',port))==0
            if ready and Path('/tmp/.X11-unix/X'+display.lstrip(':')).exists():break
            time.sleep(.05)
        else:raise RuntimeError('Сервер или Xvfb не запустился')
        scenarios=[
            ('01-get','GET','/api/tickets/3',None,200),
            ('02-post','POST','/api/tickets',{'title':'Проверка заявки из терминала','category_id':1,'description':'Не работает оборудование.'},201),
            ('03-patch','PATCH','/api/tickets/7',{'status':'done'},200),
            ('04-delete','DELETE','/api/tickets/7',None,204),
            ('05-error-400','POST','/api/tickets','абс',400),
            ('06-error-404','GET','/api/tickets/99999',None,404),
            ('07-error-405','DELETE','/api/categories',None,405),
            ('08-error-422','POST','/api/tickets',{'title':'Да','category_id':1,'description':'Неверное название.'},422),
            ('09-error-409','DELETE','/api/categories/1',None,409),
        ]
        for name,method,path,data,expected in scenarios:
            ready_file=tmp/(name+'.ready');transcript=lab/'docs'/(name+'.txt')
            command=['curl','--noproxy','*','-sS','-i','-X',method,base+path]
            if data is not None:
                command+=['-H','Content-Type: application/json','--data-raw',data if isinstance(data,str) else json.dumps(data,ensure_ascii=False)]
            pretty=shlex.join(command)
            script=tmp/(name+'.sh')
            # В tee попадают именно stdout/stderr curl, а не заранее заготовленный ответ.
            script.write_text('#!/bin/bash\nset -euo pipefail\n'+
                'printf "%s\\n\\n" '+shlex.quote('ЛР7 · REST API · '+method+' '+path)+'\n'+
                'printf "$ %s\\n" '+shlex.quote(pretty)+'\n'+
                pretty+' 2>&1 | tee '+shlex.quote(str(transcript))+'\n'+
                'printf "\\n\\nЗапрос завершён.\\n"\n'+
                'touch '+shlex.quote(str(ready_file))+'\n')
            terminal=subprocess.Popen([xterm,'-hold','-geometry','136x31+0+0','-fa','DejaVu Sans Mono','-fs','12',
                '-bg','#101827','-fg','#e6edf3','-title','LR7 curl HTTP test','-e','bash',str(script)],env=env,stdout=xlog,stderr=xlog)
            try:
                for _ in range(200):
                    if ready_file.exists():break
                    if terminal.poll() is not None:raise RuntimeError('XTerm завершился до выполнения curl')
                    time.sleep(.05)
                else:raise RuntimeError('Истекло время выполнения curl в XTerm')
                text=transcript.read_text();assert f' {expected} ' in text,text
                time.sleep(.2)
                subprocess.run(['import','-display',display,'-window','root',str(images/(name+'.png'))],check=True,env=env)
            finally:
                terminal.terminate();terminal.wait(timeout=5)
            print(name,expected,flush=True)
        # Созданная тестовая запись удалена; исходный набор данных сохранён.
        assert len(json.loads((copied/'storage/tickets.json').read_text()))==6
        (lab/'docs/screenshots.md').write_text('# Скриншоты терминала\n\n'
            'Все PNG получены захватом экрана настоящего XTerm в Xvfb. В каждом терминале выполнялся curl '
            'на отдельном встроенном сервере PHP с временной копией src. Ответы не подменялись, '
            'исходные JSON не изменялись. Текст каждого ответа сохранён рядом в docs.\n\n'+
            '| Метод и адрес | Код | Скриншот | Ответ curl |\n| --- | --- | --- | --- |\n'+
            ''.join(f'| `{method} {path}` | {expected} | [PNG](../images/{name}.png) | [TXT]({name}.txt) |\n' for name,method,path,data,expected in scenarios)+
            '\nВидны метод, URL, тело запроса (где оно есть), заголовки, тело ответа и HTTP-код. '
            'Для 204 тело ответа отсутствует. Postman не использовался: методичка допускает curl.\n')
    finally:
        server.terminate();server.wait(timeout=5)
        xv.terminate();xv.wait(timeout=5)
        xlog.close();plog.close()
