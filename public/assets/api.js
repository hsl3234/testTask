/** @type {Record<string, string>} */
const API_MESSAGE_RU = {
    'Missing Authorization header': 'Отсутствует заголовок Authorization',
    'Malformed Authorization header': 'Некорректный заголовок Authorization',
    'Empty bearer token': 'Пустой токен Bearer',
    'Invalid bearer token': 'Неверный токен Bearer',
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
 * Create a small fetch-based API client that always sends the current Bearer token.
 *
 * @param {() => string} tokenGetter Function returning the current bearer token.
 * @returns {ApiClient} Configured client.
 */
export function createApi(tokenGetter) {
    const base = window.__API_BASE__ || '/api';

    /**
     * Execute an HTTP request and parse the JSON response.
     *
     * @param {string} method HTTP method.
     * @param {string} path   Path relative to the API base.
     * @param {{ body?: any, query?: Record<string, any> }} [options] Optional body/query.
     * @returns {Promise<any>} Parsed response body (or undefined for 204).
     */
    async function request(method, path, options = {}) {
        const token = tokenGetter();
        const headers = { 'Accept': 'application/json' };
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }
        if (options.body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }
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

        const response = await fetch(url, {
            method,
            headers,
            body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        });

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
