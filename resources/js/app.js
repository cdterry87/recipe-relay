const THEME_KEY = 'recipe-relay-theme';
const TOAST_DURATION = 5000;
const TURNSTILE_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

// Unicode vulgar fractions people type into ingredient amounts.
const FRACTIONS = { '¼': 0.25, '½': 0.5, '¾': 0.75, '⅓': 1 / 3, '⅔': 2 / 3, '⅛': 0.125 };
const NICE = [[1 / 8, '1/8'], [1 / 4, '1/4'], [1 / 3, '1/3'], [1 / 2, '1/2'], [2 / 3, '2/3'], [3 / 4, '3/4']];

// Reads "2", "1.5", "1/2", "1 1/2" or "1½". Returns null for anything else, such as "a pinch".
const parseAmount = (text) => {
    const value = text.trim().replace(/[¼½¾⅓⅔⅛]/g, (c) => ` ${FRACTIONS[c]}`).trim();
    const match = value.match(/^(\d+(?:\.\d+)?)?(?:\s*(\d+)\/(\d+))?(?:\s+(0?\.\d+))?$/);

    if (!match || (!match[1] && !match[2] && !match[4])) {
        return null;
    }

    let amount = parseFloat(match[1] ?? 0);

    if (match[2]) {
        amount += parseInt(match[2], 10) / parseInt(match[3], 10);
    }

    if (match[4]) {
        amount += parseFloat(match[4]);
    }

    return amount;
};

// Writes 1.5 as "1 1/2", 0.33 as "1/3" and 2.4 as "2.4".
const formatAmount = (amount) => {
    const whole = Math.floor(amount);
    const rest = amount - whole;

    if (rest < 0.04) {
        return String(whole);
    }

    const nice = NICE.find(([value]) => Math.abs(rest - value) < 0.04);

    if (nice) {
        return whole > 0 ? `${whole} ${nice[1]}` : nice[1];
    }

    return String(Math.round(amount * 10) / 10);
};

// Scales one amount, or both ends of a range such as "2-3".
const scaleAmount = (text, factor) => {
    if (factor === 1) {
        return text;
    }

    const parts = text.split(/\s*[-–]\s*/);
    const amounts = parts.map(parseAmount);

    if (amounts.some((amount) => amount === null)) {
        return text;
    }

    return amounts.map((amount) => formatAmount(amount * factor)).join('–');
};

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

const applyTheme = (value) => {
    if (value === 'light' || value === 'dark') {
        document.documentElement.dataset.theme = value;
    } else {
        delete document.documentElement.dataset.theme;
    }
};

// wire:navigate swaps in the next page's <html> attributes, which have no data-theme (the
// server never knows the choice). Reapply it as the page swaps, before it paints, and once
// more after navigation in case the swap hook isn't available.
document.addEventListener('livewire:navigating', (event) => {
    event.detail?.onSwap?.(() => applyTheme(readTheme()));
});
document.addEventListener('livewire:navigated', () => applyTheme(readTheme()));

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

            applyTheme(value);
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

    // The recipe page: servings scaler and cook mode, which keeps the screen awake where the
    // browser allows it (the Wake Lock API).
    Alpine.data('recipePage', (baseServings) => ({
        servings: baseServings,
        cooking: false,
        awake: false,
        lock: null,

        scale(text) {
            return baseServings > 0 ? scaleAmount(text ?? '', this.servings / baseServings) : text;
        },

        async toggleCooking() {
            this.cooking = !this.cooking;

            if (this.cooking) {
                await this.keepAwake();
                document.addEventListener('visibilitychange', this.rewake);
            } else {
                document.removeEventListener('visibilitychange', this.rewake);
                await this.lock?.release();
                this.lock = null;
                this.awake = false;
            }
        },

        async keepAwake() {
            try {
                this.lock = await navigator.wakeLock?.request('screen');
                this.awake = Boolean(this.lock);
            } catch {
                this.awake = false;
            }
        },

        // The browser drops the lock when the tab is hidden, so ask again on return.
        rewake() {
            const page = Alpine.$data(document.querySelector('.recipe'));

            if (document.visibilityState === 'visible' && page.cooking) {
                page.keepAwake();
            }
        },
    }));

    // The recipe share sheet. "More options" hands off to the device's own share menu.
    Alpine.data('shareSheet', ({ url, title, text }) => ({
        copied: false,
        canShare: typeof navigator.share === 'function',

        open() {
            this.$refs.dialog.showModal();
        },

        close() {
            this.$refs.dialog.close();
        },

        async copy() {
            try {
                await navigator.clipboard.writeText(url);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Link copied. Paste it anywhere.' } }));
                setTimeout(() => (this.copied = false), 2000);
            } catch {
                const input = this.$root.querySelector('input[readonly]');
                input?.focus();
                input?.select();
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'info', message: 'Press Ctrl+C or ⌘C to copy the selected link.' } }));
            }
        },

        async native() {
            try {
                await navigator.share({ title, text, url });
            } catch {
                // Cancelled or unavailable: the sheet stays open.
            }
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
