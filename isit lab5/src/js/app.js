// ============================================================
// 1. Поиск элементов на странице по их ID
// ============================================================
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

// Словари для красивого отображения на русском
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

// ============================================================
// 2. Вспомогательные функции и работа с DOM (Шаг 3)
// ============================================================

// Функция пересчёта количества строк в таблице
function updateCounter() {
  if (counterBadge && tbody) {
    counterBadge.textContent = tbody.children.length;
  }
}

// TODO 3.1 - 3.4: Создание новой строки таблицы
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

  // Ячейка Названия (БЕЗОПАСНО: только через textContent от XSS-атак)
  const titleTd = document.createElement('td');
  titleTd.textContent = item.title;
  tr.appendChild(titleTd);

  // Ячейка Приоритета
  const priorityTd = document.createElement('td');
  priorityTd.textContent = PRIORITY_NAMES[item.priority] || item.priority || 'Средний';
  tr.appendChild(priorityTd);

  // Ячейка Статуса
  const statusTd = document.createElement('td');
  statusTd.className = 'status-cell';
  statusTd.textContent = STATUS_NAMES[item.status] || item.status || 'В работе';
  tr.appendChild(statusTd);

  // Ячейка Кнопок действий
  const actionsTd = document.createElement('td');

  // Кнопка "Выполнено"
  const doneBtn = document.createElement('button');
  doneBtn.className = 'btn btn-action';
  doneBtn.textContent = item.status === 'done' ? 'Вернуть' : 'Выполнено';
  doneBtn.setAttribute('title', item.status === 'done' ? 'Вернуть в работу' : 'Отметить выполненным');
  doneBtn.addEventListener('click', () => onDoneClick(tr, doneBtn, statusTd));
  actionsTd.appendChild(doneBtn);

  // Кнопка "Удалить"
  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'btn btn-delete';
  deleteBtn.textContent = 'Удалить';
  deleteBtn.setAttribute('title', 'Удалить задачу');
  deleteBtn.addEventListener('click', () => onDeleteClick(tr));
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

// TODO 3.5: Переключение выполнения
function onDoneClick(row, button, statusTd) {
  row.classList.toggle('done');
  const isDone = row.classList.contains('done');

  statusTd.textContent = isDone ? STATUS_NAMES.done : STATUS_NAMES.in_progress;
  button.textContent = isDone ? 'Вернуть' : 'Выполнено';
  button.setAttribute('title', isDone ? 'Вернуть в работу' : 'Отметить выполненным');
}

// TODO 3.6: Удаление строки с подтверждением
function onDeleteClick(row) {
  const isConfirmed = confirm('Вы уверены, что хотите удалить эту строку?');
  if (isConfirmed) {
    row.remove();
    updateCounter();
  }
}

// ============================================================
// 3. Обработка отправки формы (Шаг 2 и Шаг 3)
// ============================================================

// TODO 2.1, 2.2 и 3.7: Обработчик отправки формы
function onFormSubmit(event) {
  // 1. ОТМЕНЯЕМ перезагрузку страницы браузером (TODO 2.1)
  event.preventDefault();

  const titleValue = titleInput ? titleInput.value.trim() : '';

  // 2. Валидация: не менее 3 символов (TODO 2.2)
  if (titleValue.length < 3) {
    if (errorSpan) {
      errorSpan.textContent = 'Название должно содержать не менее 3 символов';
      errorSpan.style.color = 'red';
    }
    return; // Останавливаем выполнение, если текст слишком короткий
  }

  // Очищаем текст ошибки, если ввод корректный
  if (errorSpan) {
    errorSpan.textContent = '';
  }

  // 3. Создаем объект новой задачи
  const newTask = {
    id: Date.now().toString().slice(-4), // Короткий уникальный ID
    title: titleValue,
    priority: prioritySelect ? prioritySelect.value : 'medium',
    status: 'in_progress'
  };

  // 4. Генерируем DOM-строку и добавляем её в НАЧАЛО таблицы (TODO 3.7)
  const newRow = createTaskRow(newTask);
  if (tbody) {
    tbody.insertBefore(newRow, tbody.firstChild);
  }

  // 5. Очищаем форму и пересчитываем счётчик
  if (form) {
    form.reset();
  }
  updateCounter();
}

// ============================================================
// 4. Асинхронные запросы (Fetch API) и 4 состояния экрана (Шаг 4)
// ============================================================

function renderState(state, message = '') {
  if (!messageBox) return;

  switch (state) {
    case 'loading':
      messageBox.textContent = 'Загрузка данных...';
      messageBox.style.backgroundColor = '#edf2f7';
      messageBox.style.color = '#4a5568';
      messageBox.hidden = false;
      if (loadBtn) loadBtn.disabled = true;
      break;

    case 'success':
      messageBox.hidden = true;
      if (loadBtn) loadBtn.disabled = false;
      break;

    case 'empty':
      messageBox.textContent = 'Задачи не найдены. Измените фильтр или текст поиска';
      messageBox.style.backgroundColor = '#feebc8';
      messageBox.style.color = '#7b341e';
      messageBox.hidden = false;
      if (loadBtn) loadBtn.disabled = false;
      break;

    case 'error':
      messageBox.textContent = `Ошибка: ${message}`;
      messageBox.style.backgroundColor = '#fed7d7';
      messageBox.style.color = '#9b2c2c';
      messageBox.hidden = false;
      if (loadBtn) loadBtn.disabled = false;
      break;
  }
}

async function loadTasks() {
  renderState('loading');
  if (tbody) tbody.innerHTML = '';

  try {
    const status = filterSelect ? filterSelect.value : '';
    const q = searchInput ? searchInput.value.trim() : '';

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

// ============================================================
// 5. Подключение слушателей событий (TODO 2.3)
// ============================================================
if (form) {
  form.addEventListener('submit', onFormSubmit);
}

if (loadBtn) {
  loadBtn.addEventListener('click', loadTasks);
}

if (filterSelect) {
  filterSelect.addEventListener('change', loadTasks);
}

if (searchInput) {
  searchInput.addEventListener('input', loadTasks);
}

// Первичная загрузка при открытии страницы
loadTasks();


// Первичная загрузка при старте
loadTasks();
