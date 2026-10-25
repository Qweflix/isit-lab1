// Модуль инкапсулирует вызовы сетевого API
const API = {
  async getTasks(params = {}) {
    const url = new URL('/api/tasks', window.location.origin);
    if (params.status) url.searchParams.set('status', params.status);
    if (params.q) url.searchParams.set('q', params.q);

    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`Ошибка получения задач: HTTP ${res.status}`);
    return await res.json();
  },

  async createTask(taskData) {
    const res = await fetch('/api/tasks', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(taskData)
    });
    if (!res.ok) throw new Error(`Ошибка создания: HTTP ${res.status}`);
    return await res.json();
  },

  async deleteTask(id) {
    const res = await fetch(`/api/tasks/${id}`, {
      method: 'DELETE'
    });
    if (!res.ok) throw new Error(`Ошибка удаления: HTTP ${res.status}`);
    return await res.json();
  }
};
