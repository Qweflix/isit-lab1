#!/usr/bin/env python3
"""Реальные HTTP-проверки в изолированной копии данных. Python 3, PHP >= 8.2."""
import argparse
import html
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--demo', action='store_true', help='проверка примера до переноса')
args = parser.parse_args()
lab = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', 'php')
main, related = ('tasks', 'projects') if args.demo else ('tickets', 'categories')
foreign = 'project_id' if args.demo else 'category_id'
extra = 'due_date' if args.demo else 'description'
rows = []
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())

def request(path, data=None):
    encoded = None if data is None else urllib.parse.urlencode(data, doseq=True).encode()
    try:
        response = opener.open(base + path, encoded, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    body = response.read().decode('utf-8')
    assert not re.search(r'(Warning:|Fatal error:|Deprecated:|Notice:)', body), body
    return response.code, response.headers, body

def check(label, path, expected, data=None, verify=None):
    code, headers, body = request(path, data)
    assert code == expected, (label, expected, code, body[:1000])
    if verify:
        verify(headers, body)
    rows.append((label, str(expected), str(code), 'Пройдено'))
    return headers, body

def contains(needle):
    def verify(headers, body):
        assert needle in body, needle
    return verify

with tempfile.TemporaryDirectory(prefix='lab6-http-') as tmp:
    copied = Path(tmp) / 'src'
    shutil.copytree(lab / 'src', copied)
    lint = []
    for path in sorted(copied.rglob('*.php')):
        run = subprocess.run([php, '-l', str(path)], capture_output=True, text=True)
        assert run.returncode == 0, run.stdout + run.stderr
        lint.append(str(path.relative_to(copied)))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}'
    log_path = Path(tmp) / 'server.log'
    with log_path.open('w+') as log:
        server = subprocess.Popen([php, '-d', 'display_errors=1', '-d', 'error_reporting=32767',
                                   '-S', f'127.0.0.1:{port}', '-t', str(copied / 'public')], stdout=log, stderr=log)
        try:
            for attempt in range(80):
                try:
                    request('/')
                    break
                except (urllib.error.URLError, ConnectionError):
                    time.sleep(0.05)
            else:
                raise RuntimeError('Сервер не запустился')
            storage = copied / 'storage' / (main + '.json')
            original = json.loads(storage.read_text())
            related_rows = json.loads((copied / 'storage' / (related + '.json')).read_text())
            def home(headers, body):
                assert body.count('<article>') == 4
                numbers = [int(x) for x in re.findall(r'<b>([0-9]+)</b>', body)]
                assert numbers == [sum(r['status'] == s for r in original) for s in ['new', 'in_progress', 'done']] + [len(related_rows)]
                if not args.demo:
                    assert 'Система учёта заявок пользователей' in body
            check('GET /', '/', 200, verify=home)
            check('GET /' + main, '/' + main, 200, verify=contains(original[0]['title']))
            def filtered(headers, body):
                statuses = re.findall(r'data-status="([^"]+)"', body)
                assert len(statuses) == sum(r['status'] == 'done' for r in original)
                assert set(statuses) == {'done'}
            check('GET /' + main + '?status=done', '/' + main + '?status=done', 200, verify=filtered)
            def detail(headers, body):
                row = next(r for r in original if r['id'] == 3)
                assert row['title'] in body
                assert next(r['title'] for r in related_rows if r['id'] == row[foreign]) in body
            check('GET /' + main + '/3', '/' + main + '/3', 200, verify=detail)
            check('GET /' + main + '/999', '/' + main + '/999', 404)
            check('GET /' + main + '/create', '/' + main + '/create', 200, verify=contains('<form method="post"'))
            check('POST /' + main + ' (пустая форма)', '/' + main, 422, data={}, verify=contains('class="error"'))
            payload = {'title': 'Контрольная запись <script>alert("x")</script>', foreign: '2',
                       'status': 'new', 'priority': 'high', extra: '2026-11-01' if args.demo else 'Проверка сохранения и экранирования <b>текста</b>.'}
            invalid = dict(payload, title='')
            def retained(headers, body):
                assert html.escape(payload[extra], quote=True) in body
                assert 'value="2" selected' in body
                assert 'value="high" selected' in body
            check('POST /' + main + ' (пустое название, сохранение полей)', '/' + main, 422, data=invalid, verify=retained)
            headers, _ = check('POST /' + main + ' (корректная форма)', '/' + main, 302, data=payload)
            assert headers['Location'] == '/' + main
            saved = json.loads(storage.read_text())
            assert len(saved) == len(original) + 1
            created = saved[-1]
            assert created['id'] == max(r['id'] for r in original) + 1
            assert created[foreign] == 2
            assert created['title'] == payload['title']
            check('GET после 302: /' + main, headers['Location'], 200, verify=contains(html.escape(payload['title'], quote=True)))
            check('GET /' + related, '/' + related, 200, verify=contains(related_rows[0]['title']))
            def related_detail(headers, body):
                assert next(r['title'] for r in related_rows if r['id'] == 2) in body
                for row in saved:
                    if row[foreign] == 2:
                        assert html.escape(row['title'], quote=True) in body
                    else:
                        assert html.escape(row['title'], quote=True) not in body
            check('GET /' + related + '/2', '/' + related + '/2', 200, verify=related_detail)
            check('GET /nonexistent', '/nonexistent', 404)
            check('POST /' + related, '/' + related, 404, data={})
            check('GET /' + related + '/999', '/' + related + '/999', 404)
            check('GET /' + main + '/abc', '/' + main + '/abc', 404)
            check('GET /' + main + '?status=unknown', '/' + main + '?status=unknown', 200, verify=contains('Записи не найдены'))
            for field, value in [(foreign,'999'),('status','unknown'),('priority','unknown'),(extra,'')]:
                check('POST: неверное поле ' + field, '/' + main, 422, data=dict(payload, **{field:value}))
            check('POST: массив вместо title', '/' + main, 422, data=dict(payload, **{'title':'', 'title[]':'bad'}))
            def escaped(headers, body):
                assert '<script>' not in body
                assert '&lt;script&gt;' in body
            check('GET: защита HTML-вывода', '/' + main + '/' + str(created['id']), 200, verify=escaped)
            assert len(json.loads(storage.read_text())) == len(original) + 1, 'Невалидные формы изменили JSON'
            check('GET /storage/' + main + '.json', '/storage/' + main + '.json', 404)
            if not args.demo:
                for path in ['/tasks','/tasks/create','/tasks/3','/projects','/projects/2','/members']:
                    check('GET ' + path + ' (старый адрес)', path, 404)
        finally:
            server.terminate()
            server.wait(timeout=5)
        log.flush()
    log_text = log_path.read_text()
    assert not re.search(r'(PHP (Warning|Fatal|Notice|Deprecated)|Uncaught|Stack trace)', log_text), log_text
    output = lab / 'docs' / ('baseline-http' if args.demo else 'final-http')
    output.with_suffix('.log').write_text(log_text)
    version = subprocess.check_output([php, '-v'], text=True).splitlines()[0]
    text = '# Проверка ' + ('примера перед переносом' if args.demo else 'системы учёта заявок') + '\n\n'
    text += f'Дата UTC: {datetime.now(timezone.utc).isoformat(timespec="seconds")}.\n\n{version}\n\n'
    text += f'Проверены синтаксис {len(lint)} PHP-файлов и {len(rows)} HTTP-сценариев. Данные теста — временная копия; исходные JSON не изменены.\n\n'
    text += '| Запрос / сценарий | Ожидаемый код | Фактический код | Результат |\n| --- | --- | --- | --- |\n'
    text += ''.join('| ' + ' | '.join(row) + ' |\n' for row in rows)
    text += '\nТакже проверены содержимое фильтра, связь сущностей, введённые значения при 422, Location при 302, появление записи в JSON, новый числовой id, экранирование HTML и отсутствие изменений после ошибочных форм. Ошибок и предупреждений PHP в журнале не обнаружено.\n'
    output.with_suffix('.md').write_text(text)
    print(text)
