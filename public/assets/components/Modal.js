/**
 * Basic modal wrapper. Emits `close` when the backdrop is clicked.
 */
export default {
    props: {
        title: { type: String, required: true },
    },
    emits: ['close'],
    template: /* html */ `
        <div class="modal-backdrop" @mousedown.self="$emit('close')">
            <div class="modal" role="dialog">
                <h2 style="color: var(--text); text-transform: none; letter-spacing: 0; margin-top: 0;">{{ title }}</h2>
                <slot />
            </div>
        </div>
    `,
};
