(function () {
  const originalFetch = window.fetch;
  let delayMin = 200;
  let delayMax = 500;
  let errorRate = 0;
  let networkDown = false;

  window.MockApi = {
    setDelay(min, max) {
      delayMin = min;
      delayMax = max;
    },
    setErrorRate(rate) {
      errorRate = rate;
    },
    setNetworkDown(isDown) {
      networkDown = isDown;
    }
  };

  window.fetch = async function (url, options = {}) {
    const parsedUrl = new URL(url, window.location.origin);
    const pathname = parsedUrl.pathname;
    const method = (options.method || 'GET').toUpperCase();
    const routeKey = `${method} ${pathname}`;

    // Имитация задержки сети
    const delay = Math.floor(Math.random() * (delayMax - delayMin + 1)) + delayMin;
    await new Promise(resolve => setTimeout(resolve, delay));

    // Проверка обрыва сети
    if (networkDown) {
      throw new TypeError('Failed to fetch (Сеть недоступна)');
    }

    // Имитация случайных серверных сбоев
    if (Math.random() < errorRate) {
      return {
        ok: false,
        status: 500,
        statusText: 'Internal Server Error',
        json: async () => ({ error: 'Внутренняя ошибка учебного сервера' })
      };
    }

    // Поиск совпадения в таблице ROUTES
    if (typeof ROUTES !== 'undefined' && ROUTES[routeKey]) {
      const queryParams = Object.fromEntries(parsedUrl.searchParams.entries());
      let bodyData = null;
      if (options.body) {
        try { bodyData = JSON.parse(options.body); } catch (e) { bodyData = options.body; }
      }

      const responseData = ROUTES[routeKey](queryParams, bodyData);

      return {
        ok: true,
        status: 200,
        statusText: 'OK',
        json: async () => responseData
      };
    }

    // Если маршрут не зарегистрирован
    if (pathname.startsWith('/api/')) {
      return {
        ok: false,
        status: 404,
        statusText: 'Not Found',
        json: async () => ({ error: `Маршрут ${routeKey} не найден в ROUTES` })
      };
    }

    return originalFetch(url, options);
  };
})();

