В расширенной версии мок-сервер инициализируется данными и поддерживает POST и DELETE:
(function () {
  let db = [
    { id: 1, title: 'Сверстать главную страницу', status: 'done', priority: 'high' },
    { id: 2, title: 'Подключить форму обратной связи', status: 'in_progress', priority: 'medium' },
    { id: 3, title: 'Реализовать валидацию данных', status: 'pending', priority: 'low' },
    { id: 4, title: 'Настроить асинхронные запросы REST API', status: 'in_progress', priority: 'high' },
    { id: 5, title: 'Оформить отчёт по лабораторной работе', status: 'pending', priority: 'medium' }
  ];

  window.MockApi = {
    setErrorRate(rate) { window._mockErrorRate = rate; }
  };

  const originalFetch = window.fetch;

  window.fetch = async function (url, options = {}) {
    const parsed = new URL(url, window.location.origin);
    const path = parsed.pathname;
    const method = (options.method || 'GET').toUpperCase();

    await new Promise(r => setTimeout(r, 250));

    if (window._mockErrorRate && Math.random() < window._mockErrorRate) {
      return { ok: false, status: 500, json: async () => ({ error: 'Сбой сервера' }) };
    }

    // GET /api/tasks
    if (method === 'GET' && path === '/api/tasks') {
      let result = [...db];
      const status = parsed.searchParams.get('status');
      const q = parsed.searchParams.get('q');
      if (status) result = result.filter(t => t.status === status);
      if (q) result = result.filter(t => t.title.toLowerCase().includes(q.toLowerCase()));
      return { ok: true, status: 200, json: async () => result };
    }

    // POST /api/tasks
    if (method === 'POST' && path === '/api/tasks') {
      const data = JSON.parse(options.body || '{}');
      if (!data.title || data.title.length < 3) {
        return { ok: false, status: 422, json: async () => ({ error: 'Некорректные данные' }) };
      }
      const newItem = { id: db.length ? Math.max(...db.map(x => x.id)) + 1 : 1, ...data };
      db.push(newItem);
      return { ok: true, status: 201, json: async () => newItem };
    }

    // DELETE /api/tasks/:id
    if (method === 'DELETE' && path.startsWith('/api/tasks/')) {
      const id = parseInt(path.split('/').pop(), 10);
      db = db.filter(x => x.id !== id);
      return { ok: true, status: 200, json: async () => ({ success: true }) };
    }

    return originalFetch(url, options);
  };
})();

