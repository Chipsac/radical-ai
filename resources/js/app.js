import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;

Alpine.start();

// ---- Theme toggle (dark default) -----------------------------------------
const applyTheme = () => {
    const stored = localStorage.getItem('theme') || 'dark';
    document.documentElement.classList.toggle('dark', stored === 'dark');
};
applyTheme();

window.toggleTheme = () => {
    const next = (localStorage.getItem('theme') || 'dark') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    applyTheme();
};

// ---- Kanban drag-and-drop (tasks board + deal pipeline) -------------------
// Any element with [data-kanban-column] becomes a sortable list. Dropping a
// card into a new column PATCHes the URL in [data-update-url-template]
// (":id" replaced with the card's data-id) with {<data-field>: column value}.
const initKanban = () => {
    document.querySelectorAll('[data-kanban-column]').forEach((col) => {
        if (col._sortable) return;

        col._sortable = Sortable.create(col, {
            group: col.dataset.kanbanGroup || 'kanban',
            animation: 150,
            ghostClass: 'kanban-ghost',
            dragClass: 'kanban-dragging',
            onEnd: async (evt) => {
                const card = evt.item;
                const from = evt.from;
                const to = evt.to;
                if (from === to) return;

                const url = to.dataset.updateUrlTemplate.replace(':id', card.dataset.id);
                const field = to.dataset.field || 'status';
                const value = to.dataset.kanbanColumn;

                try {
                    const res = await window.axios.patch(url, { [field]: value });
                    if (!res.data.ok) throw new Error('Update rejected');
                    // Update the per-column card counters
                    [from, to].forEach((c) => {
                        const counter = document.querySelector(`[data-count-for="${c.dataset.kanbanColumn}"]`);
                        if (counter) counter.textContent = c.querySelectorAll('[data-id]').length;
                    });
                } catch (e) {
                    // Put the card back where it came from on failure
                    from.insertBefore(card, from.children[evt.oldIndex] || null);
                    console.error('Failed to persist move', e);
                }
            },
        });
    });
};

document.addEventListener('DOMContentLoaded', initKanban);
