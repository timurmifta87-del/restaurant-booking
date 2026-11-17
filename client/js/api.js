const TOKEN_KEY = 'token';
const USER_KEY = 'user';

const auth = {
    token: () => localStorage.getItem(TOKEN_KEY),
    user: () => JSON.parse(localStorage.getItem(USER_KEY) || 'null'),
    isAdmin: () => auth.user()?.role === 'admin',
    save(token, user) {
        localStorage.setItem(TOKEN_KEY, token);
        localStorage.setItem(USER_KEY, JSON.stringify(user));
    },
    clear() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    },
};

class ApiError extends Error {
    constructor(status, message, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

/**
 * Общая функция запросов к API.
 * body: объект (уйдёт как JSON) или FormData (для загрузки файлов).
 */
async function api(path, { method = 'GET', body = null, query = null } = {}) {
    const url = new URL(API_URL + path);
    if (query) {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value);
        });
    }

    const headers = { Accept: 'application/json' };
    const token = auth.token();
    if (token) headers.Authorization = `Bearer ${token}`;

    let payload = null;
    if (body instanceof FormData) {
        payload = body; // Content-Type с границей выставит браузер
    } else if (body !== null) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(url, { method, headers, body: payload });
    } catch (e) {
        throw new ApiError(0, 'Сервер недоступен. Проверьте, что запущен php artisan serve.');
    }

    if (response.status === 204) return null;

    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
        auth.clear();
        if (!location.pathname.endsWith('login.html') && !location.pathname.endsWith('register.html')) {
            location.href = 'login.html';
        }
        throw new ApiError(401, data.message || 'Требуется авторизация');
    }

    if (response.status === 422) {
        throw new ApiError(422, data.message || 'Ошибка валидации', data.errors || {});
    }

    if (!response.ok) {
        const fallback = response.status >= 500 ? 'Внутренняя ошибка сервера' : 'Ошибка запроса';
        throw new ApiError(response.status, data.message || fallback);
    }

    return data;
}
