<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    expiringSupplies: {
        type: Object,
        default: () => ({
            total_items_count: 0,
            total_quantity: 0,
            total_amount: 0,
            current_date_utc8: '',
            cutoff_date_utc8: '',
            categories: [],
        }),
    },
    categoryName: {
        type: String,
        default: 'ALL CATEGORIES',
    },
    scopeName: {
        type: String,
        default: '',
    },
    selectedCategories: {
        type: Array,
        default: () => ['All'],
    },
    statusFilter: {
        type: String,
        default: 'all',
    },
    reportFilters: {
        type: Object,
        default: () => ({}),
    },
    generatedBy: {
        type: String,
        default: 'System',
    },
    generatedAt: {
        type: String,
        default: '',
    },
    savedReportId: {
        type: Number,
        default: null,
    },
});

const currentSavedId = ref(props.savedReportId);
const isSaving = ref(false);
const saveMessage = ref('');
const saveError = ref('');

const statusSubtitle = computed(() => {
    if (props.statusFilter === 'expired') return 'Expired Items Only';
    if (props.statusFilter === 'expiring_soon') return 'Close to Expiring Items Only (Within 6 Months)';
    return 'Expired & Close to Expiring Items (Within 6 Months)';
});

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const parts = String(dateStr).slice(0, 10).split('-').map(Number);
    if (parts.length !== 3 || parts.some(Number.isNaN)) return dateStr;
    const [year, month, day] = parts;
    const d = new Date(Date.UTC(year, month - 1, day));
    return d.toLocaleDateString('en-US', {
        timeZone: 'UTC',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatMoney = (value) => {
    return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const printReport = () => {
    window.print();
};

const saveReport = async () => {
    if (currentSavedId.value || isSaving.value) return;
    isSaving.value = true;
    saveError.value = '';
    saveMessage.value = '';

    try {
        const response = await axios.post(route('supplies.expiring_report.save'), {
            categories: props.selectedCategories,
            status_filter: props.statusFilter,
            ...props.reportFilters,
        });

        if (response.data?.id) {
            currentSavedId.value = response.data.id;
            saveMessage.value = response.data.message || 'Saved to Reports Area.';
        }
    } catch (error) {
        saveError.value = error?.response?.data?.message || 'Failed to save report. Please try again.';
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <Head title="Expiring Supplies Report" />

    <div class="print-container bg-white text-black min-h-screen flex flex-col">
        <!-- Floating Action Toolbar (Hidden in Print/PDF) -->
        <div class="fixed top-6 right-6 print:hidden z-50 flex flex-col items-end gap-2">
            <div class="flex items-center gap-3">
                <button
                    v-if="!currentSavedId"
                    type="button"
                    @click="saveReport"
                    :disabled="isSaving"
                    class="px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg hover:bg-emerald-700 disabled:opacity-60 font-semibold flex items-center gap-2 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    {{ isSaving ? 'Saving...' : 'Save to Reports' }}
                </button>
                <div
                    v-else
                    class="px-5 py-3 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl shadow-md font-semibold flex items-center gap-2"
                >
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Saved to Reports
                </div>

                <button
                    type="button"
                    @click="printReport"
                    class="px-6 py-3 bg-blue-600 text-white rounded-xl shadow-lg hover:bg-blue-700 font-semibold flex items-center gap-2 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print / Save as PDF
                </button>
            </div>

            <div
                v-if="saveMessage"
                class="px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-sm"
            >
                {{ saveMessage }}
            </div>
            <div
                v-if="saveError"
                class="px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold shadow-sm"
            >
                {{ saveError }}
            </div>
        </div>

        <!-- Printable Report Content -->
        <div class="print-page p-4 sm:p-6 flex-1 flex flex-col justify-between">
            <div>
                <table class="w-full border-collapse border-[3px] border-black text-xs">
                    <thead>
                        <!-- Official Hospital Header -->
                        <tr>
                            <th colspan="9" class="border-[3px] border-black p-0">
                                <div class="relative flex items-center justify-center min-h-[90px]">
                                    <div class="absolute left-0 flex items-center gap-2 px-2">
                                        <img src="/doh.png" alt="DOH Logo" class="h-20 w-auto object-contain" />
                                        <img src="/brhmc-logo.png" alt="BRHMC Logo" class="h-20 w-auto object-contain" />
                                    </div>
                                    <div class="text-center font-sans flex flex-col justify-center py-1">
                                        <div class="font-normal text-[13px]">Republic of the Philippines</div>
                                        <div class="font-normal text-[13px]">Department of Health</div>
                                        <div class="font-bold text-[15px]">BICOL REGIONAL HOSPITAL AND MEDICAL CENTER</div>
                                        <div class="font-normal text-[13px]">Daraga, Albay</div>
                                    </div>
                                    <div class="absolute right-0 flex items-center px-2">
                                        <img src="/bp_logo.png" alt="BP Logo" class="h-20 w-auto object-contain" />
                                    </div>
                                </div>
                            </th>
                        </tr>

                        <!-- Title Row -->
                        <tr>
                            <th colspan="9" class="border-[3px] border-black p-2.5 text-center">
                                <div class="text-[15px] font-bold uppercase">REPORT ON EXPIRING AND EXPIRED SUPPLIES</div>
                                <div class="text-[13px] font-bold mt-1 uppercase">{{ categoryName }}</div>
                                <div v-if="scopeName" class="text-[13px] font-bold mt-1 uppercase">{{ scopeName }}</div>
                                <div class="text-[12px] font-semibold mt-0.5">{{ statusSubtitle }}</div>
                                <div class="text-[12px] font-bold mt-1">
                                    Checked as of {{ formatDate(expiringSupplies.current_date_utc8) }} — 6-Month Cutoff: {{ formatDate(expiringSupplies.cutoff_date_utc8) }}
                                </div>
                            </th>
                        </tr>

                        <!-- Column Headers -->
                        <tr class="font-bold text-center text-[11px] bg-slate-100">
                            <th class="border-[2px] border-black px-2 py-1.5">Article / Product Name</th>
                            <th class="border-[2px] border-black px-2 py-1.5">Description</th>
                            <th class="border-[2px] border-black px-2 py-1.5">Stock No.</th>
                            <th class="border-[2px] border-black px-2 py-1.5">Division / Area</th>
                            <th class="border-[2px] border-black px-2 py-1.5">UOM</th>
                            <th class="border-[2px] border-black px-2 py-1.5">Expiry Date</th>
                            <th class="border-[2px] border-black px-2 py-1.5 text-right">Unit Value</th>
                            <th class="border-[2px] border-black px-2 py-1.5 text-right">Quantity</th>
                            <th class="border-[2px] border-black px-2 py-1.5 text-right">Total Amount</th>
                        </tr>
                    </thead>

                    <tbody class="text-[11px]">
                        <tr v-if="!expiringSupplies.categories || expiringSupplies.categories.length === 0">
                            <td colspan="9" class="border-[2px] border-black px-3 py-6 text-center italic">
                                No expiring or expired supplies found for the selected criteria.
                            </td>
                        </tr>

                        <template v-for="cat in expiringSupplies.categories" :key="cat.category_code">
                            <!-- Category Group Header -->
                            <tr class="bg-slate-200 font-bold text-[11px]">
                                <td colspan="7" class="border-[2px] border-black px-2 py-1.5 uppercase">
                                    {{ cat.category_name }} ({{ cat.items_count }} {{ cat.items_count === 1 ? 'item' : 'items' }})
                                </td>
                                <td class="border-[2px] border-black px-2 py-1.5 text-right">
                                    {{ Number(cat.total_quantity || 0).toLocaleString() }}
                                </td>
                                <td class="border-[2px] border-black px-2 py-1.5 text-right">
                                    ₱{{ formatMoney(cat.total_amount) }}
                                </td>
                            </tr>

                            <!-- Items in Category -->
                            <tr v-for="item in cat.items" :key="item.id">
                                <td class="border border-black px-2 py-1 font-semibold">{{ item.article || '—' }}</td>
                                <td class="border border-black px-2 py-1">{{ item.description || '—' }}</td>
                                <td class="border border-black px-2 py-1 text-center">{{ item.stock_number || '—' }}</td>
                                <td class="border border-black px-2 py-1">
                                    <div>{{ item.division_name || '—' }}</div>
                                    <div v-if="item.area_name" class="text-[10px] text-slate-600">{{ item.area_name }}</div>
                                </td>
                                <td class="border border-black px-2 py-1 text-center">{{ item.unit_of_measure || '—' }}</td>
                                <td class="border border-black px-2 py-1 text-center whitespace-nowrap">
                                    <div>{{ formatDate(item.expiry_date) }}</div>
                                    <div
                                        class="text-[10px] font-bold uppercase"
                                        :class="item.is_expired ? 'text-rose-700' : 'text-amber-700'"
                                    >
                                        {{ item.is_expired ? 'Expired' : (item.days_until_expiry === 0 ? 'Expires Today' : `In ${item.days_until_expiry}d`) }}
                                    </div>
                                </td>
                                <td class="border border-black px-2 py-1 text-right whitespace-nowrap">
                                    ₱{{ formatMoney(item.unit_value) }}
                                </td>
                                <td class="border border-black px-2 py-1 text-right font-semibold whitespace-nowrap">
                                    {{ Number(item.on_hand_per_count || 0).toLocaleString() }}
                                </td>
                                <td class="border border-black px-2 py-1 text-right font-semibold whitespace-nowrap">
                                    ₱{{ formatMoney(item.total_amount) }}
                                </td>
                            </tr>

                            <!-- Category Subtotal -->
                            <tr class="bg-slate-50 font-bold text-[11px]">
                                <td colspan="7" class="border-[2px] border-black px-2 py-1.5 text-right uppercase">
                                    Subtotal — {{ cat.category_name }}
                                </td>
                                <td class="border-[2px] border-black px-2 py-1.5 text-right whitespace-nowrap">
                                    {{ Number(cat.total_quantity || 0).toLocaleString() }}
                                </td>
                                <td class="border-[2px] border-black px-2 py-1.5 text-right whitespace-nowrap">
                                    ₱{{ formatMoney(cat.total_amount) }}
                                </td>
                            </tr>
                        </template>

                        <!-- Grand Total -->
                        <tr v-if="expiringSupplies.categories && expiringSupplies.categories.length > 0" class="font-bold text-[12px] bg-slate-200">
                            <td colspan="7" class="border-[3px] border-black px-2 py-2 text-right uppercase">
                                Grand Total ({{ expiringSupplies.total_items_count }} {{ expiringSupplies.total_items_count === 1 ? 'item' : 'items' }})
                            </td>
                            <td class="border-[3px] border-black px-2 py-2 text-right whitespace-nowrap">
                                {{ Number(expiringSupplies.total_quantity || 0).toLocaleString() }}
                            </td>
                            <td class="border-[3px] border-black px-2 py-2 text-right whitespace-nowrap">
                                ₱{{ formatMoney(expiringSupplies.total_amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Very Bottom User Attribution Footer -->
            <div class="mt-8 pt-4 border-t-2 border-black flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs">
                <div>
                    Generated by: <span class="font-bold uppercase">{{ generatedBy }}</span>
                </div>
                <div>
                    Date Generated: <span class="font-semibold">{{ generatedAt }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: 13in 8.5in;
        margin: 0.5in;
    }
    body {
        margin: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print-container {
        background: transparent !important;
        min-height: auto;
    }
    .print-page {
        padding: 0 !important;
    }
}
</style>
