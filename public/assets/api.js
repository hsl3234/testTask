/** @type {Record<string, string>} */
const API_MESSAGE_RU = {
    'Missing Authorization header': 'Отсутствует заголовок Authorization',
    'Malformed Authorization header': 'Некорректный заголовок Authorization',
    'Empty bearer token': 'Пустой токен Bearer',
    'Invalid bearer token': 'Неверный токен Bearer',
    'Invalid login or password': 'Неверный логин или пароль',
    'Invalid or expired refresh token': 'Срок сессии истёк, войдите заново',
    Unauthorized: 'Нет доступа',
    'Resource not found': 'Ресурс не найден',
    'Product not found': 'Товар не найден',
    'Category not found': 'Категория не найден',
    'Route not found': 'Маршрут не найден',
    'Validation failed': 'Ошибка валидации',
    'No fields provided': 'Не переданы поля для обновления',
    'Invalid JSON body': 'Некорректное тело JSON',
    'JSON body must be an object': 'Тело JSON должно быть объектом',
    'Category has child categories': 'У категории есть дочерние категории',
    'Category has products': 'В категории есть товары',
    'Internal Server Error': 'Внутренняя ошибка сервера',
    'Category cannot be its own parent': 'Категория не может быть родителем самой себя',
    'Parent category not found': 'Родительская категория не найдена',
    'Cannot move a category under its own descendant': 'Нельзя перенести категорию в потомка',
};

/**
 * @param {string} message
 * @returns {string}
 */
function translateApiMessage(message) {
    return API_MESSAGE_RU[message] || message;
}

/**
 * @typedef {Object} ApiClient
 * @property {(path: string, query?: Record<string, any>) => Promise<any>} get
 * @property {(path: string, body: any) => Promise<any>} post
 * @property {(path: string, body: any) => Promise<any>} put
 * @property {(path: string) => Promise<void>} del
 */

/**
 * @typedef {Object} ApiAuth
 * @property {() => string} getAccessToken     Current access token (or empty).
 * @property {() => string} getRefreshToken    Current refresh token (or empty).
 * @property {(pair: { accessToken: string, refreshToken: string }) => void} onTokenRefreshed
 *                                              Persist a freshly rotated pair.
 * @property {() => void} onAuthFailed          Called when refresh fails — caller should drop session.
 */

/**
 * Create a small fetch-based API client with automatic refresh-on-401.
 *
 * On the first 401 from a protected endpoint the client posts the current
 * refresh token to `/auth/refresh`; on success it stores the new pair and
 * retries the original request once. On failure it calls `onAuthFailed` so
 * the host application can return the user to the login screen.
 *
 * @param {ApiAuth} auth Token storage hooks.
 * @returns {ApiClient}
 */
export function createApi(auth) {
    const base = window.__API_BASE__ || '/api';
    let refreshPromise = null;

    /**
     * Run a refresh round-trip, deduplicating concurrent attempts.
     *
     * @returns {Promise<boolean>} True on success, false otherwise.
     */
    async function refreshTokens() {
        if (refreshPromise) return refreshPromise;
        const refreshToken = auth.getRefreshToken();
        if (!refreshToken) return false;

        refreshPromise = (async () => {
            try {
                const response = await fetch(base + '/auth/refresh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ refreshToken }),
                });
                if (!response.ok) return false;
                const data = await response.json();
                auth.onTokenRefreshed({
                    accessToken: data.accessToken,
                    refreshToken: data.refreshToken,
                });
                return true;
            } catch (e) {
                return false;
            } finally {
                refreshPromise = null;
            }
        })();
        return refreshPromise;
    }

    /**
     * Build a fetch request from method + path + options.
     *
     * @param {string} method
     * @param {string} path
     * @param {{ body?: any, query?: Record<string, any> }} options
     * @returns {Promise<Response>}
     */
    function send(method, path, options) {
        const headers = { Accept: 'application/json' };
        const access = auth.getAccessToken();
        if (access) headers['Authorization'] = 'Bearer ' + access;
        if (options.body !== undefined) headers['Content-Type'] = 'application/json';
        let url = base + path;
        if (options.query) {
            const usp = new URLSearchParams();
            for (const [k, v] of Object.entries(options.query)) {
                if (v === null || v === undefined || v === '') continue;
                usp.append(k, String(v));
            }
            const qs = usp.toString();
            if (qs) url += '?' + qs;
        }
        return fetch(url, {
            method,
            headers,
            body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        });
    }

    /**
     * Execute an HTTP request, transparently refreshing the access token on 401.
     *
     * @param {string} method
     * @param {string} path
     * @param {{ body?: any, query?: Record<string, any> }} [options]
     * @returns {Promise<any>}
     */
    async function request(method, path, options = {}) {
        let response = await send(method, path, options);

        if (response.status === 401 && auth.getRefreshToken()) {
            const ok = await refreshTokens();
            if (ok) {
                response = await send(method, path, options);
            } else {
                auth.onAuthFailed();
            }
        }

        if (response.status === 204) return undefined;

        const text = await response.text();
        const data = text ? JSON.parse(text) : undefined;

        if (!response.ok) {
            const raw = data && data.error && data.error.message
                ? data.error.message
                : 'Ошибка HTTP ' + response.status;
            const message = translateApiMessage(raw);
            const err = new Error(message);
            err.status = response.status;
            err.details = data && data.error ? data.error : undefined;
            if (response.status === 401) auth.onAuthFailed();
            throw err;
        }
        return data;
    }

    return {
        get: (path, query) => request('GET', path, { query }),
        post: (path, body) => request('POST', path, { body }),
        put: (path, body) => request('PUT', path, { body }),
        del: (path) => request('DELETE', path),
    };
}
