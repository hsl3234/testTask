import { createApp, ref, reactive, computed, onMounted, watch } from 'vue';
import { createApi } from './api.js';
import ProductsPanel from './components/ProductsPanel.js';
import CategoriesPanel from './components/CategoriesPanel.js';

/**
 * Root SPA component: token handling, side navigation, and routing between
 * the two main panels (products / categories).
 */
const App = {
    components: { ProductsPanel, CategoriesPanel },
    setup() {
        /** @type {import('vue').Ref<string>} */
        const token = ref(localStorage.getItem('api_token') || '');
        const api = createApi(() => token.value);

        /** @type {import('vue').Ref<'products'|'categories'>} */
        const view = ref('products');

        /** @type {import('vue').Ref<Array<{id:number, parentId:number|null, name:string, path:string}>>} */
        const categories = ref([]);
        const error = ref('');

        /**
         * Fetch the flat list of categories (shared across both panels).
         *
         * @returns {Promise<void>}
         */
        async function reloadCategories() {
            error.value = '';
            try {
                categories.value = await api.get('/categories');
            } catch (e) {
                error.value = e.message || 'Не удалось загрузить категории';
            }
        }

        /**
         * Persist the Bearer token to localStorage and reload categories.
         *
         * @param {string} value New token value.
         * @returns {void}
         */
        function setToken(value) {
            token.value = value;
            localStorage.setItem('api_token', value);
            reloadCategories();
        }

        onMounted(() => {
            if (token.value) {
                reloadCategories();
            }
        });

        return { token, setToken, view, categories, reloadCategories, error, api };
    },
    template: /* html */ `
        <div class="layout">
            <aside class="sidebar">
                <h1>Админка</h1>
                <div class="token-box">
                    <input
                        type="password"
                        placeholder="Токен Bearer"
                        :value="token"
                        @change="e => setToken(e.target.value)"
                    />
                </div>
                <div v-if="!token" class="muted" style="font-size: 13px;">
                    Введите API-токен. Демо-токен из сида:
                    <code>demo-token-please-change</code>.
                </div>

                <h2>Разделы</h2>
                <button class="secondary" style="width:100%; margin-bottom: 8px; text-align:left;"
                    :style="view === 'products' ? 'background: var(--panel-alt)' : ''"
                    @click="view = 'products'">Товары</button>
                <button class="secondary" style="width:100%; text-align:left;"
                    :style="view === 'categories' ? 'background: var(--panel-alt)' : ''"
                    @click="view = 'categories'">Категории</button>

                <div v-if="error" class="error" style="margin-top: 16px;">{{ error }}</div>
            </aside>
            <main class="main">
                <div v-if="!token" class="muted" style="padding: 40px; text-align: center; font-size: 15px;">
                    Введите валидный API-токен в боковой панели, чтобы начать.
                </div>
                <template v-else>
                    <ProductsPanel
                        v-if="view === 'products'"
                        :api="api"
                        :categories="categories"
                        @categories-changed="reloadCategories"
                    />
                    <CategoriesPanel
                        v-else
                        :api="api"
                        :categories="categories"
                        @changed="reloadCategories"
                    />
                </template>
            </main>
        </div>
    `,
};

createApp(App).mount('#app');
