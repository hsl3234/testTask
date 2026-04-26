import { createApp, ref, computed, onMounted } from 'vue';
import { createApi } from './api.js';
import ProductsPanel from './components/ProductsPanel.js';
import CategoriesPanel from './components/CategoriesPanel.js';
import LoginView from './components/LoginView.js';

const ACCESS_KEY = 'api_access_token';
const REFRESH_KEY = 'api_refresh_token';

/**
 * Root SPA component: login gating, token rotation, and switching between
 * the two main panels (products / categories).
 */
const App = {
    components: { ProductsPanel, CategoriesPanel, LoginView },
    setup() {
        /** @type {import('vue').Ref<string>} */
        const accessToken = ref(localStorage.getItem(ACCESS_KEY) || '');
        /** @type {import('vue').Ref<string>} */
        const refreshToken = ref(localStorage.getItem(REFRESH_KEY) || '');

        const isAuthenticated = computed(() => Boolean(accessToken.value));

        /** @type {import('vue').Ref<'products'|'categories'>} */
        const view = ref('products');

        /** @type {import('vue').Ref<Array<{id:number, parentId:number|null, name:string, path:string}>>} */
        const categories = ref([]);
        const error = ref('');

        /**
         * Persist a freshly issued or rotated token pair.
         *
         * @param {{ accessToken: string, refreshToken: string }} pair
         * @returns {void}
         */
        function storeTokens(pair) {
            accessToken.value = pair.accessToken;
            refreshToken.value = pair.refreshToken;
            localStorage.setItem(ACCESS_KEY, pair.accessToken);
            localStorage.setItem(REFRESH_KEY, pair.refreshToken);
        }

        /**
         * Drop the in-memory + persisted session.
         *
         * @returns {void}
         */
        function clearTokens() {
            accessToken.value = '';
            refreshToken.value = '';
            localStorage.removeItem(ACCESS_KEY);
            localStorage.removeItem(REFRESH_KEY);
            categories.value = [];
        }

        const api = createApi({
            getAccessToken: () => accessToken.value,
            getRefreshToken: () => refreshToken.value,
            onTokenRefreshed: storeTokens,
            onAuthFailed: clearTokens,
        });

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
         * Handle the `authenticated` event from {@link LoginView}.
         *
         * @param {{ accessToken: string, refreshToken: string }} pair
         */
        function onAuthenticated(pair) {
            storeTokens(pair);
            error.value = '';
            reloadCategories();
        }

        /**
         * Drop the session and return to the login screen.
         *
         * @returns {void}
         */
        function logout() {
            clearTokens();
        }

        onMounted(() => {
            if (isAuthenticated.value) {
                reloadCategories();
            }
        });

        return {
            isAuthenticated,
            view,
            categories,
            reloadCategories,
            onAuthenticated,
            logout,
            error,
            api,
        };
    },
    template: /* html */ `
        <LoginView v-if="!isAuthenticated" @authenticated="onAuthenticated" />
        <div v-else class="layout">
            <aside class="sidebar">
                <h1>Админка</h1>
                <h2>Разделы</h2>
                <button class="secondary" style="width:100%; margin-bottom: 8px; text-align:left;"
                    :style="view === 'products' ? 'background: var(--panel-alt)' : ''"
                    @click="view = 'products'">Товары</button>
                <button class="secondary" style="width:100%; text-align:left;"
                    :style="view === 'categories' ? 'background: var(--panel-alt)' : ''"
                    @click="view = 'categories'">Категории</button>

                <button class="secondary logout" @click="logout">Выйти</button>

                <div v-if="error" class="error" style="margin-top: 16px;">{{ error }}</div>
            </aside>
            <main class="main">
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
            </main>
        </div>
    `,
};

createApp(App).mount('#app');
