В расширенной версии вызовы идут через API.createTask и API.deleteTask:
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

function updateCounter() {
  counterBadge.textContent = tbody.children.length;
}

function createTaskRow(item) {
  const tr = document.createElement('tr');
  tr.setAttribute('data-id', item.id);
  if (item.status === 'done') tr.classList.add('done');

  tr.innerHTML = `
    <td>${item.id}</td>
    <td>${item.title}</td>
    <td>${item.priority}</td>
    <td class="status-cell">${item.status === 'done' ? 'Выполнено' : 'В работе'}</td>
    <td>
      <button class="btn btn-action done-btn">${item.status === 'done' ? 'Вернуть' : 'Выполнено'}</button>
      <button class="btn btn-delete del-btn">Удалить</button>
    </td>
  `;

  tr.querySelector('.done-btn').addEventListener('click', () => {
    tr.classList.toggle('done');
    const isDone = tr.classList.contains('done');
    tr.querySelector('.status-cell').textContent = isDone ? 'Выполнено' : 'В работе';
    tr.querySelector('.done-btn').textContent = isDone ? 'Вернуть' : 'Выполнено';
  });

  tr.querySelector('.del-btn').addEventListener('click', async () => {
    if (confirm('Удалить с сервера?')) {
      await API.deleteTask(item.id);
      tr.remove();
      updateCounter();
    }
  });

  return tr;
}

async function renderList() {
  messageBox.hidden = true;
  tbody.innerHTML = '';
  try {
    const list = await API.getTasks({
      status: filterSelect.value,
      q: searchInput.value.trim()
    });
    if (list.length === 0) {
      messageBox.textContent = 'Ничего не найдено';
      messageBox.hidden = false;
    } else {
      list.forEach(i => tbody.appendChild(createTaskRow(i)));
    }
  } catch (err) {
    messageBox.textContent = err.message;
    messageBox.hidden = false;
  }
  updateCounter();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const title = titleInput.value.trim();
  if (title.length < 3) {
    errorSpan.textContent = 'Минимум 3 символа';
    return;
  }
  errorSpan.textContent = '';
  try {
    const created = await API.createTask({
      title,
      priority: prioritySelect.value,
      status: 'in_progress'
    });
    tbody.insertBefore(createTaskRow(created), tbody.firstChild);
    form.reset();
    updateCounter();
  } catch (err) {
    alert(err.message);
  }
});

loadBtn.addEventListener('click', renderList);
filterSelect.addEventListener('change', renderList);
searchInput.addEventListener('input', renderList);

renderList();

