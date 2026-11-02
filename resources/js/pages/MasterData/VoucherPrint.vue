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
            <p>{{ props.codes.length }} unique code baru telah dibuat untuk {{ props.voucher.name }}.</p>
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
body { margin: 0; background: #f4f6fb; color: #172033; font: 14px/1.45 Arial, sans-serif; }
.print-toolbar { max-width: 1120px; margin: 24px auto 12px; display: flex; justify-content: space-between; align-items: center; }
.print-toolbar h1 { margin: 0; font-size: 20px; }
.print-toolbar p { margin: 3px 0 0; color: #667085; }
.print-toolbar button { border: 0; background: #3b4cf0; color: #fff; padding: 10px 16px; border-radius: 7px; font-weight: 700; cursor: pointer; }
.print-sheet { max-width: 1120px; margin: 0 auto 30px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.print-voucher { position: relative; overflow: hidden; min-height: 250px; border: 1px solid #dfe3ee; border-radius: 14px; background: #fff; box-shadow: 0 5px 16px rgba(22, 31, 55, .06); }
.print-voucher::before { content: ""; position: absolute; top: -100px; right: -65px; width: 230px; height: 230px; background: radial-gradient(circle, rgba(59, 76, 240, .18), transparent 66%); }
.print-head { position: relative; padding: 18px 20px; color: #fff; background: linear-gradient(120deg, #1c2a6b, #3b4cf0); }
.print-brand { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; opacity: .84; }
.print-name { margin-top: 5px; max-width: 86%; font-size: 19px; font-weight: 700; }
.print-body { padding: 18px 20px; }
.print-description { min-height: 34px; color: #667085; }
.print-code-label { margin-top: 16px; color: #667085; font-size: 11px; font-weight: 700; letter-spacing: .8px; }
.print-code { margin-top: 3px; font: 700 24px/1.2 'Courier New', monospace; letter-spacing: 1.1px; }
.print-footer { display: flex; gap: 12px; margin-top: 18px; padding-top: 12px; border-top: 1px dashed #dfe3ee; color: #667085; font-size: 11px; }
.print-footer div { flex: 1; }
.print-footer strong { display: block; margin-top: 2px; color: #172033; font-size: 12px; }
@media print {
    @page { size: A4; margin: 10mm; }
    body { background: #fff; }
    .print-toolbar { display: none; }
    .print-sheet { max-width: none; gap: 8mm; }
    .print-voucher { break-inside: avoid; box-shadow: none; min-height: 230px; }
}
</style>
