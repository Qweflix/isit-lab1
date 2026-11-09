#!/usr/bin/env python3
"""API-проверки настоящими curl-запросами в изолированной копии приложения."""
import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import shlex
import shutil
import socket
import subprocess
import tempfile
import time
from urllib.parse import quote as urllib_quote

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--demo',action='store_true',help='проверка примера до переноса')
args=parser.parse_args()
lab=Path(__file__).resolve().parents[1]
php=os.environ.get('PHP_BIN','php')
main,parent,third=('tasks','projects','users') if args.demo else ('tickets','categories','staff')
foreign='project_id' if args.demo else 'category_id'
extra='due_date' if args.demo else 'description'
trace=[]
checks=[]
with tempfile.TemporaryDirectory(prefix='lab7-api-') as tmp:
    copied=Path(tmp)/'src';shutil.copytree(lab/'src',copied)
    files=sorted(copied.rglob('*.php'))
    for file in files:
        result=subprocess.run([php,'-l',str(file)],capture_output=True,text=True)
        assert result.returncode==0,result.stdout+result.stderr
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    base=f'http://127.0.0.1:{port}'
    prefix='baseline' if args.demo else 'final'
    log_path=lab/'docs'/f'{prefix}-server.log'
    with log_path.open('w') as log:
        server=subprocess.Popen([php,'-d','display_errors=1','-d','error_reporting=32767','-S',f'127.0.0.1:{port}','-t',str(copied/'public')],stdout=log,stderr=log)
        def call(method,path,expected,data=None,raw=None,label=None):
            body_path=Path(tmp)/'response-body.txt';head_path=Path(tmp)/'response-headers.txt'
            command=['curl','--noproxy','*','--silent','--show-error','--max-time','10','-X',method,base+path]
            if data is not None or raw is not None:
                command+=['-H','Content-Type: application/json','--data-raw',raw if raw is not None else json.dumps(data,ensure_ascii=False)]
            result=subprocess.run(command+['-D',str(head_path),'-o',str(body_path),'-w','%{http_code}'],capture_output=True,text=True)
            assert result.returncode==0,result.stderr
            code=int(result.stdout);body=body_path.read_text();headers_text=head_path.read_text()
            headers={}
            for line in headers_text.splitlines()[1:]:
                if ': ' in line:
                    name,value=line.split(': ',1);headers[name.lower()]=value
            assert code==expected,(method,path,expected,code,body)
            assert not any(v in body for v in ['Warning:','Fatal error:','Deprecated:','Notice:']),body
            decoded=None
            if code==204:
                assert body=='',(method,path,body)
            else:
                assert headers.get('content-type','').startswith('application/json'),(headers,body)
                decoded=json.loads(body)
                if code>=400:
                    assert isinstance(decoded,dict) and isinstance(decoded.get('error'),str) and isinstance(decoded.get('errors'),dict),decoded
            name=label or method+' '+path
            checks.append((name,expected,code))
            trace.append({'label':name,'method':method,'path':path,'request':data,'raw':raw,'command':shlex.join(command),'status':code,'headers':headers_text,'body':body})
            return decoded,headers
        try:
            for _ in range(80):
                with socket.socket() as sock:
                    if sock.connect_ex(('127.0.0.1',port))==0:break
                time.sleep(.05)
            else:raise RuntimeError('PHP не запустился')
            original={name:json.loads((copied/'storage'/f'{name}.json').read_text()) for name in [main,parent,third]}
            # Все пять операций и оба варианта обновления для каждого ресурса.
            for resource,payload,patch in [
                (main,{'title':'Проверка создания через API',foreign:1,extra:'2026-11-01' if args.demo else 'Приложение не открывается на рабочем месте.'},{'status':'done'}),
                (parent,{'title':'Дополнительная категория','description':'Создано для проверки CRUD.'},{'description':'Описание обновлено.'}),
                (third,{'name':'Ирина Волкова','email':'irina@example.test','role':'Инженер поддержки'},{'role':'Старший инженер'})]:
                listed,_=call('GET','/api/'+resource,200)
                assert listed==original[resource]
                one,_=call('GET','/api/'+resource+'/1',200)
                assert one==original[resource][0]
                payload=dict(payload,id=999,unexpected='ignore')
                created,headers=call('POST','/api/'+resource,201,data=payload)
                created_id=created['id'];location=headers.get('location')
                assert location=='/api/'+resource+'/'+str(created_id)
                assert created_id==max(r['id'] for r in original[resource])+1
                assert 'unexpected' not in created
                assert created_id!=999
                if resource==main:
                    assert created['status']=='new' and created['priority']=='medium'
                fetched,_=call('GET',location,200)
                assert fetched==created
                changed,_=call('PATCH',location,200,data=dict(patch,id=12345))
                for key,value in patch.items():assert changed[key]==value
                assert changed['id']==created_id
                for key in set(created)-set(patch):assert changed[key]==created[key]
                updated,_=call('PUT',location,200,data=patch)
                assert updated==changed,'PUT должен сохранять не присланные поля по условию ЛР7'
                call('DELETE',location,204)
                call('GET',location,404,label='GET после удаления '+resource)
                call('DELETE',location,404,label='Повторное удаление '+resource)
                assert json.loads((copied/'storage'/f'{resource}.json').read_text())==original[resource]
                call('POST','/api/'+resource,422,data={},label='Пустые поля '+resource)
                call('POST','/api/'+resource,400,raw='абс',label='Некорректный JSON '+resource)
                call('GET','/api/'+resource+'/99999',404)
                _,allowed=call('DELETE','/api/'+resource,405)
                assert set(allowed['allow'].split(', '))=={'GET','POST'}
                _,allowed=call('POST','/api/'+resource+'/1',405,data={})
                assert set(allowed['allow'].split(', '))=={'GET','PUT','PATCH','DELETE'}
                call('PATCH','/api/'+resource+'/99999',404,data={})
            rows,_=call('GET','/api/'+main+'?status=done',200)
            assert rows==[r for r in original[main] if r['status']=='done']
            rows,_=call('GET','/api/'+main+'?'+foreign+'=2',200)
            assert rows==[r for r in original[main] if r[foreign]==2]
            rows,_=call('GET','/api/'+main+'?status=done&'+foreign+'=2',200)
            assert rows==[r for r in original[main] if r[foreign]==2 and r['status']=='done']
            rows,_=call('GET','/api/'+main+'?'+foreign+'=99999',200)
            assert rows==[]
            nested,_=call('GET','/api/'+parent+'/2/'+main,200)
            assert nested==[r for r in original[main] if r[foreign]==2]
            call('GET','/api/'+parent+'/99999/'+main,404)
            call('DELETE','/api/'+parent+'/1',409)
            assert json.loads((copied/'storage'/f'{parent}.json').read_text())==original[parent]
            valid={'title':'Проверка валидации',foreign:1,extra:'2026-11-01' if args.demo else 'Описание обращения.'}
            for field,value in [('title','Да'),(foreign,'1'),(foreign,99999),('status','unknown'),('priority',['high']),('title',None),(extra,'bad-date' if args.demo else '')]:
                problem,_=call('POST','/api/'+main,422,data=dict(valid,**{field:value}),label='Неверное поле '+field+' = '+str(value))
                assert field in problem['errors']
            call('POST','/api/'+third,422,data={'name':12,'email':'bad','role':[]})
            call('PATCH','/api/'+parent+'/1',422,data={'title':[]})
            for raw in ['{"title":','[]','null','"строка"','1','']:
                call('POST','/api/'+main,400,raw=raw,label='Неверное тело: '+repr(raw))
            call('GET','/api/missing',404)
            call('GET','/api',404)
            call('GET','/api/'+main+'/abc',404)
            call('GET','/api/'+main+'/99999999999999999999999999999',404)
            call('GET','/api/'+main+'?status=unknown',422)
            call('GET','/api/'+main+'?'+foreign+'=abc',422)
            call('GET','/api/'+main+'?status[]=done',422)
            # Сайт ЛР6 продолжает возвращать HTML, файлы storage не опубликованы.
            for path,code in [('/',200),('/'+main,200),('/'+parent,200),('/'+main+'/create',200),('/nonexistent',404),('/storage/'+main+'.json',404)]:
                result=subprocess.run(['curl','--noproxy','*','-sS','-o',str(Path(tmp)/'html'),'-w','%{http_code}',base+path],capture_output=True,text=True,check=True)
                assert int(result.stdout)==code,(path,result.stdout)
                assert '<!doctype html>' in (Path(tmp)/'html').read_text()
                checks.append(('HTML GET '+path,code,int(result.stdout)))
            if not args.demo:
                rows,_=call('GET','/api/tickets?q='+urllib_quote('КОМПЬЮТЕР'),200)
                assert rows and all('компьютер' in r['title'].lower() for r in rows)
                rows,_=call('GET','/api/tickets?q='+urllib_quote('неизвестная строка'),200)
                assert rows==[]
                call('GET','/api/tickets?q[]=x',422)
                for path in ['/api/tasks','/api/projects','/api/users','/api/tasks/1','/api/projects/2/tasks']:
                    call('GET',path,404,label='Старый адрес '+path)
            for name in original:
                assert json.loads((copied/'storage'/f'{name}.json').read_text())==original[name],'Ошибочные запросы изменили данные'
            # Настоящая ошибка JSON-хранилища: 500 в JSON, без вывода PHP-трассировки клиенту.
            storage=copied/'storage'/f'{main}.json';snapshot=storage.read_bytes();storage.write_text('invalid JSON')
            error,_=call('GET','/api/'+main,500,label='Ошибка чтения хранилища')
            assert error=={'error':'Внутренняя ошибка сервера','errors':{}}
            storage.write_bytes(snapshot)
            call('GET','/api/'+main,200,label='Работа после восстановления хранилища')
        finally:
            server.terminate();server.wait(timeout=5)
    log_text=log_path.read_text()
    assert not re.search(r'PHP (Warning|Fatal|Deprecated|Notice)',log_text),log_text
    assert log_text.count('JsonException:')==1,'Ожидается ровно одно намеренное исключение для проверки 500'
    (lab/'docs'/f'{prefix}-transcript.json').write_text(json.dumps(trace,ensure_ascii=False,indent=2)+'\n')
    report='# '+('Проверка примера до переноса' if args.demo else 'Результаты тестирования REST API')+'\n\n'
    report+=f'Дата UTC: {datetime.now(timezone.utc).isoformat(timespec="seconds")}. '+subprocess.check_output([php,'-v'],text=True).splitlines()[0]+'.\n\n'
    report+=f'Проверены {len(files)} PHP-файлов (`php -l`) и {len(checks)} HTTP-сценариев. Все API-запросы отправлены настоящим curl на встроенный сервер PHP.\n\n'
    report+='| № | Запрос / сценарий | Ожидаемый код | Фактический код |\n| --- | --- | --- | --- |\n'
    report+=''.join(f'| {i} | `{name}` | {expected} | {actual} |\n' for i,(name,expected,actual) in enumerate(checks,1))
    report+='\nПроверены тела JSON, Content-Type, Location при 201, Allow при 405, пустое тело при 204, обновление только присланных полей, запрет изменения id, фильтры, вложенный список, ошибки проверки данных, запрет удаления связанной записи и сохранение JSON.\n\n'
    report+='Тесты работают во временной копии src: исходные данные не меняются. В журнале есть одно намеренно вызванное исключение JsonException для проверки ответа 500; предупреждений, фатальных ошибок и непредвиденных исключений нет.\n\n'
    report+=f'Полный протокол запросов и ответов: [{prefix}-transcript.json]({prefix}-transcript.json). Журнал сервера: [{prefix}-server.log]({prefix}-server.log).\n'
    (lab/'docs'/('baseline-testing.md' if args.demo else 'testing.md')).write_text(report)
    print(report)
