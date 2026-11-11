<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface RowAction {
    label: string;
    icon?: string;
    danger?: boolean;
    disabled?: boolean;
    onSelect: () => void;
}

const props = withDefaults(defineProps<{
    actions: RowAction[];
    label?: string;
}>(), {
    label: 'Aksi baris',
});

const MENU_WIDTH = 200;
const MENU_GAP = 6;

const open = ref(false);
const triggerRef = ref<HTMLButtonElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);
const menuStyle = ref<Record<string, string>>({});

const positionMenu = () => {
    const trigger = triggerRef.value;

    if (!trigger) return;

    const rect = trigger.getBoundingClientRect();
    const left = Math.min(
        Math.max(8, rect.right - MENU_WIDTH),
        window.innerWidth - MENU_WIDTH - 8,
    );
    const estimatedHeight = Math.min(props.actions.length * 40 + 12, 320);
    const opensUp = rect.bottom + MENU_GAP + estimatedHeight > window.innerHeight;
    const top = opensUp
        ? Math.max(8, rect.top - estimatedHeight - MENU_GAP)
        : rect.bottom + MENU_GAP;

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${MENU_WIDTH}px`,
    };
};

const openMenu = async () => {
    positionMenu();
    open.value = true;
    await nextTick();
    positionMenu();
    menuRef.value?.querySelector<HTMLButtonElement>('.row-actions-item:not(:disabled)')?.focus();
};

const closeMenu = (restoreFocus = false) => {
    if (!open.value) return;

    open.value = false;

    if (restoreFocus) {
        triggerRef.value?.focus();
    }
};

const toggle = () => {
    if (open.value) {
        closeMenu(true);
        return;
    }

    void openMenu();
};

const run = (action: RowAction) => {
    if (action.disabled) return;

    closeMenu(false);
    action.onSelect();
};

const onDocumentPointer = (event: Event) => {
    const target = event.target as Node | null;

    if (!open.value || !target) return;
    if (triggerRef.value?.contains(target) || menuRef.value?.contains(target)) return;

    closeMenu(false);
};

const onWindowChange = () => {
    if (open.value) {
        positionMenu();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (!open.value) return;

    if (event.key === 'Escape') {
        event.preventDefault();
        closeMenu(true);
        return;
    }

    if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;

    const items = Array.from(
        menuRef.value?.querySelectorAll<HTMLButtonElement>('.row-actions-item:not(:disabled)') ?? [],
    );

    if (items.length === 0) return;

    event.preventDefault();

    const index = items.indexOf(document.activeElement as HTMLButtonElement);

    if (event.key === 'Home') {
        items[0]?.focus();
        return;
    }

    if (event.key === 'End') {
        items[items.length - 1]?.focus();
        return;
    }

    if (event.key === 'ArrowDown') {
        items[(index + 1) % items.length]?.focus();
        return;
    }

    items[(index - 1 + items.length) % items.length]?.focus();
};

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointer);
    window.addEventListener('resize', onWindowChange);
    window.addEventListener('scroll', onWindowChange, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocumentPointer);
    window.removeEventListener('resize', onWindowChange);
    window.removeEventListener('scroll', onWindowChange, true);
});
</script>

<template>
    <div class="row-actions">
        <button
            ref="triggerRef"
            type="button"
            class="btn btn-sm btn-ghost"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-label="label"
            @click.stop="toggle"
        >
            <i class="mdi mdi-dots-horizontal" aria-hidden="true"></i>
        </button>

        <Teleport to="body">
            <Transition name="app-menu">
                <div
                    v-if="open"
                    ref="menuRef"
                    class="row-actions-menu"
                    role="menu"
                    :aria-label="label"
                    :style="menuStyle"
                    @keydown="onKeydown"
                >
                    <button
                        v-for="action in actions"
                        :key="action.label"
                        type="button"
                        class="row-actions-item"
                        :class="{ 'is-danger': action.danger }"
                        role="menuitem"
                        :disabled="action.disabled"
                        @click="run(action)"
                    >
                        <i v-if="action.icon" class="mdi" :class="action.icon" aria-hidden="true"></i>
                        <span>{{ action.label }}</span>
                    </button>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
