import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;

// ---- Help centre: searchable drawer + guided page tours ------------------
Alpine.data('helpCentre', (config) => ({
    drawer: false,
    query: '',
    articles: {},
    count: 0,
    loading: false,

    tour: config.tour,
    tourKey: config.tourKey,
    tourActive: false,
    stepIndex: 0,
    spotlightStyle: '',
    popoverStyle: '',

    init() {
        this.search();

        // Auto-run a page's tour once, after the layout has settled.
        if (config.autoStart && this.tour) {
            setTimeout(() => this.startTour(), 700);
        }

        window.addEventListener('resize', () => this.tourActive && this.positionStep());
        document.addEventListener('keydown', (e) => {
            if (!this.tourActive) return;
            if (e.key === 'Escape') this.endTour();
            if (e.key === 'ArrowRight') this.next();
            if (e.key === 'ArrowLeft') this.prev();
        });
    },

    get currentStep() {
        return this.tour?.steps[this.stepIndex] ?? { title: '', body: '' };
    },

    openDrawer() {
        this.drawer = true;
        if (!this.count) this.search();
    },

    async search() {
        this.loading = true;
        try {
            const url = `${config.articlesUrl}?q=${encodeURIComponent(this.query)}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.articles = data.articles;
            this.count = data.count;
        } catch (e) {
            console.error('Help search failed', e);
        } finally {
            this.loading = false;
        }
    },

    startTour() {
        if (!this.tour) return;
        this.drawer = false;
        this.stepIndex = 0;
        // Only run steps whose target actually exists on this page.
        this.tour.steps = this.tour.steps.filter((s) => document.querySelector(s.target));
        if (!this.tour.steps.length) return;
        this.tourActive = true;
        this.$nextTick(() => this.positionStep());
    },

    next() {
        if (this.stepIndex >= this.tour.steps.length - 1) return this.endTour();
        this.stepIndex++;
        this.positionStep();
    },

    prev() {
        if (this.stepIndex > 0) this.stepIndex--;
        this.positionStep();
    },

    endTour() {
        this.tourActive = false;
        if (this.tourKey) this.markSeen(this.tourKey);
    },

    positionStep() {
        const el = document.querySelector(this.currentStep.target);
        if (!el) return this.next();

        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            const r = el.getBoundingClientRect();
            const pad = 8;
            this.spotlightStyle =
                `top:${r.top - pad}px;left:${r.left - pad}px;` +
                `width:${r.width + pad * 2}px;height:${r.height + pad * 2}px;`;

            // Prefer placing the popover below the target; flip up if it would
            // fall off screen, and keep it inside the horizontal viewport.
            const below = r.bottom + 16;
            const popoverH = 210;
            const top = below + popoverH > window.innerHeight ? Math.max(16, r.top - popoverH - 16) : below;
            const left = Math.min(Math.max(16, r.left), window.innerWidth - 336);

            this.popoverStyle = `top:${top}px;left:${left}px;`;
        }, 320);
    },

    markSeen(key) {
        fetch(config.seenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
            },
            body: JSON.stringify({ key }),
        }).catch(() => {});
    },
}));

// ---- Assistant chat -------------------------------------------------------
Alpine.data('assistant', (config) => ({
    prompt: '',
    busy: false,
    turns: [],
    pending: null,
    conversationId: config.conversationId,

    async send() {
        const text = this.prompt.trim();
        if (!text || this.busy) return;

        this.push('user', text);
        this.prompt = '';

        await this.exchange(config.sendUrl, {
            message: text,
            conversation_id: this.conversationId,
        });
    },

    // Approve or decline a pending write. The tool_use_id is echoed back and
    // re-validated server-side against what the model actually proposed, so a
    // tampered value cannot run a call the user was never shown.
    async decide(approved) {
        const pending = this.pending;
        if (!pending) return;

        this.pending = null;
        if (!approved) this.push('user', 'No, cancel that.');

        await this.exchange(config.confirmUrl, {
            conversation_id: this.conversationId,
            tool_use_id: pending.tool_use_id,
            approved,
        });
    },

    async exchange(url, body) {
        this.busy = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                },
                body: JSON.stringify(body),
            });

            if (!res.ok) {
                // 402 and 429 are the add-on gates (not purchased, over the
                // monthly cap). Their messages are written for the user, so
                // show them rather than a generic failure.
                const payload = await res.json().catch(() => ({}));
                this.push('assistant', payload.message ?? 'Something went wrong. Please try again.');
                return;
            }

            const data = await res.json();
            this.conversationId = data.conversation_id;
            if (data.text) this.push('assistant', data.text);
            this.pending = data.pending;
        } catch {
            this.push('assistant', 'Could not reach the assistant. Check your connection and try again.');
        } finally {
            this.busy = false;
            this.scroll();
        }
    },

    push(role, text) {
        this.turns.push({ key: `${role}-${this.turns.length}-${Date.now()}`, role, text });
        this.scroll();
    },

    scroll() {
        this.$nextTick(() => {
            const log = this.$refs.log;
            if (log) log.scrollTop = log.scrollHeight;
        });
    },
}));

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

// ---- Two-factor setup QR -------------------------------------------------
// Loaded on demand so the QR library only ships to the page that needs it.
const initTotpQr = async () => {
    const el = document.getElementById('totp-qr');
    if (!el?.dataset.uri) return;

    try {
        const { default: QRCode } = await import('qrcode');
        const canvas = await QRCode.toCanvas(el.dataset.uri, { width: 168, margin: 1 });
        el.innerHTML = '';
        el.appendChild(canvas);
    } catch (e) {
        // The manual entry key is always shown, so this stays usable.
        el.innerHTML = '<p class="p-2 text-xs text-gray-500">Enter the key manually instead.</p>';
        console.error('QR render failed', e);
    }
};

document.addEventListener('DOMContentLoaded', initTotpQr);
