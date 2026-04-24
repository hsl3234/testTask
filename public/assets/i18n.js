/** @type {Record<string, string>} */
export const FIELD_RU = {
    name: 'Название',
    content: 'Описание',
    price: 'Цена',
    in_stock: 'Наличие',
    category_id: 'Категория',
    parent_id: 'Родитель',
    page: 'Страница',
    per_page: 'На странице',
};

/** @type {Record<string, string>} */
const REASON_RU = {
    'is required': 'обязательное поле',
    'must be a non-empty string up to 255 characters': 'непустая строка до 255 символов',
    'must be a string or null': 'строка или пусто',
    'must be a non-negative number': 'неотрицательное число',
    'must be a boolean': 'логическое значение (да/нет)',
    'must be a positive integer': 'положительное целое число',
    'category does not exist': 'категория не существует',
    'must be a positive integer or null': 'положительное целое или пусто',
    'cannot be the category itself': 'нельзя указать саму категорию',
    'parent category does not exist': 'родительская категория не существует',
    'must be a string': 'должно быть строкой',
    'must be 1..191 characters': 'от 1 до 191 символа',
    'must be 0/1/true/false': 'допустимо: 0, 1, true, false',
};

/**
 * @param {{ message?: string, details?: { errors?: Record<string,string> } }} e
 * @returns {string}
 */
export function formatFormApiError(e) {
    if (e?.details?.errors) {
        const parts = Object.entries(e.details.errors).map(([k, v]) => {
            const field = FIELD_RU[k] || k;
            const reason = REASON_RU[v] || v;
            return `${field}: ${reason}`;
        });
        return `${e.message}: ${parts.join(', ')}`;
    }
    return e?.message || 'Запрос не выполнен';
}
