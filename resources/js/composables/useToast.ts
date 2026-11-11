import { reactive } from 'vue';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface ToastItem {
    id: number;
    message: string;
    variant: ToastVariant;
    timeout: number;
}

const MAX_VISIBLE = 4;
const items = reactive<ToastItem[]>([]);
const timers = new Map<number, ReturnType<typeof setTimeout>>();
let counter = 0;

const dismiss = (id: number) => {
    const index = items.findIndex((item) => item.id === id);

    if (index === -1) return;

    items.splice(index, 1);

    const timer = timers.get(id);

    if (timer) {
        clearTimeout(timer);
        timers.delete(id);
    }
};

const push = (message: string, variant: ToastVariant, timeout: number) => {
    while (items.length >= MAX_VISIBLE) {
        dismiss(items[0].id);
    }

    const id = ++counter;
    items.push({ id, message, variant, timeout });

    if (timeout > 0) {
        timers.set(id, setTimeout(() => dismiss(id), timeout));
    }

    return id;
};

export const useToast = () => ({
    items,
    dismiss,
    success: (message: string, timeout = 4000) => push(message, 'success', timeout),
    error: (message: string, timeout = 6000) => push(message, 'error', timeout),
    warning: (message: string, timeout = 5000) => push(message, 'warning', timeout),
    info: (message: string, timeout = 4000) => push(message, 'info', timeout),
});
