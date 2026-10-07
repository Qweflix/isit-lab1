// ==========================================
// 1. Ссылки на элементы DOM
// ==========================================
const form = document.getElementById('task-form');
const titleInput = document.getElementById('task-title');
const prioritySelect = document.getElementById('task-priority');
const errorSpan = document.getElementById('form-error');

const filterSelect = document.getElementById('filter-status');
const searchInput = document.getElementById('search-input');
const loadBtn = document.getElementById('load-btn');
const counterBadge = document.getElementById('task-counter');

const messageBox = document.getElementById('message-box');
const tbody = document.getElementById('task-tbody');

// Отображение статусов и приоритетов на русском
const STATUS_NAMES = {
  done: 'Выполнено',
  in_progress: 'В работе',
  pending: 'Ожидает'
};

const PRIORITY_NAMES = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий'
};

// ==========================================
// 2. Вспомогательные функции DOM (Шаг 3)
// ==========================================
function updateCounter() {
  if (counterBadge && tbody) {
    counterBadge.textContent = tbody.children.length;
  }
}

// TODO 3.1 - 3.4: Создание строки таблицы
function createTaskRow(item) {
  const tr = document.createElement('tr');
  tr.setAttribute('data-id', item.id);

  if (item.status === 'done') {
    tr.classList.add('done');
  }

  // Ячейка ID
  const idTd = document.createElement('td');
  idTd.textContent = item.id;
  tr.appendChild(idTd);

  // Ячейка Названия (БЕЗОПАСНО: только textContent!)
  const titleTd = document.createElement('td');
  titleTd.textContent = item.title;
  tr.appendChild(titleTd);

  // Ячейка Приоритета
  const priorityTd = document.createElement('td');
  priorityTd.textContent = PRIORITY_NAMES[item.priority] || item.priority;
  tr.appendChild(priorityTd);

  // Ячейка Статуса
  const statusTd = document.createElement('td');
  statusTd.textContent = STATUS_NAMES[item.status] || item.status;
  tr.appendChild(statusTd);

  // Ячейка Кнопок действий
  const actionsTd = document.createElement('td');

  // Кнопка "Выполнено"
  const doneBtn = document.createElement('button');
  doneBtn.className = 'btn btn-action';
  doneBtn.textContent = item.status === 'done' ? 'Вернуть' : 'Выполнено';
  doneBtn.setAttribute('title', item.status === 'done' ? 'Вернуть задачу в работу' : 'Отметить выполненной');
  doneBtn.addEventListener('click', () => onDoneClick(tr, doneBtn, statusTd));
  actionsTd.appendChild(doneBtn);

  // Кнопка "Удалить"
  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'btn btn-delete';
  deleteBtn.textContent = 'Удалить';
  deleteBtn.setAttribute('title', 'Удалить строку из таблицы');
  deleteBtn.addEventListener('click', () => onDeleteClick(tr));
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

// TODO 3.5: Переключение статуса выполнения
function onDoneClick(row, button, statusTd) {
  row.classList.toggle('done');
  const isDone = row.classList.contains('done');

  statusTd.textContent = isDone ? STATUS_NAMES.done : STATUS_NAMES.in_progress;
  button.textContent = isDone ? 'Вернуть' : 'Выполнено';
  button.setAttribute('title', isDone ? 'Вернуть в работу' : 'Отметить выполненным');
}

// TODO 3.6: Удаление строки с подтверждением
function onDeleteClick(row) {
  const isConfirmed = confirm('Вы действительно хотите удалить эту запись?');
  if (isConfirmed) {
    row.remove();
    updateCounter();
  }
}

// TODO 3.7: Добавление записи из формы в начало таблицы
function addNewTaskFromForm() {
  const newTask = {
    id: Date.now(), // генерация временного id
    title: titleInput.value.trim(),
    priority: prioritySelect.value,
    status: 'in_progress'
  };

  const row = createTaskRow(newTask);
  tbody.insertBefore(row, tbody.firstChild);

  form.reset();
  updateCounter();
}

// ==========================================
// 3. Обработка событий формы (Шаг 2)
// ==========================================
// TODO 2.1 и 2.2: Функция отправки формы
function onFormSubmit(event) {
  event.preventDefault(); // 2.1 Отмена перезагрузки

  const titleValue = titleInput.value.trim();

  // 2.2 Валидация длины названия
  if (titleValue.length < 3) {
    errorSpan.textContent = 'Название должно содержать не менее 3 символов';
    return;
  }

  errorSpan.textContent = '';
  addNewTaskFromForm();
}

// ==========================================
// 4. Асинхронные запросы и 4 состояния (Шаг 4)
// ==========================================
function renderState(state, message = '') {
  switch (state) {
    case 'loading':
      messageBox.textContent = 'Загрузка данных...';
      messageBox.style.backgroundColor = '#edf2f7';
      messageBox.style.color = '#4a5568';
      messageBox.hidden = false;
      loadBtn.disabled = true;
      break;

    case 'success':
      messageBox.hidden = true;
      loadBtn.disabled = false;
      break;

    case 'empty':
      messageBox.textContent = 'Задачи не найдены. Измените фильтр или текст поиска';
      messageBox.style.backgroundColor = '#feebc8';
      messageBox.style.color = '#7b341e';
      messageBox.hidden = false;
      loadBtn.disabled = false;
      break;

    case 'error':
      messageBox.textContent = `Ошибка: ${message}`;
      messageBox.style.backgroundColor = '#fed7d7';
      messageBox.style.color = '#9b2c2c';
      messageBox.hidden = false;
      loadBtn.disabled = false;
      break;
  }
}

async function loadTasks() {
  renderState('loading');
  tbody.innerHTML = '';

  try {
    const status = filterSelect.value;
    const q = searchInput.value.trim();

    const url = new URL('/api/tasks', window.location.origin);
    if (status) url.searchParams.set('status', status);
    if (q) url.searchParams.set('q', q);

    const response = await fetch(url.toString());

    if (!response.ok) {
      throw new Error(`Сервер вернул код ${response.status}`);
    }

    const data = await response.json();

    if (!data || data.length === 0) {
      renderState('empty');
      updateCounter();
      return;
    }

    data.forEach(item => {
      const row = createTaskRow(item);
      tbody.appendChild(row);
    });

    renderState('success');
    updateCounter();

  } catch (error) {
    console.error('Ошибка загрузки данных:', error);
    renderState('error', error.message);
  }
}

// ==========================================
// 5. Подключение слушателей (TODO 2.3)
// ==========================================
form.addEventListener('submit', onFormSubmit);
loadBtn.addEventListener('click', loadTasks);
filterSelect.addEventListener('change', loadTasks);
searchInput.addEventListener('input', loadTasks);

// Первичная загрузка при старте
loadTasks();
