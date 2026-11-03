<script setup lang="ts">
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

interface VoucherCode { id: string; code: string; expires_at: string | null }

const props = defineProps<{
    voucher: Record<string, any>;
    codes: VoucherCode[];
}>();

const formatDate = (value: string | null) => {
    if (!value) return '-';
    return new Date(value).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const printNow = () => window.print();

onMounted(() => window.setTimeout(printNow, 500));
</script>

<template>
    <Head :title="`Cetak Voucher - ${props.voucher.name}`" />
    <div class="print-toolbar">
        <div>
            <h1>Voucher siap dicetak</h1>
            <p>{{ props.codes.length }} kode unik siap dicetak untuk {{ props.voucher.name }}.</p>
        </div>
        <button type="button" @click="printNow">Cetak sekarang</button>
    </div>
    <main class="print-sheet">
        <article v-for="code in props.codes" :key="code.id" class="print-voucher">
            <header class="print-head">
                <div class="print-brand">VEESTORE VOUCHER</div>
                <div class="print-name">{{ props.voucher.name }}</div>
            </header>
            <section class="print-body">
                <div class="print-description">{{ props.voucher.description || 'Gunakan kode unik ini saat transaksi.' }}</div>
                <div class="print-code-label">KODE VOUCHER</div>
                <div class="print-code">{{ code.code }}</div>
                <div class="print-footer">
                    <div>Berlaku dari<strong>{{ formatDate(props.voucher.valid_from) }}</strong></div>
                    <div>Berakhir pada<strong>{{ formatDate(code.expires_at) }}</strong></div>
                </div>
            </section>
        </article>
    </main>
</template>

<style>
body { margin: 0; background: var(--app-bg); color: var(--app-ink); font: 14px/1.45 Arial, sans-serif; }
.print-toolbar { max-width: 1120px; margin: 24px auto 12px; padding-inline: 16px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
.print-toolbar h1 { margin: 0; font-size: 20px; }
.print-toolbar p { margin: 3px 0 0; color: var(--app-muted); }
.print-toolbar button { border: 0; background: var(--app-accent); color: var(--app-on-accent); padding: 10px 16px; border-radius: 6px; font: inherit; font-weight: 700; cursor: pointer; }
.print-toolbar button:hover { background: var(--app-accent-strong); }
.print-toolbar button:focus-visible { outline: 2px solid var(--app-accent-strong); outline-offset: 3px; }
.print-sheet { max-width: 1120px; margin: 0 auto 30px; padding-inline: 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.print-voucher { overflow: hidden; min-width: 0; min-height: 250px; border: 1px solid var(--app-border); border-radius: 8px; background: var(--app-surface); }
.print-head { padding: 18px 20px; color: var(--app-hero-text); background: var(--app-hero-bg); }
.print-brand { font-size: 11px; font-weight: 700; letter-spacing: .08em; opacity: .84; }
.print-name { margin-top: 5px; font-size: 19px; font-weight: 700; overflow-wrap: anywhere; }
.print-body { padding: 18px 20px; }
.print-description { min-height: 34px; color: var(--app-muted); }
.print-code-label { margin-top: 16px; color: var(--app-muted); font-size: 11px; font-weight: 700; letter-spacing: .06em; }
.print-code { margin-top: 3px; font: 700 24px/1.2 'Courier New', monospace; letter-spacing: .04em; overflow-wrap: anywhere; }
.print-footer { display: flex; gap: 12px; margin-top: 18px; padding-top: 12px; border-top: 1px dashed var(--app-border); color: var(--app-muted); font-size: 11px; }
.print-footer div { flex: 1; }
.print-footer strong { display: block; margin-top: 2px; color: var(--app-ink); font-size: 12px; }
@media (max-width: 640px) {
    .print-toolbar { align-items: flex-start; flex-direction: column; }
    .print-toolbar button { width: 100%; }
    .print-sheet { grid-template-columns: minmax(0, 1fr); }
}
@media print {
    @page { size: A4; margin: 10mm; }
    body { background: #fff; color: #1d2a25; }
    .print-toolbar { display: none; }
    .print-sheet { max-width: none; gap: 8mm; }
    .print-voucher { break-inside: avoid; box-shadow: none; min-height: 230px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
