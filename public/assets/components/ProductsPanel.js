import { ref, reactive, computed, watch, onMounted } from 'vue';
import { formatFormApiError } from '../i18n.js';
import Modal from './Modal.js';

/**
 * @typedef {Object} Category
 * @property {number}      id
 * @property {number|null} parent_id
 * @property {string}      name
 * @property {string}      path
 */

/**
 * @typedef {Object} Product
 * @property {number}        id
 * @property {string}        name
 * @property {string|null}   content
 * @property {string}        price
 * @property {boolean}       in_stock
 * @property {{id:number, name:string, path:string}} category
 */

/**
 * Panel for listing and managing products.
 */
export default {
    components: { Modal },
    props: {
        api: { type: Object, required: true },
        categories: { type: Array, required: true },
    },
    emits: ['categories-changed'],
    setup(props) {
        const filters = reactive({
            category_id: '',
            in_stock: '',
            page: 1,
            per_page: 10,
        });

        /** @type {import('vue').Ref<Product[]>} */
        const items = ref([]);
        const meta = ref({ page: 1, per_page: 10, total: 0, aggregates: { in_stock_count: 0, in_stock_total_price: '0.00' } });
        const loading = ref(false);
        const error = ref('');

        /** @type {import('vue').Ref<null | Partial<Product & {category_id:number}>>} */
        const editing = ref(null);
        const editingError = ref('');

        const totalPages = computed(() =>
            meta.value.per_page > 0
                ? Math.max(1, Math.ceil(meta.value.total / meta.value.per_page))
                : 1,
        );

        /**
         * Reload the current page of products with the current filters.
         *
         * @returns {Promise<void>}
         */
        async function reload() {
            loading.value = true;
            error.value = '';
            try {
                const response = await props.api.get('/products', {
                    category_id: filters.category_id || undefined,
                    in_stock: filters.in_stock === '' ? undefined : filters.in_stock,
                    page: filters.page,
                    per_page: filters.per_page,
                });
                items.value = response.data;
                meta.value = response.meta;
            } catch (e) {
                error.value = e.message || 'Не удалось загрузить товары';
            } finally {
                loading.value = false;
            }
        }

        watch(() => [filters.category_id, filters.in_stock, filters.per_page], () => {
            filters.page = 1;
            reload();
        });
        watch(() => filters.page, reload);

        /**
         * Open the modal to create a new product.
         *
         * @returns {void}
         */
        function openCreate() {
            editing.value = {
                id: 0,
                name: '',
                content: '',
                price: '0.00',
                in_stock: true,
                category_id: props.categories[0]?.id ?? 0,
            };
            editingError.value = '';
        }

        /**
         * Open the modal to edit an existing product.
         *
         * @param {Product} product Product to edit.
         * @returns {void}
         */
        function openEdit(product) {
            editing.value = {
                id: product.id,
                name: product.name,
                content: product.content ?? '',
                price: product.price,
                in_stock: product.in_stock,
                category_id: product.category.id,
            };
            editingError.value = '';
        }

        /**
         * Persist the currently edited product (create or update).
         *
         * @returns {Promise<void>}
         */
        async function save() {
            const form = editing.value;
            if (!form) return;
            editingError.value = '';
            const payload = {
                name: form.name,
                content: form.content === '' ? null : form.content,
                price: Number(form.price),
                in_stock: !!form.in_stock,
                category_id: Number(form.category_id),
            };
            try {
                if (form.id) {
                    await props.api.put('/products/' + form.id, payload);
                } else {
                    await props.api.post('/products', payload);
                }
                editing.value = null;
                reload();
            } catch (e) {
                editingError.value = formatFormApiError(e);
            }
        }

        /**
         * Delete a product after confirmation.
         *
         * @param {Product} product Product to remove.
         * @returns {Promise<void>}
         */
        async function remove(product) {
            if (!window.confirm(`Удалить товар «${product.name}»?`)) return;
            try {
                await props.api.del('/products/' + product.id);
                reload();
            } catch (e) {
                error.value = e.message || 'Не удалось удалить';
            }
        }

        onMounted(reload);

        return {
            filters, items, meta, loading, error,
            editing, editingError,
            totalPages,
            reload, openCreate, openEdit, save, remove,
        };
    },
    template: /* html */ `
        <section>
            <div class="toolbar">
                <h1 style="margin:0;">Товары</h1>
                <div class="actions">
                    <button class="secondary" @click="reload" :disabled="loading">Обновить</button>
                    <button @click="openCreate">+ Новый товар</button>
                </div>
            </div>

            <div class="filters">
                <select v-model="filters.category_id">
                    <option value="">Все категории</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.path }} {{ c.name }}
                    </option>
                </select>
                <select v-model="filters.in_stock">
                    <option value="">Любая доступность</option>
                    <option value="1">В наличии</option>
                    <option value="0">Нет в наличии</option>
                </select>
                <select v-model.number="filters.per_page">
                    <option :value="10">10 / стр.</option>
                    <option :value="20">20 / стр.</option>
                    <option :value="50">50 / стр.</option>
                </select>
            </div>

            <div class="aggregate">
                <div class="card">
                    <div class="label">В наличии, шт. (с учётом фильтров)</div>
                    <div class="value">{{ meta.aggregates.in_stock_count }}</div>
                </div>
                <div class="card">
                    <div class="label">Сумма в наличии (с учётом фильтров)</div>
                    <div class="value">{{ meta.aggregates.in_stock_total_price }}</div>
                </div>
            </div>

            <div v-if="error" class="error">{{ error }}</div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Цена</th>
                        <th>Категория</th>
                        <th>Наличие</th>
                        <th style="width: 140px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!items.length && !loading">
                        <td colspan="6" class="muted">Нет товаров по заданным фильтрам.</td>
                    </tr>
                    <tr v-for="p in items" :key="p.id">
                        <td><strong>{{ p.name }}</strong></td>
                        <td class="muted">{{ p.content }}</td>
                        <td>{{ p.price }}</td>
                        <td>{{ p.category.name }}</td>
                        <td>
                            <span class="badge" :class="p.in_stock ? 'ok' : 'no'">
                                {{ p.in_stock ? 'В наличии' : 'Нет' }}
                            </span>
                        </td>
                        <td>
                            <button class="secondary" @click="openEdit(p)">Изм.</button>
                            <button class="danger" @click="remove(p)">Удал.</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pagination">
                <button class="secondary" :disabled="filters.page <= 1" @click="filters.page = 1">«</button>
                <button class="secondary" :disabled="filters.page <= 1" @click="filters.page--">‹</button>
                <span class="page-info">Стр. {{ meta.page }} / {{ totalPages }} · всего {{ meta.total }}</span>
                <button class="secondary" :disabled="filters.page >= totalPages" @click="filters.page++">›</button>
                <button class="secondary" :disabled="filters.page >= totalPages" @click="filters.page = totalPages">»</button>
            </div>

            <Modal v-if="editing" :title="editing.id ? 'Редактирование товара' : 'Новый товар'" @close="editing = null">
                <div class="form-row"><label>Название</label><input v-model="editing.name" /></div>
                <div class="form-row"><label>Описание</label><textarea v-model="editing.content" rows="3"></textarea></div>
                <div class="form-row"><label>Цена</label><input type="number" min="0" step="0.01" v-model="editing.price" /></div>
                <div class="form-row">
                    <label>Категория</label>
                    <select v-model.number="editing.category_id">
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.path }} {{ c.name }}</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>В наличии</label>
                    <label><input type="checkbox" v-model="editing.in_stock" /> Доступен</label>
                </div>
                <div v-if="editingError" class="error">{{ editingError }}</div>
                <div class="actions">
                    <button class="secondary" @click="editing = null">Отмена</button>
                    <button @click="save">Сохранить</button>
                </div>
            </Modal>
        </section>
    `,
};

