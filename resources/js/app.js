const THEME_KEY = 'recipe-relay-theme';
const TOAST_DURATION = 5000;
const TURNSTILE_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let turnstileScript;

// Loads Cloudflare's script only when a page renders the widget.
const loadTurnstile = () => {
    turnstileScript ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = TURNSTILE_SRC;
        script.async = true;
        script.onload = () => resolve(window.turnstile);
        script.onerror = reject;
        document.head.append(script);
    });

    return turnstileScript;
};

const readTheme = () => {
    try {
        return localStorage.getItem(THEME_KEY) ?? 'system';
    } catch {
        return 'system';
    }
};

document.addEventListener('alpine:init', () => {
    // Light, dark, or follow the system. The head script applies a saved choice before first paint.
    Alpine.store('theme', {
        value: readTheme(),

        set(value) {
            this.value = value;

            try {
                localStorage.setItem(THEME_KEY, value);
            } catch {
                // Private browsing can block storage; the theme still applies for this page.
            }

            if (value === 'system') {
                delete document.documentElement.dataset.theme;
            } else {
                document.documentElement.dataset.theme = value;
            }
        },
    });

    // Renders a Cloudflare Turnstile widget and keeps the component's `turnstile` property
    // set to a fresh token. Options are passed to turnstile.render(), for example
    // { appearance: 'interaction-only' } to stay hidden unless Cloudflare needs a click.
    Alpine.data('turnstile', (siteKey, options = {}) => ({
        widgetId: null,

        async init() {
            const turnstile = await loadTurnstile();

            this.render(turnstile);
            this.$watch('$store.theme.value', () => this.render(turnstile));
            window.addEventListener('turnstile-reset', () => turnstile.reset(this.widgetId));
        },

        render(turnstile) {
            if (this.widgetId !== null) {
                turnstile.remove(this.widgetId);
            }

            const theme = Alpine.store('theme').value;

            this.$wire.turnstile = '';
            this.widgetId = turnstile.render(this.$el, {
                sitekey: siteKey,
                theme: theme === 'system' ? 'auto' : theme,
                size: 'flexible',
                ...options,
                callback: (token) => (this.$wire.turnstile = token),
                'expired-callback': () => (this.$wire.turnstile = ''),
                'error-callback': () => (this.$wire.turnstile = ''),
            });
        },
    }));

    Alpine.data('toasts', (flashed = null) => ({
        items: [],
        nextId: 0,

        init() {
            if (flashed) {
                this.push(flashed);
            }
        },

        push({ type = 'info', message }) {
            const id = this.nextId++;

            this.items.push({ id, type, message });
            setTimeout(() => this.dismiss(id), TOAST_DURATION);
        },

        dismiss(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    }));
});
