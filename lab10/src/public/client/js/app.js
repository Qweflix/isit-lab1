import { request, clearSession } from './api.js';
const $ = (id) => document.getElementById(id);
const statuses = { new: 'Новая', in_progress: 'В работе', done: 'Решена' },
  priorities = { low: 'Низкий', medium: 'Средний', high: 'Высокий', critical: 'Критический' };
let user = null,
  categories = [],
  staff = [],
  editing = null,
  listRequest = null,
  sequence = 0;
const fields = [
  'title',
  'description',
  'category_id',
  'assignee_id',
  'due_date',
  'status',
  'priority',
];
function message(text, kind = 'error') {
  $('message').textContent = text;
  $('message').className = `message-box ${kind}`;
  $('message').hidden = !text;
}
function loginScreen(text = '') {
  sequence++;
  listRequest?.abort();
  user = null;
  $('workspace').hidden = true;
  $('login-panel').hidden = false;
  $('login-error').textContent = text;
  $('ticket-tbody').replaceChildren();
  $('password').value = '';
  resetForm();
}
window.addEventListener('session-expired', () => loginScreen('Сессия истекла. Войдите снова.'));
function clearErrors() {
  document.querySelectorAll('[data-error]').forEach((el) => (el.textContent = ''));
  $('form-error').textContent = '';
}
function resetForm() {
  editing = null;
  $('ticket-form').reset();
  $('form-heading').textContent = 'Новая заявка';
  $('save').textContent = 'Создать';
  $('cancel').hidden = true;
  clearErrors();
}
function formError(e) {
  $('form-error').textContent = e.message;
  for (const [key, text] of Object.entries(e.errors || {})) {
    const el = document.querySelector(`[data-error="${key.replace(/[^a-z_]/g, '')}"]`);
    if (el) el.textContent = text;
  }
}
function fillSelect(id, rows, label, empty) {
  $(id).replaceChildren();
  if (empty) $(id).add(new Option(empty, ''));
  rows.forEach((row) => $(id).add(new Option(row[label], String(row.id))));
}
async function enter() {
  user = await request('/auth/me');
  [categories, staff] = await Promise.all([request('/categories'), request('/staff')]);
  fillSelect('category_id', categories, 'title');
  fillSelect('assignee_id', staff, 'name', 'Не назначен');
  $('identity').textContent =
    `${user.name} — ${user.role === 'manager' ? 'Руководитель' : 'Оператор'}`;
  $('login-panel').hidden = true;
  $('workspace').hidden = false;
  resetForm();
  await load();
}
function cell(row, text) {
  const td = document.createElement('td');
  td.textContent = text ?? '—';
  row.append(td);
  return td;
}
function button(td, text, fn, cls = 'btn-action') {
  const b = document.createElement('button');
  b.className = `btn ${cls}`;
  b.textContent = text;
  b.addEventListener('click', async () => {
    b.disabled = true;
    try {
      await fn();
    } catch (e) {
      message(e.message);
    } finally {
      b.disabled = false;
    }
  });
  td.append(b);
}
function render(rows) {
  $('ticket-tbody').replaceChildren();
  $('counter').textContent = rows.length;
  for (const item of rows) {
    const tr = document.createElement('tr');
    tr.dataset.id = item.id;
    cell(tr, item.id);
    const title = cell(tr, item.title),
      sub = document.createElement('small');
    sub.textContent = categories.find((x) => x.id === item.category_id)?.title || '';
    title.append(sub);
    cell(tr, priorities[item.priority]);
    cell(tr, statuses[item.status]);
    cell(
      tr,
      `${item.due_date || 'Без срока'} / ${staff.find((x) => x.id === item.assignee_id)?.name || 'Не назначен'}`,
    );
    const actions = cell(tr, '');
    button(actions, 'Изменить', () => {
      editing = item.id;
      fields.forEach((k) => ($(k).value = item[k] ?? ''));
      $('form-heading').textContent = `Изменение заявки №${item.id}`;
      $('save').textContent = 'Сохранить';
      $('cancel').hidden = false;
      clearErrors();
      $('title').focus();
    });
    button(actions, item.status === 'done' ? 'Вернуть' : 'Решена', async () => {
      await request(`/tickets/${item.id}`, {
        method: 'PATCH',
        body: { status: item.status === 'done' ? 'in_progress' : 'done' },
      });
      await load();
    });
    if (user.role === 'manager')
      button(
        actions,
        'Удалить',
        async () => {
          if (confirm('Удалить заявку?')) {
            await request(`/tickets/${item.id}`, { method: 'DELETE' });
            await load();
            message('Заявка удалена', 'success');
          }
        },
        'btn-delete',
      );
    $('ticket-tbody').append(tr);
  }
}
async function load() {
  listRequest?.abort();
  listRequest = new AbortController();
  const version = ++sequence;
  $('load-btn').disabled = true;
  message('Загрузка данных…', '');
  const q = new URLSearchParams({ sort: $('sort').value });
  if ($('search').value.trim()) q.set('q', $('search').value.trim());
  if ($('filter-status').value) q.set('status', $('filter-status').value);
  try {
    const rows = await request(`/tickets?${q}`, { signal: listRequest.signal });
    if (version !== sequence) return;
    render(rows);
    message(rows.length ? '' : 'Заявки не найдены. Измените фильтр или текст поиска.', '');
  } catch (e) {
    if (e.name !== 'AbortError' && version === sequence) {
      render([]);
      message(e.message);
    }
  } finally {
    if (version === sequence) $('load-btn').disabled = false;
  }
}
$('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const b = e.submitter;
  b.disabled = true;
  $('login-error').textContent = '';
  try {
    const result = await request('/auth/login', {
      method: 'POST',
      body: { email: $('email').value.trim(), password: $('password').value },
    });
    localStorage.setItem('authToken', result.token);
    $('password').value = '';
    await enter();
  } catch (error) {
    $('login-error').textContent = error.message;
  } finally {
    b.disabled = false;
  }
});
$('ticket-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearErrors();
  $('save').disabled = true;
  const data = Object.fromEntries(fields.map((k) => [k, $(k).value]));
  data.category_id = Number(data.category_id);
  data.assignee_id = data.assignee_id ? Number(data.assignee_id) : null;
  data.due_date = data.due_date || null;
  try {
    const updating = editing !== null;
    await request(updating ? `/tickets/${editing}` : '/tickets', {
      method: updating ? 'PATCH' : 'POST',
      body: data,
    });
    resetForm();
    await load();
    message(updating ? 'Заявка изменена' : 'Заявка создана', 'success');
  } catch (error) {
    formError(error);
  } finally {
    $('save').disabled = false;
  }
});
$('cancel').addEventListener('click', resetForm);
$('logout').addEventListener('click', async () => {
  $('logout').disabled = true;
  try {
    await request('/auth/logout', { method: 'POST' });
    clearSession();
    loginScreen('Вы вышли из системы.');
  } catch (e) {
    if (user) message(e.message);
  } finally {
    $('logout').disabled = false;
  }
});
$('load-btn').addEventListener('click', load);
$('sort').addEventListener('change', load);
$('filter-status').addEventListener('change', load);
let debounce;
$('search').addEventListener('input', () => {
  clearTimeout(debounce);
  debounce = setTimeout(load, 250);
});
if (localStorage.getItem('authToken')) enter().catch((e) => loginScreen(e.message));
