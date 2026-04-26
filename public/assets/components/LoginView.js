import { reactive, ref } from 'vue';

/**
 * Centred login form. Submits credentials to `/api/auth/login` via a plain
 * fetch (the {@link createApi} client is reserved for authenticated calls)
 * and emits the resulting token pair to the parent.
 *
 * @typedef {Object} TokenPair
 * @property {string} accessToken
 * @property {string} refreshToken
 */
const LoginView = {
    emits: ['authenticated'],
    setup(_, { emit }) {
        const form = reactive({ login: '', password: '' });
        const loading = ref(false);
        const error = ref('');

        /**
         * Send the form to the API and emit the resulting token pair.
         *
         * @returns {Promise<void>}
         */
        async function submit() {
            error.value = '';
            loading.value = true;
            try {
                const base = window.__API_BASE__ || '/api';
                const response = await fetch(base + '/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ login: form.login, password: form.password }),
                });
                const text = await response.text();
                const data = text ? JSON.parse(text) : undefined;
                if (!response.ok) {
                    const message = (data && data.error && data.error.message) || 'Ошибка входа';
                    error.value = message === 'Invalid login or password'
                        ? 'Неверный логин или пароль'
                        : message;
                    return;
                }
                emit('authenticated', {
                    accessToken: data.accessToken,
                    refreshToken: data.refreshToken,
                });
            } catch (e) {
                error.value = e.message || 'Ошибка сети';
            } finally {
                loading.value = false;
            }
        }

        return { form, loading, error, submit };
    },
    template: /* html */ `
        <div class="login-screen">
            <form class="login-card" @submit.prevent="submit">
                <h1>Вход в админку</h1>
                <p class="muted">Введите логин и пароль администратора.</p>
                <label class="login-field">
                    <span>Логин</span>
                    <input v-model="form.login" autocomplete="username" autofocus required />
                </label>
                <label class="login-field">
                    <span>Пароль</span>
                    <input v-model="form.password" type="password" autocomplete="current-password" required />
                </label>
                <button type="submit" :disabled="loading || !form.login || !form.password">
                    {{ loading ? 'Входим…' : 'Войти' }}
                </button>
                <div v-if="error" class="error">{{ error }}</div>
            </form>
        </div>
    `,
};

export default LoginView;
