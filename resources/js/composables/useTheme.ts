import { computed, ref } from 'vue';

type Theme = 'light' | 'dark';

const STORAGE_KEY = 'veestore.theme';

const readInitialTheme = (): Theme => {
    if (typeof window === 'undefined') {
        return 'light';
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);

    if (stored === 'light' || stored === 'dark') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const theme = ref<Theme>(readInitialTheme());

const applyTheme = (value: Theme) => {
    document.documentElement.dataset.theme = value;
    document.documentElement.classList.toggle('dark', value === 'dark');
    window.localStorage.setItem(STORAGE_KEY, value);
};

if (typeof document !== 'undefined') {
    document.documentElement.dataset.theme = theme.value;
    document.documentElement.classList.toggle('dark', theme.value === 'dark');
}

export const useTheme = () => {
    const isDark = computed(() => theme.value === 'dark');

    const setTheme = (value: Theme) => {
        theme.value = value;
        applyTheme(value);
    };

    const toggleTheme = () => {
        setTheme(isDark.value ? 'light' : 'dark');
    };

    return {
        theme,
        isDark,
        setTheme,
        toggleTheme,
    };
};
