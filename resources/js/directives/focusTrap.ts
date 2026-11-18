import type { Directive } from 'vue';
import { lockAppRoot, unlockAppRoot } from '@/composables/useAppRootInert';

const FOCUSABLE = [
    'a[href]',
    'button:not(:disabled)',
    'input:not(:disabled)',
    'select:not(:disabled)',
    'textarea:not(:disabled)',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

interface TrapState {
    keydown: (event: KeyboardEvent) => void;
    previousFocus: HTMLElement | null;
    locked: boolean;
}

const states = new WeakMap<HTMLElement, TrapState>();

const getFocusable = (element: HTMLElement) => Array.from(
    element.querySelectorAll<HTMLElement>(FOCUSABLE),
).filter((item) => item.getClientRects().length > 0);

const onKeydown = (element: HTMLElement, event: KeyboardEvent) => {
    if (event.key !== 'Tab') return;

    const focusable = getFocusable(element);

    if (focusable.length === 0) {
        event.preventDefault();
        element.focus();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

export const vFocusTrap: Directive<HTMLElement> = {
    mounted(element, binding) {
        const locked = !binding.modifiers.noInert && !element.closest('#app');
        const previousFocus = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;

        if (locked) {
            lockAppRoot();
        }

        const keydown = (event: KeyboardEvent) => onKeydown(element, event);

        element.addEventListener('keydown', keydown);
        states.set(element, { keydown, previousFocus, locked });

        window.requestAnimationFrame(() => {
            if (element.contains(document.activeElement)) return;

            if (!element.hasAttribute('tabindex') && getFocusable(element).length === 0) {
                element.setAttribute('tabindex', '-1');
            }

            const target = element.querySelector<HTMLElement>('[autofocus]')
                ?? getFocusable(element)[0]
                ?? element;

            target.focus();
        });
    },

    unmounted(element) {
        const state = states.get(element);

        if (!state) return;

        element.removeEventListener('keydown', state.keydown);

        if (state.locked) {
            unlockAppRoot();
        }

        state.previousFocus?.focus();
        states.delete(element);
    },
};
