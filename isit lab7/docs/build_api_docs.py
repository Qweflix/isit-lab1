#!/usr/bin/env python3
"""Собрать API-документацию с фактическими примерами из final-transcript.json."""
import json
from pathlib import Path
lab=Path(__file__).resolve().parents[1]
traces=json.loads((lab/'docs/final-transcript.json').read_text())
resources=[('tickets','Заявки'),('categories','Категории'),('staff','Сотрудники поддержки')]
schemas={
 'tickets': [('title','string','да','Не менее 3 символов после удаления пробелов по краям.'),('category_id','integer','да','ID существующей категории. JSON-строка "1" не принимается вместо числа 1.'),('description','string','да','Непустое описание проблемы.'),('status','string','нет','new (по умолчанию), in_progress, done.'),('priority','string','нет','low, medium (по умолчанию), high, critical.')],
 'categories':[('title','string','да','Не менее 3 символов.'),('description','string','нет','Описание; по умолчанию пустая строка.')],
 'staff':[('name','string','да','Не менее 3 символов.'),('email','string','да','Корректный адрес электронной почты.'),('role','string','нет','Непустая должность; по умолчанию «Инженер».')]
}
def find(method,path,status):
 return next(t for t in traces if t['method']==method and t['path']==path and t['status']==status)
def sample(t):
 s='**Пример запроса:**\n\n```http\n'+t['method']+' '+t['path']+' HTTP/1.1\nHost: localhost:8000\n'
 if t['request'] is not None:
  s+='Content-Type: application/json\n\n'+json.dumps(t['request'],ensure_ascii=False,indent=2)+'\n'
 s+='```\n\n**Пример фактического ответа:**\n\n```http\n'+t['headers'].splitlines()[0]+'\n'
 for line in t['headers'].splitlines()[1:]:
  if line.lower().startswith(('content-type:','location:','allow:')):s+=line+'\n'
 if t['body']:s+='\n'+json.dumps(json.loads(t['body']),ensure_ascii=False,indent=2)+'\n'
 s+='```\n\n'
 if t['status']==204:s+='Тело ответа отсутствует.\n\n'
 return s
text='''# Документация REST API системы учёта заявок

Базовый адрес: `http://localhost:8000`. API отвечает JSON в UTF-8; успешное удаление возвращает 204 без тела. Авторизация отсутствует в рамках задания. Начальные данные: 6 заявок, 3 категории, 3 сотрудника.

## Сводная таблица конечных точек

| Метод | URL | Назначение | Успешный код |
| --- | --- | --- | --- |
'''
for resource,label in resources:
 for method,suffix,desc,code in [('GET','','Список',200),('GET','/{id}','Просмотр по ID',200),('POST','','Создание',201),('PUT','/{id}','Обновление присланных полей',200),('PATCH','/{id}','Обновление присланных полей',200),('DELETE','/{id}','Удаление',204)]:
  text+=f'| {method} | `/api/{resource}{suffix}` | {label}: {desc.lower()} | {code} |\n'
text+='| GET | `/api/categories/{id}/tickets` | Заявки выбранной категории | 200 |\n\n'
text+='''## Общие правила

- В URL используется положительный целочисленный `id`. Несуществующий, нечисловой или слишком большой ID даёт 404.
- POST, PUT и PATCH принимают JSON-объект. Передавайте `Content-Type: application/json`. Некорректный JSON, пустое тело, массив или скаляр вместо объекта дают 400.
- Строки очищаются от пробелов по краям. Проверки полей возвращают 422 с объектом `errors`. Проверка JSON-типов обязательна: `category_id` должен быть целым числом, не строкой или `null`.
- `id` назначает сервер как максимальный ID плюс один. `clean()` отбрасывает неизвестные поля и попытки изменения `id`. PATCH с `{}` сохраняет запись без изменений.
- По условию учебной работы **PUT и PATCH имеют одинаковое частичное обновление**: отсутствующие поля сохраняют прежние значения. Это учебное упрощение относительно полной замены ресурса стандартным PUT.
- 201 содержит `Location` с адресом созданной записи; GET этого адреса возвращает тот же объект. 204 не содержит ни JSON, ни другого тела.
- Ошибка маршрута внутри `/api` всегда JSON. Если путь существует для другого метода, возвращаются 405 и `Allow`. Для коллекции разрешены GET, POST; для объекта — GET, PUT, PATCH, DELETE; для вложенного списка — GET.
- Неизвестные query-параметры игнорируются. Для списка заявок поддерживаются `status`, `category_id`, `q`; фильтры применяются совместно через И. У категорий и сотрудников фильтров нет.
- Операции POST, PUT, PATCH, DELETE меняют JSON-файлы. Примеры из блока одного ресурса выполняются последовательно на исходных данных. ID в примерах получены фактическими запросами; после собственных изменений используйте ID из вашего ответа.
- Обычные HTML-адреса сайта ЛР6 сохранены отдельно. Старые API `/api/tasks`, `/api/projects`, `/api/users` отсутствуют и дают 404.

В тестовых примерах POST дополнительно переданы `id` и `unexpected`: сервер игнорирует их через `clean()`. Это намеренная проверка, в обычном запросе эти поля не нужны.

### Единый формат ошибок

```json
{"error":"Ошибка проверки данных","errors":{"title":"Название должно содержать не менее 3 символов."}}
```

При ошибке без привязки к полям `errors` — пустой JSON-объект `{}`.

| Код | Причина |
| --- | --- |
| 400 | Тело не удалось прочитать как JSON-объект |
| 404 | Неизвестный адрес, ID или удалённый объект |
| 405 | Метод не поддерживается для существующего пути; есть Allow |
| 409 | Категория содержит заявки и не может быть удалена |
| 422 | Неверные поля или параметры фильтра |
| 500 | Ошибка сервера / хранилища; подробности только в журнале |

'''
for resource,label in resources:
 text+='## '+label+'\n\n'
 for method,pattern,code,operation in [('GET','',200,'Получить список'),('GET','/{id}',200,'Получить одну запись'),('POST','',201,'Создать запись'),('PUT','/{id}',200,'Обновить переданные поля'),('PATCH','/{id}',200,'Обновить переданные поля'),('DELETE','/{id}',204,'Удалить запись')]:
  text+=f'### {method} /api/{resource}{pattern}\n\n**Назначение:** {operation.lower()}.\n\n'
  object_id='1' if method=='GET' else ('7' if resource=='tickets' else '4')
  actual='/api/'+resource+(('/'+object_id) if pattern else '')
  t=find(method,actual,code)
  if pattern:text+='**Параметры пути:** `id` — обязательный положительный целый номер записи.\n\n'
  else:text+='**Параметры пути:** отсутствуют.\n\n'
  if method=='GET' and pattern=='' and resource=='tickets':
   text+='''**Query-параметры (необязательные):**

| Имя | Тип | Описание |
| --- | --- | --- |
| status | string | new, in_progress или done. Неизвестное значение — 422 |
| category_id | integer в URL | Положительный номер. Неверный формат — 422; неизвестный номер — пустой массив 200 |
| q | string | Подстрока названия, без учёта регистра, в том числе для кириллицы. Пустая строка не ограничивает список; массив вместо строки — 422 |

Например: `/api/tickets?status=new&category_id=1&q=компьютер`. Без фильтров возвращаются все заявки. Нет совпадений — `[]` и 200.

'''
  else:text+='**Query-параметры:** не используются.\n\n'
  if method in ['POST','PUT','PATCH']:
   text+='**Тело JSON:**\n\n| Поле | Тип | Обязательно | Проверка / значение по умолчанию |\n| --- | --- | --- | --- |\n'
   for field,typ,required,desc in schemas[resource]:text+=f'| {field} | {typ} | {required if method=="POST" else "нет; сохраняется прежнее"} | {desc} |\n'
   text+='\nПри обновлении проверяется итоговая запись после объединения со старой; значения по умолчанию применяются только при создании.\n\n'
  else:text+='**Тело запроса:** отсутствует.\n\n'
  text+=sample(t)
  errors=[]
  if pattern:errors+=['404 — запись не найдена']
  if method in ['POST','PUT','PATCH']:errors+=['400 — не JSON-объект','422 — неверные поля']
  if resource=='tickets' and method=='GET' and not pattern:errors+=['422 — неверный фильтр']
  if resource=='categories' and method=='DELETE':errors+=['409 — в категории есть заявки; сначала удалите их или измените category_id']
  errors+=['405 — неподдерживаемый метод для пути','500 — ошибка сервера или хранилища']
  text+='**Возможные ошибки:** '+ '; '.join(errors)+'.\n\n'
text+='''## Вложенная связь

### GET /api/categories/{id}/tickets

**Назначение:** вернуть заявки, у которых `category_id` равен ID категории.

**Параметры пути:** `id` — обязательный положительный целый номер существующей категории.

**Query-параметры:** не используются. **Тело запроса:** отсутствует.

'''+sample(find('GET','/api/categories/2/tickets',200))+'''Существующая категория без заявок возвращает `[]` и 200. Несуществующая категория возвращает 404. Другой HTTP-метод даёт 405 с `Allow: GET`; ошибка хранилища — 500.

## Проверенные примеры ошибок

'''
for code in [400,404,405,409,422,500]:
 t=next(t for t in traces if t['status']==code)
 text+=f'### Ответ {code}\n\n'
 if t['raw'] is not None:text+='Тело запроса (как текст): `'+t['raw']+'`.\n\n'
 text+=sample(t)
text+='Полный протокол: [final-transcript.json](final-transcript.json). Таблица тестов: [testing.md](testing.md).\n'
(lab/'docs/api.md').write_text(text)
print('docs/api.md: 19 конечных точек с параметрами и фактическими примерами.')
