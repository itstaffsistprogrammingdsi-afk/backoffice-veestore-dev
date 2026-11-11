<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface NotificationItem {
    key: string;
    fingerprint: string | null;
    count: number;
    menu_slug: string;
    title: string;
    subtitle: string;
    icon: string;
    severity: string;
    url: string;
    open_url: string;
    time: string;
}

interface NotificationSummary {
    total_count: number;
    category_count: number;
    menu_counts: Record<string, number>;
    branch_operations_count: number;
    notifications: NotificationItem[];
    generated_at: string;
}

const props = withDefaults(defineProps<{
    summaryUrl?: string;
}>(), {
    summaryUrl: '/notifications/summary',
});

const POLL_INTERVAL = 60_000;
const REFRESH_AFTER_MS = 30_000;

const open = ref(false);
const loading = ref(false);
const error = ref('');
const summary = ref<NotificationSummary | null>(null);
const wrapperRef = ref<HTMLElement | null>(null);
const buttonRef = ref<HTMLButtonElement | null>(null);
const panelRef = ref<HTMLElement | null>(null);

let pollTimer: ReturnType<typeof setInterval> | null = null;
let lastLoadedAt = 0;

const notifications = computed(() => summary.value?.notifications ?? []);
const totalCount = computed(() => summary.value?.total_count ?? 0);
const badgeLabel = computed(() => (totalCount.value > 99 ? '99+' : String(totalCount.value)));
const buttonLabel = computed(() => (
    totalCount.value > 0
        ? `${totalCount.value} notifikasi baru`
        : 'Tidak ada notifikasi baru'
));

const severityClass = (severity: string): string => {
    if (['danger', 'warning', 'info', 'primary'].includes(severity)) {
        return `is-${severity}`;
    }

    return 'is-info';
};

const loadSummary = async (silent = false) => {
    if (!silent) loading.value = true;

    error.value = '';

    try {
        const response = await fetch(props.summaryUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();

        if (!payload?.success || !payload?.data) {
            throw new Error('Respons notifikasi tidak valid.');
        }

        summary.value = payload.data as NotificationSummary;
        lastLoadedAt = Date.now();
    } catch {
        error.value = 'Notifikasi tidak dapat dimuat.';

        if (!summary.value) {
            summary.value = null;
        }
    } finally {
        if (!silent) loading.value = false;
    }
};

const toggle = async () => {
    if (open.value) {
        close();

        return;
    }

    open.value = true;

    await nextTick();
    panelRef.value?.focus();

    if (!summary.value || Date.now() - lastLoadedAt > REFRESH_AFTER_MS) {
        void loadSummary();
    }
};

const close = (restoreFocus = false) => {
    if (!open.value) return;

    open.value = false;

    if (restoreFocus) {
        nextTick(() => buttonRef.value?.focus());
    }
};

const onDocumentClick = (event: MouseEvent) => {
    if (!open.value) return;

    if (!wrapperRef.value?.contains(event.target as Node)) {
        close();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && open.value) {
        close(true);
    }
};

const onVisibilityChange = () => {
    if (document.visibilityState === 'visible' && Date.now() - lastLoadedAt > REFRESH_AFTER_MS) {
        void loadSummary(true);
    }
};

const openNotification = () => {
    open.value = false;
};

onMounted(() => {
    void loadSummary(true);

    pollTimer = setInterval(() => {
        if (document.visibilityState === 'visible') {
            void loadSummary(true);
        }
    }, POLL_INTERVAL);

    document.addEventListener('click', onDocumentClick);
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);

    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="wrapperRef" class="notification-bell-wrap">
        <button
            ref="buttonRef"
            class="notification-bell"
            type="button"
            aria-haspopup="dialog"
            aria-controls="notification-panel"
            :aria-expanded="open"
            :aria-label="`Notifikasi. ${buttonLabel}`"
            :title="buttonLabel"
            @click.stop="toggle"
        >
            <i class="mdi mdi-bell-outline" aria-hidden="true"></i>
            <span v-if="totalCount > 0" class="notification-badge" aria-hidden="true">{{ badgeLabel }}</span>
            <span class="visually-hidden" role="status" aria-live="polite">{{ buttonLabel }}</span>
        </button>

        <Transition name="notification-panel">
            <section
                v-if="open"
                id="notification-panel"
                ref="panelRef"
                class="notification-panel"
                role="dialog"
                aria-modal="false"
                aria-labelledby="notification-panel-title"
                tabindex="-1"
            >
                <header class="notification-panel-header">
                    <div>
                        <h2 id="notification-panel-title">Notifikasi</h2>
                        <p v-if="totalCount > 0">{{ totalCount }} kategori perlu ditinjau</p>
                        <p v-else>Semua notifikasi sudah dibaca</p>
                    </div>
                    <div class="notification-panel-actions">
                        <button
                            class="notification-refresh"
                            type="button"
                            :disabled="loading"
                            :aria-label="loading ? 'Memuat notifikasi' : 'Muat ulang notifikasi'"
                            :title="loading ? 'Memuat notifikasi' : 'Muat ulang notifikasi'"
                            @click="loadSummary()"
                        >
                            <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-refresh'" aria-hidden="true"></i>
                        </button>
                        <button
                            class="notification-refresh"
                            type="button"
                            aria-label="Tutup notifikasi"
                            title="Tutup notifikasi"
                            @click="close(true)"
                        >
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </div>
                </header>

                <div class="notification-panel-body">
                    <div v-if="loading && !summary" class="notification-state" role="status">
                        <i class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                        <span>Memuat notifikasi...</span>
                    </div>

                    <div v-else-if="error && !summary" class="notification-state is-error" role="alert">
                        <i class="mdi mdi-alert-circle-outline" aria-hidden="true"></i>
                        <strong>Notifikasi tidak dapat dimuat.</strong>
                        <button type="button" class="notification-retry" @click="loadSummary()">Coba lagi</button>
                    </div>

                    <div v-else-if="notifications.length === 0" class="notification-state">
                        <i class="mdi mdi-bell-check-outline" aria-hidden="true"></i>
                        <strong>Tidak ada notifikasi baru</strong>
                        <span>Approval, retur, label, voucher, dan stok akan tampil di sini.</span>
                    </div>

                    <ul v-else class="notification-list" aria-label="Daftar notifikasi">
                        <li v-for="notification in notifications" :key="notification.key">
                            <a
                                :href="notification.open_url"
                                class="notification-item"
                                @click="openNotification"
                            >
                                <span class="notification-item-icon" :class="severityClass(notification.severity)">
                                    <i class="mdi" :class="notification.icon" aria-hidden="true"></i>
                                </span>
                                <span class="notification-item-copy">
                                    <strong>{{ notification.title }}</strong>
                                    <small>{{ notification.subtitle }}</small>
                                    <small class="notification-item-time">{{ notification.time }}</small>
                                </span>
                                <span class="notification-item-count" aria-hidden="true">{{ notification.count }}</span>
                                <i class="mdi mdi-chevron-right notification-item-arrow" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>

                    <p v-if="error && summary" class="notification-inline-error" role="status">
                        <i class="mdi mdi-alert-circle-outline" aria-hidden="true"></i>
                        Gagal memperbarui. Menampilkan data terakhir.
                    </p>
                </div>

                <footer class="notification-panel-footer">
                    <i class="mdi mdi-refresh" aria-hidden="true"></i>
                    Diperbarui otomatis setiap 60 detik
                </footer>
            </section>
        </Transition>
    </div>
</template>

<style scoped>
.notification-bell-wrap {
    position: relative;
    flex: 0 0 auto;
}

.notification-bell {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    padding: 0;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-md);
    background: var(--app-surface);
    color: var(--app-ink);
    font-size: 1.15rem;
    cursor: pointer;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out);
}

.notification-bell:hover,
.notification-bell:focus-visible {
    border-color: var(--app-focus);
    background: var(--app-surface-2);
}

.notification-badge {
    position: absolute;
    top: -0.35rem;
    right: -0.35rem;
    display: grid;
    place-items: center;
    min-width: 1.2rem;
    height: 1.2rem;
    padding: 0 0.22rem;
    border: 2px solid var(--app-surface);
    border-radius: var(--app-radius-pill);
    background: var(--app-danger-solid);
    color: var(--app-on-danger);
    font-size: 0.58rem;
    font-weight: 800;
    line-height: 1;
}

.notification-panel {
    position: absolute;
    top: calc(100% + 0.45rem);
    right: 0;
    z-index: 60;
    display: flex;
    flex-direction: column;
    width: min(380px, calc(100vw - 2rem));
    max-height: min(560px, calc(100dvh - 6rem));
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface);
    color: var(--app-ink);
    box-shadow: var(--app-shadow-md);
    outline: none;
}

.notification-panel:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}

.notification-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 0.9rem 0.7rem;
    border-bottom: 1px solid var(--app-border);
}

.notification-panel-header h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
}

.notification-panel-header p {
    margin: 0.2rem 0 0;
    color: var(--app-muted);
    font-size: 0.74rem;
}

.notification-panel-actions {
    display: flex;
    gap: 0.25rem;
}

.notification-refresh {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: 1px solid transparent;
    border-radius: var(--app-radius-sm);
    background: transparent;
    color: var(--app-muted);
    font-size: 1rem;
    cursor: pointer;
}

.notification-refresh:hover,
.notification-refresh:focus-visible {
    border-color: var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-ink);
}

.notification-refresh:disabled {
    cursor: wait;
    opacity: 0.65;
}

.notification-panel-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.notification-list {
    display: grid;
    gap: 0.15rem;
    margin: 0;
    padding: 0.35rem;
    list-style: none;
}

.notification-item {
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr) auto 1rem;
    align-items: center;
    gap: 0.6rem;
    min-height: 62px;
    padding: 0.55rem 0.55rem;
    border-radius: var(--app-radius-md);
    color: var(--app-ink);
    text-decoration: none;
}

.notification-item:hover,
.notification-item:focus-visible {
    background: var(--app-surface-2);
}

.notification-item-icon {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: var(--app-radius-sm);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 1.15rem;
}

.notification-item-icon.is-danger {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.notification-item-icon.is-warning {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.notification-item-icon.is-primary {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.notification-item-icon.is-info {
    background: var(--app-info-bg);
    color: var(--app-info-ink);
}

.notification-item-copy {
    min-width: 0;
}

.notification-item-copy strong {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    line-height: 1.35;
}

.notification-item-copy small {
    display: block;
    margin-top: 0.12rem;
    color: var(--app-muted);
    font-size: 0.72rem;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.notification-item-copy small.notification-item-time {
    margin-top: 0.2rem;
    font-size: 0.66rem;
}

.notification-item-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.45rem;
    height: 1.45rem;
    padding: 0 0.3rem;
    border-radius: var(--app-radius-pill);
    background: var(--app-surface-3);
    color: var(--app-accent-ink);
    font-size: 0.65rem;
    font-weight: 800;
}

.notification-item-arrow {
    color: var(--app-muted);
    font-size: 1rem;
}

.notification-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 2rem 1.25rem;
    color: var(--app-muted);
    text-align: center;
}

.notification-state > i {
    margin-bottom: 0.15rem;
    font-size: 1.8rem;
}

.notification-state strong {
    color: var(--app-ink);
    font-size: 0.86rem;
}

.notification-state span {
    max-width: 32ch;
    font-size: 0.76rem;
    line-height: 1.5;
}

.notification-state.is-error > i {
    color: var(--app-danger-ink);
}

.notification-retry {
    margin-top: 0.4rem;
    padding: 0.35rem 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-sm);
    background: var(--app-surface);
    color: var(--app-accent-ink);
    font: inherit;
    font-size: 0.74rem;
    font-weight: 700;
    cursor: pointer;
}

.notification-retry:hover,
.notification-retry:focus-visible {
    background: var(--app-accent-soft);
}

.notification-inline-error {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0;
    padding: 0.55rem 0.85rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-danger-ink);
    font-size: 0.72rem;
}

.notification-panel-footer {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.55rem 0.9rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: 0.66rem;
}

.notification-panel-enter-active,
.notification-panel-leave-active {
    transition: opacity var(--app-dur-fast) var(--app-ease-out), transform var(--app-dur-fast) var(--app-ease-out);
}

.notification-panel-enter-from,
.notification-panel-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@media (max-width: 767.98px) {
    .notification-panel {
        position: fixed;
        top: 4.5rem;
        right: 0.5rem;
        left: 0.5rem;
        width: auto;
        max-height: calc(100dvh - 5rem);
    }
}

@media (max-width: 575.98px) {
    .notification-bell {
        width: 44px;
        height: 44px;
    }

    .notification-item {
        min-height: 66px;
    }

    .notification-panel-body {
        max-height: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .notification-bell,
    .notification-panel-enter-active,
    .notification-panel-leave-active {
        transition: none;
    }
}
</style>
