import { reactive } from 'vue';

export interface ConfirmOptions {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'danger';
}

interface ConfirmState {
    open: boolean;
    options: Required<Pick<ConfirmOptions, 'title' | 'confirmLabel' | 'cancelLabel' | 'variant'>> & ConfirmOptions;
    resolve: ((value: boolean) => void) | null;
}

const defaults = {
    title: 'Konfirmasi',
    confirmLabel: 'Lanjutkan',
    cancelLabel: 'Batal',
    variant: 'primary' as const,
};

const state = reactive<ConfirmState>({
    open: false,
    options: { ...defaults },
    resolve: null,
});

export const confirmState = state;

export const confirmAction = (options: ConfirmOptions): Promise<boolean> => {
    if (state.resolve) {
        state.resolve(false);
        state.resolve = null;
    }

    state.options = { ...defaults, ...options };
    state.open = true;

    return new Promise<boolean>((resolve) => {
        state.resolve = resolve;
    });
};

export const settleConfirm = (value: boolean) => {
    const resolve = state.resolve;

    state.open = false;
    state.resolve = null;
    resolve?.(value);
};
