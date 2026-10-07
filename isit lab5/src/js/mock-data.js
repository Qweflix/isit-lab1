// Начальный массив данных
const tasks = [
  { id: 1, title: 'Сверстать главную страницу приложения', status: 'done', priority: 'high' },
  { id: 2, title: 'Подключить форму обратной связи', status: 'in_progress', priority: 'medium' },
  { id: 3, title: 'Реализовать валидацию полей ввода', status: 'pending', priority: 'low' },
  { id: 4, title: 'Настроить асинхронные запросы Fetch API', status: 'in_progress', priority: 'high' },
  { id: 5, title: 'Написать отчёт и подготовить скриншоты', status: 'pending', priority: 'medium' }
];

// Вспомогательная функция фильтрации
function filterList(list, query) {
  let result = [...list];

  if (query.status) {
    result = result.filter(item => item.status === query.status);
  }

  if (query.q) {
    const qLower = query.q.toLowerCase();
    result = result.filter(item => item.title.toLowerCase().includes(qLower));
  }

  return result;
}

// Таблица маршрутов (ROUTES)
const ROUTES = {
  'GET /api/tasks': function (query) {
    return filterList(tasks, query);
  }
};
