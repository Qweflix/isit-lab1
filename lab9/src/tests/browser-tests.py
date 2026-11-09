"""Real Chromium scenarios. Needs playwright, DEMO_PASSWORD and running lab9 Compose.
Run only against a disposable lab database after api-tests.php.
"""
import os, json, hashlib, subprocess, time
from pathlib import Path
from playwright.sync_api import sync_playwright, expect

LAB = Path(__file__).resolve().parents[2]
IMAGES = LAB / 'images'
COMPOSE = ['docker','compose','-p',os.getenv('COMPOSE_PROJECT_NAME','lab9'),'-f',str(LAB/'src/docker-compose.yml')]
BASE = os.getenv('APP_URL','http://127.0.0.1:8000')
PASSWORD = os.environ['DEMO_PASSWORD']
results=[]
def checked(name,expected,actual):
    assert expected == actual, (name,expected,actual)
    results.append({'scenario':name,'expected':expected,'actual':actual,'passed':True})
    print('PASS',name)
def compose(*args,**kwargs):
    return subprocess.run(COMPOSE+list(args),check=True,capture_output=True,text=True,**kwargs)
def shot(page,name): page.screenshot(path=str(IMAGES/name),full_page=True)
def login(page,email):
    page.locator('#email').fill(email)
    page.locator('#password').fill(PASSWORD)
    page.get_by_role('button',name='Войти',exact=True).click()
    expect(page.locator('#workspace')).to_be_visible()
    expect(page.locator('#load-btn')).to_be_enabled()
def api(page,method,path,body=None):
    return page.evaluate('''async ([method,path,body])=>{
      const r=await fetch('/api'+path,{method,headers:{Authorization:'Bearer '+localStorage.getItem('authToken'),'Content-Type':'application/json'},body:body===null?undefined:JSON.stringify(body)});
      return {status:r.status,body:await r.text()};
    }''',[method,path,body])
with sync_playwright() as p:
    b=p.chromium.launch(headless=True)
    context=b.new_context(viewport={'width':1440,'height':1100},locale='ru-RU')
    page=context.new_page(); errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    page.goto(BASE+'/client/');shot(page,'01-login.png')
    checked('A01 Форма входа',True,page.locator('#login-panel').is_visible())
    page.locator('#email').fill('manager@example.test');page.locator('#password').fill('incorrect')
    with page.expect_response('**/api/auth/login') as response:page.get_by_role('button',name='Войти',exact=True).click()
    expect(page.locator('#login-error')).to_have_text('Неверный email или пароль')
    checked('A02 Неверный пароль: форма и HTTP 401',401,response.value.status);shot(page,'02-login-error.png')
    login(page,'manager@example.test');checked('A03 Вход руководителя и список',6,page.locator('#ticket-tbody tr').count());shot(page,'03-list-manager.png')
    page.locator('#search').fill('КОМПЬЮТЕР');expect(page.locator('#ticket-tbody tr')).to_have_count(1)
    checked('A04 Поиск кириллицей',True,'компьютер' in page.locator('#ticket-tbody').inner_text())
    page.locator('#search').fill('');page.locator('#filter-status').select_option('done');expect(page.locator('#ticket-tbody tr')).to_have_count(2)
    checked('A05 Фильтрация',2,page.locator('#ticket-tbody tr').count())
    page.locator('#filter-status').select_option('');expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    page.locator('#title').fill('Проверка интеграции принтера');page.locator('#description').fill('Заявка создана из браузера.')
    page.locator('#due_date').fill('2026-12-01');page.locator('#assignee_id').select_option('1')
    with page.expect_response(lambda r:r.request.method=='POST' and r.url.endswith('/api/tickets')) as response:page.locator('#save').click()
    expect(page.locator('#message')).to_have_text('Заявка создана');expect(page.locator('#ticket-tbody tr')).to_have_count(7)
    checked('A06 Создание со сроком и исполнителем',201,response.value.status);shot(page,'04-created.png')
    created=page.locator('#ticket-tbody tr').filter(has_text='Проверка интеграции принтера')
    created.get_by_role('button',name='Изменить',exact=True).click();page.locator('#title').fill('Проверка интеграции — изменено');page.locator('#save').click()
    expect(page.locator('#message')).to_have_text('Заявка изменена');checked('A07 Изменение темы',1,page.locator('#ticket-tbody tr').filter(has_text='Проверка интеграции — изменено').count())
    created=page.locator('#ticket-tbody tr').filter(has_text='Проверка интеграции — изменено');created.get_by_role('button',name='Решена',exact=True).click()
    expect(created.get_by_role('button',name='Вернуть',exact=True)).to_be_visible();checked('A08 Изменение статуса',True,'Решена' in created.inner_text())
    page.once('dialog',lambda dialog:dialog.accept());created.get_by_role('button',name='Удалить',exact=True).click();expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    checked('A09 Удаление руководителем',6,page.locator('#ticket-tbody tr').count())
    page.locator('#title').fill('ab');page.locator('#description').fill('')
    with page.expect_response(lambda r:r.status==422) as response:page.locator('#save').click()
    expect(page.locator('[data-error="title"]')).not_to_be_empty();expect(page.locator('[data-error="description"]')).not_to_be_empty()
    checked('A10 Ошибки 422 у полей',422,response.value.status);shot(page,'05-error-422.png')
    page.locator('#logout').click();expect(page.locator('#login-panel')).to_be_visible();checked('A11 Выход',None,page.evaluate("localStorage.getItem('authToken')"))
    login(page,'operator@example.test');checked('A12 Оператор не видит удаление',0,page.locator('.btn-delete').count());shot(page,'06-list-operator.png')
    r=api(page,'DELETE','/tickets/1');checked('A13 Прямой запрос удаления оператором',403,r['status'])
    (LAB/'docs/403-response.txt').write_text('DELETE /api/tickets/1\nAuthorization: Bearer [скрыт]\nHTTP '+str(r['status'])+'\n'+r['body']+'\n')
    page.locator('#logout').click();login(page,'manager@example.test');checked('A14 Повторный вход',True,'Руководитель' in page.locator('#identity').inner_text())
    token=page.evaluate("localStorage.getItem('authToken')")
    h=hashlib.sha256(token.encode()).hexdigest()
    sql="UPDATE auth_tokens SET expires_at=CURRENT_TIMESTAMP-INTERVAL '1 second' WHERE token_hash='"+h+"';"
    compose('exec','-T','db','psql','-U','lab9','-d','supportdb','-c',sql)
    page.locator('#load-btn').click();expect(page.locator('#login-error')).to_have_text('Сессия истекла. Войдите снова.')
    checked('A15 Истёкший на сервере токен',True,page.locator('#login-panel').is_visible());shot(page,'08-session-expired.png')
    login(page,'manager@example.test');context.set_offline(True);page.locator('#load-btn').click()
    expect(page.locator('#message')).to_contain_text('Нет связи с сервером');checked('A16 Пропажа сети',True,page.locator('#message').is_visible());shot(page,'09-offline.png')
    context.set_offline(False);page.locator('#load-btn').click();expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    checked('A17 Восстановление связи',6,page.locator('#ticket-tbody tr').count())
    page.locator('#search').fill('несуществующаязаявка');expect(page.locator('#ticket-tbody tr')).to_have_count(0);expect(page.locator('#message')).to_contain_text('не найдены')
    checked('A18 Пустой результат',0,page.locator('#ticket-tbody tr').count());shot(page,'14-empty.png')
    page.locator('#search').fill('');expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    compose('stop','db')
    try:
        with page.expect_response(lambda r:r.status==503) as response:page.locator('#load-btn').click()
        expect(page.locator('#message')).to_have_text('База данных недоступна')
        checked('A19 Остановлена PostgreSQL',503,response.value.status);shot(page,'15-db-down.png')
    finally:compose('up','-d','--wait','db')
    page.locator('#load-btn').click();expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    checked('A20 Восстановление PostgreSQL',6,page.locator('#ticket-tbody tr').count())
    compose('stop','web')
    try:
        page.locator('#load-btn').click();expect(page.locator('#message')).to_contain_text('Нет связи с сервером')
        checked('A21 Остановлен PHP-сервер',True,page.locator('#message').is_visible())
    finally:compose('start','web')
    page.locator('#load-btn').click();expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    checked('A22 Восстановление PHP-сервера',6,page.locator('#ticket-tbody tr').count())
    payload='<img src=x onerror="window.xss=true">'
    page.locator('#title').fill(payload);page.locator('#description').fill('Проверка безопасного вывода')
    page.locator('#save').click();expect(page.locator('#message')).to_have_text('Заявка создана')
    row=page.locator('#ticket-tbody tr').filter(has_text=payload);expect(row).to_have_count(1)
    checked('A23 Защита DOM от XSS',True,row.locator('img').count()==0 and page.evaluate('window.xss !== true'))
    page.once('dialog',lambda d:d.accept());row.get_by_role('button',name='Удалить',exact=True).click();expect(page.locator('#ticket-tbody tr')).to_have_count(6)
    checked('A24 Нет исключений JavaScript',[],errors)
    page.locator('#logout').click();b.close()
(LAB/'docs/browser-results.json').write_text(json.dumps(results,ensure_ascii=False,indent=2)+'\n')
print('TOTAL',len(results),'FAILED 0')
