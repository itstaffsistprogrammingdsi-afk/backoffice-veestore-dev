<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { formatDate, formatNumber, tierBadgeClass } from '@/composables/useFormatters';

interface BirthdayCustomer {
    id: string;
    name: string | null;
    wa_number: string | null;
    email: string | null;
    date_of_birth: string | null;
    province_name: string | null;
    regency_name: string | null;
    membership_name: string | null;
}

interface GreetingTemplate {
    subject: string;
    message: string;
    placeholders: string[];
}

interface BirthdayVoucher {
    id: string;
    name: string;
    code: string;
    type: string;
    value: number;
    distribution_type: string;
    valid_until: string | null;
    available_codes_count: number | null;
}

interface Props {
    customers: BirthdayCustomer[];
    total: number;
    monthLabel: string;
    template: GreetingTemplate;
    sendUrl: string;
    vouchers?: BirthdayVoucher[];
    title?: string;
    subtitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
    vouchers: () => [],
    title: 'Ulang Tahun',
    subtitle: 'Pelanggan yang dapat disapa pada momen ulang tahunnya.',
});

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const isOpen = ref(false);
const mode = ref<'single' | 'bulk'>('single');
const selectedCustomer = ref<BirthdayCustomer | null>(null);
const subject = ref('');
const message = ref('');
const voucherId = ref('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const subjectInput = ref<HTMLInputElement | null>(null);

const emailRecipients = computed(() => props.customers.filter(customer => Boolean(customer.email)));
const previewCustomer = computed(() => selectedCustomer.value ?? emailRecipients.value[0] ?? null);
const recipientCount = computed(() => mode.value === 'single' ? 1 : emailRecipients.value.length);
const selectedVoucher = computed(() => props.vouchers.find(voucher => voucher.id === voucherId.value) ?? null);

const uniqueCodesInsufficient = (voucher: BirthdayVoucher) =>
    voucher.distribution_type === 'unique_code' && (voucher.available_codes_count ?? 0) < recipientCount.value;

const voucherOptionLabel = (voucher: BirthdayVoucher) => {
    if (voucher.distribution_type === 'unique_code') {
        return `${voucher.name} - kode unik (${formatNumber(voucher.available_codes_count ?? 0)} tersedia)`;
    }

    return `${voucher.name} - ${voucher.code}`;
};

const personalize = (text: string, customer: BirthdayCustomer | null) =>
    text
        .replaceAll('{nama}', customer?.name || 'Pelanggan')
        .replaceAll('{email}', customer?.email || '')
        .replaceAll('{bulan}', props.monthLabel);

const previewSubject = computed(() => personalize(subject.value, previewCustomer.value));
const previewMessage = computed(() => personalize(message.value, previewCustomer.value));

const recipientLabel = computed(() => {
    if (mode.value === 'single' && selectedCustomer.value) {
        return selectedCustomer.value.name || 'Pelanggan';
    }

    return `${formatNumber(emailRecipients.value.length)} pelanggan`;
});

const submitLabel = computed(() => {
    if (processing.value) return 'Mengirim...';

    return mode.value === 'bulk' ? `Kirim ke ${emailRecipients.value.length} Pelanggan` : 'Kirim Email';
});

const openGreeting = (customer: BirthdayCustomer) => {
    if (!customer.email) return;

    mode.value = 'single';
    selectedCustomer.value = customer;
    subject.value = props.template.subject;
    message.value = props.template.message;
    voucherId.value = '';
    errors.value = {};
    isOpen.value = true;
};

const openBulk = () => {
    if (emailRecipients.value.length === 0) return;

    mode.value = 'bulk';
    selectedCustomer.value = null;
    subject.value = props.template.subject;
    message.value = props.template.message;
    voucherId.value = '';
    errors.value = {};
    isOpen.value = true;
};

const close = () => {
    if (processing.value) return;

    isOpen.value = false;
};

const submit = () => {
    const customerIds = mode.value === 'single' && selectedCustomer.value
        ? [selectedCustomer.value.id]
        : emailRecipients.value.map(customer => customer.id);

    if (customerIds.length === 0) return;

    processing.value = true;
    errors.value = {};

    router.post(
        props.sendUrl,
        {
            customer_ids: customerIds,
            subject: subject.value,
            message: message.value,
            voucher_id: voucherId.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isOpen.value = false;
            },
            onError: incoming => {
                errors.value = incoming;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isOpen.value) {
        close();
    }
};

watch(isOpen, async open => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (!open) return;

    await nextTick();
    subjectInput.value?.focus();
});

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <article class="birthday-card">
        <header>
            <div class="birthday-title">
                <span class="birthday-icon"><i class="mdi mdi-cake-variant-outline"></i></span>
                <div>
                    <h2>{{ props.title }} {{ props.monthLabel }}</h2>
                    <p>{{ props.subtitle }}</p>
                </div>
            </div>
            <div class="birthday-actions">
                <span class="birthday-count">{{ formatNumber(props.total) }} pelanggan</span>
                <button
                    class="btn btn-sm btn-outline-primary"
                    type="button"
                    :disabled="emailRecipients.length === 0"
                    @click="openBulk"
                >
                    <i class="mdi mdi-email-multiple-outline me-1"></i>
                    Kirim ke Daftar
                </button>
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small mb-3" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small mb-3" role="alert">{{ flash.error }}</div>

        <div v-if="props.customers.length > 0" class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Kontak</th>
                        <th>Ulang Tahun</th>
                        <th>Wilayah</th>
                        <th>Tier</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="customer in props.customers" :key="customer.id">
                        <td><strong>{{ customer.name ?? '-' }}</strong></td>
                        <td>
                            <span v-if="customer.email" class="birthday-email">{{ customer.email }}</span>
                            <small v-else class="birthday-no-email">Email belum diisi</small>
                            <small v-if="customer.wa_number" class="birthday-wa">WA {{ customer.wa_number }}</small>
                        </td>
                        <td>{{ formatDate(customer.date_of_birth) }}</td>
                        <td>{{ [customer.regency_name, customer.province_name].filter(Boolean).join(', ') || '-' }}</td>
                        <td>
                            <span class="badge" :class="tierBadgeClass(customer.membership_name)">
                                {{ customer.membership_name ?? 'Bukan anggota' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button
                                class="btn btn-sm btn-light"
                                type="button"
                                :disabled="!customer.email"
                                :title="customer.email ? 'Kirim sapaan ulang tahun' : 'Email pelanggan belum diisi'"
                                @click="openGreeting(customer)"
                            >
                                <i class="mdi mdi-email-outline me-1"></i>
                                Sapaan
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="birthday-empty">
            <i class="mdi mdi-calendar-blank-outline"></i>
            Tidak ada pelanggan yang berulang tahun pada bulan ini.
        </p>
    </article>

    <Teleport to="body">
        <div v-if="isOpen" class="birthday-modal-backdrop" @click.self="close">
            <section class="birthday-modal" role="dialog" aria-modal="true" aria-labelledby="birthday-modal-title">
                <header class="birthday-modal-header">
                    <div>
                        <h3 id="birthday-modal-title" class="h5 mb-1">Template Sapaan Ulang Tahun</h3>
                        <p class="mb-0">Penerima: {{ recipientLabel }}</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup template sapaan" @click="close">
                        <i class="mdi mdi-close"></i>
                    </button>
                </header>

                <div class="birthday-modal-body">
                    <div v-if="errors.customer_ids" class="alert alert-danger py-2 small">{{ errors.customer_ids }}</div>

                    <div class="mb-3">
                        <label class="form-label" for="birthday-subject">Subjek Email</label>
                        <input
                            id="birthday-subject"
                            ref="subjectInput"
                            v-model="subject"
                            class="form-control"
                            type="text"
                            maxlength="150"
                            :class="{ 'is-invalid': errors.subject }"
                        />
                        <div v-if="errors.subject" class="invalid-feedback">{{ errors.subject }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="birthday-message">Isi Sapaan</label>
                        <textarea
                            id="birthday-message"
                            v-model="message"
                            class="form-control"
                            rows="8"
                            maxlength="5000"
                            :class="{ 'is-invalid': errors.message }"
                        ></textarea>
                        <div v-if="errors.message" class="invalid-feedback">{{ errors.message }}</div>
                    </div>

                    <div v-if="props.vouchers.length > 0" class="mb-3">
                        <label class="form-label" for="birthday-voucher">Lampirkan Voucher (opsional)</label>
                        <select
                            id="birthday-voucher"
                            v-model="voucherId"
                            class="form-select"
                            :class="{ 'is-invalid': errors.voucher_id }"
                        >
                            <option value="">Tanpa voucher</option>
                            <option
                                v-for="voucher in props.vouchers"
                                :key="voucher.id"
                                :value="voucher.id"
                                :disabled="uniqueCodesInsufficient(voucher)"
                            >
                                {{ voucherOptionLabel(voucher) }}
                            </option>
                        </select>
                        <div v-if="errors.voucher_id" class="invalid-feedback">{{ errors.voucher_id }}</div>

                        <div v-if="selectedVoucher" class="birthday-voucher-hint">
                            <i class="mdi" :class="selectedVoucher.distribution_type === 'unique_code' ? 'mdi-ticket-confirmation-outline' : 'mdi-ticket-percent-outline'"></i>
                            <span v-if="selectedVoucher.distribution_type === 'unique_code'">
                                Setiap penerima akan menerima satu kode unik yang berbeda.
                            </span>
                            <span v-else>
                                Kode voucher yang sama akan dikirim ke seluruh penerima.
                            </span>
                            <template v-if="selectedVoucher.valid_until">
                                Berlaku hingga {{ formatDate(selectedVoucher.valid_until) }}.
                            </template>
                        </div>
                    </div>

                    <div class="birthday-placeholders">
                        <span>Placeholder otomatis:</span>
                        <code v-for="placeholder in props.template.placeholders" :key="placeholder">{{ placeholder }}</code>
                    </div>

                    <div v-if="previewCustomer" class="birthday-preview">
                        <span class="birthday-preview-label">
                            Pratinjau untuk {{ previewCustomer.name ?? 'Pelanggan' }}
                            <template v-if="mode === 'bulk'">(setiap penerima memakai nama masing-masing)</template>
                        </span>
                        <strong>{{ previewSubject }}</strong>
                        <p class="birthday-preview-body">{{ previewMessage }}</p>
                        <div v-if="selectedVoucher" class="birthday-preview-voucher">
                            <i class="mdi mdi-ticket-percent-outline"></i>
                            <span>
                                Voucher: <strong>{{ selectedVoucher.name }}</strong>
                                <template v-if="selectedVoucher.distribution_type === 'unique_code'">
                                    (kode unik dipilih otomatis saat email dikirim)
                                </template>
                                <template v-else>
                                    (kode {{ selectedVoucher.code }})
                                </template>
                            </span>
                        </div>
                    </div>
                </div>

                <footer class="birthday-modal-footer">
                    <button class="btn btn-light" type="button" :disabled="processing" @click="close">Batal</button>
                    <button
                        class="btn btn-primary"
                        type="button"
                        :disabled="processing || !subject.trim() || !message.trim()"
                        @click="submit"
                    >
                        <i v-if="processing" class="mdi mdi-loading mdi-spin me-1"></i>
                        <i v-else class="mdi mdi-send-outline me-1"></i>
                        {{ submitLabel }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.birthday-card { display: flex; flex-direction: column; height: 100%; padding: 1rem 1.05rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.birthday-card header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem; }
.birthday-title { display: flex; align-items: flex-start; gap: 0.65rem; }
.birthday-icon { display: grid; flex: 0 0 auto; width: 2rem; height: 2rem; place-items: center; border-radius: 0.55rem; background: var(--app-warning-bg); color: var(--app-warning-ink); font-size: 1.05rem; }
.birthday-card h2 { margin: 0; font-size: 0.9rem; }
.birthday-title p { margin: 0.15rem 0 0; color: var(--app-muted); font-size: 0.74rem; }
.birthday-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.5rem; }
.birthday-count { color: var(--app-muted); font-size: 0.75rem; font-weight: 700; white-space: nowrap; }
.birthday-card th { color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.03em; text-transform: uppercase; }
.birthday-card td { font-size: 0.8rem; }
.birthday-email, .birthday-wa, .birthday-no-email { display: block; }
.birthday-email { color: var(--app-ink); font-size: 0.78rem; }
.birthday-wa, .birthday-no-email { color: var(--app-muted); font-size: 0.7rem; }
.birthday-no-email { font-style: italic; }
.birthday-empty { display: flex; align-items: center; justify-content: center; gap: 0.4rem; margin: 0; padding: 1.5rem 0; color: var(--app-muted); font-size: 0.82rem; text-align: center; }
.birthday-modal-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(6vh, 3rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.birthday-modal { width: min(680px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.birthday-modal-header, .birthday-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.birthday-modal-header { border-bottom: 1px solid var(--app-border); }
.birthday-modal-header p { color: var(--app-muted); font-size: 0.8rem; }
.birthday-modal-body { padding: 1rem 1.1rem; }
.birthday-modal-footer { justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.birthday-placeholders { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-bottom: 0.85rem; color: var(--app-muted); font-size: 0.72rem; }
.birthday-placeholders code { padding: 0.1rem 0.35rem; border: 1px solid var(--app-border); border-radius: 0.35rem; background: var(--app-surface-2); color: var(--app-accent-ink); font-size: 0.7rem; }
.birthday-preview { padding: 0.75rem 0.85rem; border: 1px dashed var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); }
.birthday-preview-label { display: block; margin-bottom: 0.4rem; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; }
.birthday-preview strong { display: block; font-size: 0.84rem; }
.birthday-preview-body { margin: 0.35rem 0 0; color: var(--app-ink); font-size: 0.8rem; line-height: 1.55; white-space: pre-line; }
.birthday-preview-voucher { display: flex; align-items: flex-start; gap: 0.35rem; margin-top: 0.6rem; padding-top: 0.55rem; border-top: 1px dashed var(--app-border); color: var(--app-muted); font-size: 0.76rem; line-height: 1.5; }
.birthday-voucher-hint { display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem; margin-top: 0.45rem; color: var(--app-muted); font-size: 0.74rem; }
@media (max-width: 575.98px) {
    .birthday-card header { align-items: flex-start; flex-direction: column; }
    .birthday-actions { justify-content: flex-start; }
    .birthday-modal-header { align-items: flex-start; }
}
</style>
