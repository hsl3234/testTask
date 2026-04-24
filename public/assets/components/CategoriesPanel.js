import { ref, computed } from 'vue';
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
 * Recursive tree node used inside {@link CategoriesPanel}.
 */
const CategoryNode = {
    name: 'CategoryNode',
    props: {
        node: { type: Object, required: true },
    },
    emits: ['create-child', 'edit', 'delete'],
    template: /* html */ `
        <li>
            <div class="category-row">
                <span class="name">
                    <strong>{{ node.name }}</strong>
                    <span class="muted" style="margin-left:8px; font-size: 12px;">{{ node.path }}</span>
                </span>
                <div class="actions" style="display:flex; gap:6px;">
                    <button class="secondary" @click="$emit('create-child', node.id)">+ Подкат.</button>
                    <button class="secondary" @click="$emit('edit', node)">Изм.</button>
                    <button class="danger" @click="$emit('delete', node)">Удал.</button>
                </div>
            </div>
            <ul v-if="node.children.length">
                <CategoryNode
                    v-for="c in node.children"
                    :key="c.id"
                    :node="c"
                    @create-child="(id) => $emit('create-child', id)"
                    @edit="(cat) => $emit('edit', cat)"
                    @delete="(cat) => $emit('delete', cat)"
                />
            </ul>
        </li>
    `,
};

/**
 * Build a nested tree structure from the flat category list.
 *
 * @param {Category[]} flat Flat list sorted by path.
 * @returns {Array<Category & { children: Array }>}  Tree where each node has a `children` array.
 */
function toTree(flat) {
    const byId = {};
    for (const c of flat) byId[c.id] = { ...c, children: [] };
    const roots = [];
    for (const c of Object.values(byId)) {
        if (c.parent_id && byId[c.parent_id]) {
            byId[c.parent_id].children.push(c);
        } else {
            roots.push(c);
        }
    }
    return roots;
}

/**
 * Flatten validation errors (if any) into a readable single-line message.
 *
 * @param {{ message?: string, details?: { errors?: Record<string,string> } }} e Error thrown by the API client.
 * @returns {string} Human-readable message.
 */
/**
 * Panel for listing and managing the category tree.
 */
export default {
    components: { Modal, CategoryNode },
    props: {
        api: { type: Object, required: true },
        categories: { type: Array, required: true },
    },
    emits: ['changed'],
    setup(props, { emit }) {
        const error = ref('');

        /** @type {import('vue').Ref<null | { id: number, name: string, parent_id: number|null }>} */
        const editing = ref(null);
        const editingError = ref('');

        const tree = computed(() => toTree(props.categories));

        /**
         * Open the modal for creating a new category.
         *
         * @param {number|null} parentId Parent id or null for a root category.
         * @returns {void}
         */
        function openCreate(parentId = null) {
            editing.value = { id: 0, name: '', parent_id: parentId };
            editingError.value = '';
        }

        /**
         * Open the modal for editing an existing category.
         *
         * @param {Category} category Category to edit.
         * @returns {void}
         */
        function openEdit(category) {
            editing.value = { id: category.id, name: category.name, parent_id: category.parent_id };
            editingError.value = '';
        }

        /**
         * Persist the edited category (create or update).
         *
         * @returns {Promise<void>}
         */
        async function save() {
            const form = editing.value;
            if (!form) return;
            editingError.value = '';
            const payload = { name: form.name, parent_id: form.parent_id || null };
            try {
                if (form.id) {
                    await props.api.put('/categories/' + form.id, payload);
                } else {
                    await props.api.post('/categories', payload);
                }
                editing.value = null;
                emit('changed');
            } catch (e) {
                editingError.value = formatFormApiError(e);
            }
        }

        /**
         * Delete a category after confirmation.
         *
         * @param {Category} category Category to delete.
         * @returns {Promise<void>}
         */
        async function remove(category) {
            if (!window.confirm(`Удалить категорию «${category.name}»?`)) return;
            try {
                await props.api.del('/categories/' + category.id);
                emit('changed');
            } catch (e) {
                error.value = formatFormApiError(e);
            }
        }

        return { error, editing, editingError, tree, openCreate, openEdit, save, remove };
    },
    template: /* html */ `
        <section>
            <div class="toolbar">
                <h1 style="margin:0;">Категории</h1>
                <div class="actions">
                    <button @click="openCreate(null)">+ Корневая категория</button>
                </div>
            </div>

            <div v-if="error" class="error">{{ error }}</div>

            <ul class="category-tree">
                <CategoryNode
                    v-for="node in tree"
                    :key="node.id"
                    :node="node"
                    @create-child="openCreate"
                    @edit="openEdit"
                    @delete="remove"
                />
            </ul>

            <Modal v-if="editing" :title="editing.id ? 'Редактирование категории' : 'Новая категория'" @close="editing = null">
                <div class="form-row"><label>Название</label><input v-model="editing.name" /></div>
                <div class="form-row">
                    <label>Родитель</label>
                    <select v-model="editing.parent_id">
                        <option :value="null">— Корень —</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id" :disabled="c.id === editing.id">
                            {{ c.path }} {{ c.name }}
                        </option>
                    </select>
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
