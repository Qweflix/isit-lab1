#!/usr/bin/env python3
"""Скриншоты настоящего PHP-приложения, HAR и вывод curl. Нужны Playwright и Chromium."""
import json
import os
from pathlib import Path
import shlex
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
from playwright.sync_api import sync_playwright

lab = Path(__file__).resolve().parents[1]
images = lab / 'images'
images.mkdir(exist_ok=True)
php = os.environ.get('PHP_BIN', 'php')
with tempfile.TemporaryDirectory(prefix='lab6-browser-') as tmp:
    copied = Path(tmp) / 'src'
    shutil.copytree(lab / 'src', copied)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}'
    log_path = lab / 'docs/browser-server.log'
    with log_path.open('w') as log:
        server = subprocess.Popen([php, '-d','display_errors=1','-d','error_reporting=32767',
            '-S', f'127.0.0.1:{port}', '-t',str(copied/'public')],stdout=log,stderr=log)
        try:
            opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
            for _ in range(80):
                try:
                    opener.open(base,timeout=1).close()
                    break
                except (urllib.error.URLError,ConnectionError):time.sleep(.05)
            else:raise RuntimeError('PHP не запустился')
            with sync_playwright() as p:
                browser = p.chromium.launch(executable_path=os.environ.get('CHROMIUM_BIN','/usr/bin/chromium'),
                    headless=True,args=['--no-sandbox'])
                context = browser.new_context(viewport={'width':1440,'height':1000},locale='ru-RU',
                    record_har_path=str(lab/'docs/browser.har'),record_har_mode='full',record_har_content='omit')
                page = context.new_page()
                errors=[]
                page.on('pageerror',lambda error:errors.append(str(error)))
                results=[]
                for path,name,expected in [('/', '01-home.png',200),('/tickets','02-tickets.png',200),
                    ('/tickets/3','03-ticket.png',200),('/categories/2','06-category.png',200),
                    ('/tickets/create','07-create.png',200),('/nonexistent','05-404.png',404)]:
                    response=page.goto(base+path,wait_until='networkidle')
                    assert response.status==expected,(path,response.status)
                    assert page.locator('h1').count()==1
                    page.screenshot(path=str(images/name),full_page=True)
                    results.append(f'| `{path}` | {response.status} | `{name}` |')
                page.goto(base+'/tickets/create')
                page.locator('#category_id').select_option('2')
                page.locator('#priority').select_option('high')
                page.locator('#description').fill('Не запускается приложение. Описание сохранено после ошибки.')
                with page.expect_navigation() as navigation:
                    page.get_by_role('button',name='Сохранить').click()
                assert navigation.value.status==422
                assert page.locator('#description').input_value().startswith('Не запускается')
                assert page.locator('#category_id').input_value()=='2'
                assert page.locator('.error').count()==1
                page.screenshot(path=str(images/'04-create-errors.png'),full_page=True)
                results.append('| `POST /tickets`, пустое название | 422 | `04-create-errors.png` |')
                # Отправка корректной формы браузером с реальным переходом 302 -> 200.
                page.locator('#title').fill('Не запускается приложение на рабочем месте')
                with page.expect_navigation() as navigation:
                    page.get_by_role('button',name='Сохранить').click()
                assert navigation.value.status==200
                assert page.url==base+'/tickets'
                assert page.get_by_role('link',name='Не запускается приложение на рабочем месте').count()==1
                saved=json.loads((copied/'storage/tickets.json').read_text())
                assert len(saved)==7 and saved[-1]['category_id']==2
                assert errors==[],errors
                context.close()
                # Сохраняем подлинные заголовки curl; на скриншоте открыт этот текстовый файл.
                evidence='Реальные HTTP-ответы встроенного сервера PHP (curl)\n\n'
                commands=[['-D','-','-o','/dev/null',base+'/tickets'],
                    ['-D','-','-o','/dev/null',base+'/nonexistent'],
                    ['-D','-','-o','/dev/null','--data','title=',base+'/tickets'],
                    ['-L','-D','-','-o','/dev/null','--data-urlencode','title=Проверка HTTP перенаправления',
                     '--data-urlencode','description=Контроль сохранения JSON',
                     '--data','category_id=1&status=new&priority=medium',base+'/tickets']]
                for command in commands:
                    result=subprocess.run(['curl','--noproxy','*','-sS']+command,capture_output=True,text=True,check=True)
                    evidence+='$ curl '+ shlex.join(command)+'\n'+result.stdout+'\n'
                evidence_path=lab/'docs/http-headers.txt'
                evidence_path.write_text(evidence.rstrip() + '\n')
                proof=browser.new_page(viewport={'width':1440,'height':1500})
                (copied / 'public/http-headers.txt').write_text(evidence)
                proof.goto(base + '/http-headers.txt')
                proof.screenshot(path=str(images/'08-http.png'),full_page=True)
                browser.close()
            (lab/'docs/browser-results.md').write_text('# Проверка в Chromium\n\n'
                'Скриншоты сделаны Playwright в Chromium с настоящего встроенного сервера PHP. '
                'Использована временная копия приложения; исходные JSON не менялись.\n\n'
                '| Адрес / действие | HTTP-код | Скриншот |\n| --- | --- | --- |\n'+'\n'.join(results)+
                '\n\nКорректная форма отправлена браузером: переход на `/tickets`, ответ 200 после 302; '
                'созданная запись найдена в списке и JSON (7 записей в тестовой копии). Ошибок JavaScript нет. '
                'Сетевые запросы и цепочка перенаправления записаны в [browser.har](browser.har).\n\n'
                '[08-http.png](../images/08-http.png) показывает сохранённый вывод настоящих команд curl '
                'из [http-headers.txt](http-headers.txt), открытый как текст в браузере. '
                'Это скриншот HTTP-проверок, а не вкладки DevTools Network. '
                'Вкладку Network для демонстрации преподавателю можно открыть вручную (F12).\n')
        finally:
            server.terminate();server.wait(timeout=5)
print('Сохранены 8 PNG, HAR, журнал сервера и результаты браузерных проверок.')
