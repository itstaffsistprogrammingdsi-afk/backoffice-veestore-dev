<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { StoreTransferReconciliation } from '@/types/storeTransfer';

const props = withDefaults(defineProps<{
    reconciliation: StoreTransferReconciliation;
    indexUrl: string;
    storeId?: string | null;
    warehouseId?: string | null;
    scopeLabel?: string;
}>(), {
    storeId: null,
    warehouseId: null,
    scopeLabel: 'stok operasional',
});

const reportUrl = computed(() => {
    const params = new URLSearchParams({ reconciliation_status: 'not_recorded' });

    if (props.storeId) params.set('store_id', props.storeId);
    if (props.warehouseId) params.set('warehouse_id', props.warehouseId);

    return `${props.indexUrl}?${params.toString()}`;
});

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
};
</script>

<template>
    <details
        v-if="props.reconciliation.summary.total_transfers > 0"
        class="card transfer-panel"
        :open="props.reconciliation.summary.unresolved_count > 0"
    >
        <summary class="transfer-panel-summary">
            <div>
                <h2>Outbound Gudang ke Toko</h2>
                <p>Transfer yang sudah keluar dari gudang tetapi belum sepenuhnya masuk ke {{ props.scopeLabel }}.</p>
            </div>
            <span class="transfer-panel-meta">
                <span v-if="props.reconciliation.summary.unresolved_count > 0" class="badge bg-label-danger">
                    {{ formatNumber(props.reconciliation.summary.unresolved_count) }} belum tercatat
                </span>
                <span v-else class="badge bg-label-success">Semua tercatat</span>
                <i class="mdi mdi-chevron-down transfer-panel-chevron" aria-hidden="true"></i>
            </span>
        </summary>

        <div class="transfer-panel-body">
            <div class="transfer-panel-grid">
                <div><span>Belum Tercatat</span><strong>{{ formatNumber(props.reconciliation.summary.not_recorded_count) }}</strong></div>
                <div><span>Tercatat Sebagian</span><strong>{{ formatNumber(props.reconciliation.summary.partial_count) }}</strong></div>
                <div><span>Qty Belum Masuk</span><strong>{{ formatNumber(props.reconciliation.summary.outstanding_qty) }}</strong></div>
                <div><span>Toko Terdampak</span><strong>{{ formatNumber(props.reconciliation.summary.affected_store_count) }}</strong></div>
            </div>

            <div v-if="props.reconciliation.summary.overdue_count > 0" class="transfer-panel-alert">
                <i class="mdi mdi-clock-alert-outline" aria-hidden="true"></i>
                <span>
                    {{ formatNumber(props.reconciliation.summary.overdue_count) }} transfer sudah lebih dari 3 hari.
                    Terlama {{ formatNumber(props.reconciliation.summary.oldest_days) }} hari.
                </span>
            </div>

            <div v-if="props.reconciliation.rows.length" class="table-responsive">
                <table class="table table-sm align-middle mb-0 transfer-panel-table">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>Order</th>
                            <th>Gudang</th>
                            <th class="text-end">Keluar</th>
                            <th class="text-end">Diterima</th>
                            <th class="text-end">Selisih</th>
                            <th>Status</th>
                            <th>Umur</th>
                            <th>Tanggal Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.reconciliation.rows" :key="row.id">
                            <td>
                                <strong>{{ row.store_name }}</strong>
                                <small>{{ row.store_code || '-' }}</small>
                            </td>
                            <td>
                                <strong>{{ row.order_number }}</strong>
                                <small>{{ row.invoice_number || row.delivery_order_number || row.shipping_number || '-' }}</small>
                            </td>
                            <td>
                                <strong>{{ row.warehouse_name }}</strong>
                                <small>{{ row.warehouse_code || '-' }}</small>
                            </td>
                            <td class="text-end">{{ formatNumber(row.outbound_qty) }}</td>
                            <td class="text-end">{{ formatNumber(row.received_qty) }}</td>
                            <td class="text-end"><strong>{{ formatNumber(row.outstanding_qty) }}</strong></td>
                            <td>
                                <span class="badge" :class="row.status === 'not_recorded' ? 'bg-label-danger' : 'bg-label-warning'">
                                    {{ row.status_label }}
                                </span>
                                <small v-if="row.in_delivery_qty > 0">{{ formatNumber(row.in_delivery_qty) }} masih in delivery</small>
                            </td>
                            <td>
                                <span :class="{ 'transfer-panel-overdue': row.is_overdue }">{{ formatNumber(row.age_days) }} hari</span>
                            </td>
                            <td>{{ formatDate(row.released_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="transfer-panel-empty">Tidak ada transfer yang belum tercatat.</p>

            <div class="transfer-panel-footer">
                <Link :href="reportUrl" class="transfer-panel-link">
                    Lihat laporan outbound ke toko
                    <i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                </Link>
            </div>
        </div>
    </details>
</template>

<style scoped>
.transfer-panel { overflow: hidden; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.transfer-panel-summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; cursor: pointer; list-style: none; }
.transfer-panel-summary::-webkit-details-marker { display: none; }
.transfer-panel-summary:focus-visible { outline: 2px solid var(--app-accent); outline-offset: -2px; }
.transfer-panel-summary h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.transfer-panel-summary p { margin: 0; color: var(--app-muted); font-size: 0.82rem; }
.transfer-panel-meta { display: inline-flex; align-items: center; gap: 0.45rem; white-space: nowrap; }
.transfer-panel-chevron { transition: transform 160ms ease; }
.transfer-panel[open] .transfer-panel-chevron { transform: rotate(180deg); }
.transfer-panel-body { padding: 0 1.1rem 1rem; border-top: 1px solid var(--app-border); }
.transfer-panel-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; margin: 0.9rem 0; }
.transfer-panel-grid > div { padding: 0.7rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); }
.transfer-panel-grid span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.transfer-panel-grid strong { display: block; margin-top: 0.2rem; font-size: 1.2rem; }
.transfer-panel-alert { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 0.55rem; background: var(--app-warning-bg); color: var(--app-warning-ink); font-size: 0.82rem; }
.transfer-panel-table { font-size: 0.8rem; }
.transfer-panel-table th { padding: 0.55rem 0.65rem; border-bottom: 1px solid var(--app-border); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
.transfer-panel-table td { padding: 0.55rem 0.65rem; border-color: var(--app-border); vertical-align: middle; }
.transfer-panel-table td small { display: block; color: var(--app-muted); font-size: 0.7rem; }
.transfer-panel-overdue { color: var(--app-danger-ink); font-weight: 700; }
.transfer-panel-empty { margin: 0; color: var(--app-muted); font-size: 0.82rem; }
.transfer-panel-footer { display: flex; justify-content: flex-end; padding-top: 0.75rem; }
.transfer-panel-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--app-accent-ink); font-size: 0.82rem; font-weight: 700; text-decoration: none; }
.transfer-panel-link:hover { text-decoration: underline; }
@media (max-width: 767.98px) {
    .transfer-panel-summary { align-items: flex-start; flex-direction: column; }
    .transfer-panel-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
