const THEME_KEY = 'recipe-relay-theme';

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
});
