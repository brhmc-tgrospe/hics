<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    report: Object,
    supplies: Array,
    categoryName: String,
});

const totalAmount = computed(() => {
    return props.supplies.reduce((sum, item) => sum + (Number(item.unit_value) * Number(item.on_hand_per_count)), 0);
});

const reportPeriodText = computed(() => {
    if (props.report.report_period === '1st Qtr') return `As of March ${props.report.year_of_report}`;
    if (props.report.report_period === '2nd Qtr') return `As of June ${props.report.year_of_report}`;
    if (props.report.report_period === '3rd Qtr') return `As of September ${props.report.year_of_report}`;
    if (props.report.report_period === '4th Qtr') return `As of December ${props.report.year_of_report}`;
    if (props.report.report_period === 'Custom Month' && props.report.custom_month) {
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        return `As of ${months[props.report.custom_month - 1]} ${props.report.year_of_report}`;
    }
    return `As of December 31, ${props.report.year_of_report}`;
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

// Automatically print when page loads
onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Supply Report" />
    
    <div class="print-container bg-white text-black min-h-screen">
        <!-- Floating Print Button (Hidden in Print) -->
        <div class="fixed top-6 right-6 print:hidden z-50">
            <button @click="printReport" class="px-6 py-3 bg-blue-600 text-white rounded-xl shadow-lg hover:bg-blue-700 font-semibold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
        </div>

        <div class="print-page p-4">
            <table class="w-full border-collapse border-[3px] border-black text-xs">
                <thead>
                    <!-- Header Row -->
                    <tr>
                        <th colspan="7" class="border-[3px] border-black p-0">
                            <div class="relative flex items-center justify-center min-h-[90px]">
                                <div class="absolute left-0 flex items-center gap-2 px-2">
                                    <img src="/doh.png" alt="DOH Logo" class="h-20 w-auto object-contain" />
                                    <img src="/brhmc-logo.png" alt="BRHMC Logo" class="h-20 w-auto object-contain" />
                                </div>
                                <div class="text-center font-sans flex flex-col justify-center py-1">
                                    <div class="font-normal text-[13px]">Republic of the Philippines</div>
                                    <div class="font-normal text-[13px]">Division of Health</div>
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
                        <th colspan="7" class="border-[3px] border-black p-2 text-center relative">
                            <div class="text-[15px] font-bold uppercase">REPORT ON THE PHYSICAL COUNT OF Inventories</div>
                            <div class="text-[14px] font-bold mt-1 uppercase">{{ categoryName }}</div>
                            <div class="text-[14px] font-bold mt-1 mb-4">{{ reportPeriodText }}</div>
                            
                            <div class="mt-4 flex justify-start font-bold text-[13px]">
                                <div class="flex items-end pl-2">
                                    <div class="pr-2">Fund Cluster :</div>
                                    <div class="font-bold border-b-[2px] border-black text-red-600 pb-0">{{ report.fund_cluster }}</div>
                                </div>
                            </div>
                        </th>
                    </tr>
                    <!-- Accountability Row -->
                    <tr>
                        <th colspan="7" class="border-[3px] border-black p-2 text-left text-[12px] font-normal leading-tight">
                            For which <span class="font-bold underline">ERIC RAYMOND N. RABORAR, MD,MPA-HEDM,MMHoA,FPSMS</span>, Medical Center Chief II, <span class="font-bold underline">BICOL REGIONAL TRAINING AND TEACHING HOSPITAL</span>, is accountable, having assumed such accountability on <span class="font-bold">{{ formatDate(report.date_of_accountability) }}</span>.
                        </th>
                    </tr>
                    <!-- Table Columns -->
                    <tr class="font-bold text-center">
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Article</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Description</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Stock Number</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Unit of Measure</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Unit Value</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Quantity per<br/>Physical Count</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in supplies" :key="item.id">
                        <td class="border-[3px] border-black px-2 py-1 font-semibold">{{ item.article }}</td>
                        <td class="border-[3px] border-black px-2 py-1">{{ item.description }}</td>
                        <td class="border-[3px] border-black px-2 py-1 text-center">{{ item.stock_number }}</td>
                        <td class="border-[3px] border-black px-2 py-1 text-center">{{ item.unit_of_measure }}</td>
                        <td class="border-[3px] border-black px-2 py-1 text-right">{{ Number(item.unit_value).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                        <td class="border-[3px] border-black px-2 py-1 text-center">{{ item.on_hand_per_count }}</td>
                        <td class="border-[3px] border-black px-2 py-1 text-right">{{ (Number(item.unit_value) * Number(item.on_hand_per_count)).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                    </tr>
                    <tr v-if="supplies.length === 0">
                        <td colspan="7" class="border-[3px] border-black px-2 py-4 text-center italic">No supplies found for this category.</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border-[3px] border-black px-2 py-1 font-bold">***Nothing follows***</td>
                        <td colspan="4" class="border-[3px] border-black px-2 py-1"></td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="6" class="border-[3px] border-black px-2 py-2 text-center uppercase">Total</td>
                        <td class="border-[3px] border-black px-2 py-2 text-right">{{ totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatories -->
            <div v-if="!report.report_type || report.report_type === 'General'" class="mt-10 grid grid-cols-3 gap-8 text-[13px]">
                <div class="flex flex-col items-center text-center">
                    <p class="mb-10 font-semibold w-full text-left pl-6">Certified Correct by:</p>
                    <div class="w-10/12">
                        <div class="border-b-[2px] border-black w-full mb-1"></div>
                        <p class="font-bold">BARBY ANN VILLAMOR-BENITEZ</p>
                        <p>Administrative Officer V</p>
                        <p>Chairman, Inventory Committee</p>
                    </div>
                </div>

                <div class="flex flex-col items-center text-center">
                    <p class="mb-10 font-semibold w-full text-left pl-6">Approved by:</p>
                    <div class="w-10/12">
                        <div class="border-b-[2px] border-black w-full mb-1"></div>
                        <p class="font-bold text-xs">ERIC RAYMOND N. RABORAR, MD, MPA-HEDM, MMHoA, FPSMS</p>
                        <p>Medical Center Chief II</p>
                    </div>
                </div>

                <div class="flex flex-col items-center text-center">
                    <p class="mb-10 font-semibold w-full text-left pl-6">Verified by:</p>
                    <div class="w-10/12">
                        <div class="border-b-[2px] border-black w-full mb-1"></div>
                        <p class="font-bold">JEMALYN G. OCAY</p>
                        <p>State Auditor III</p>
                        <p>Audit Team Leader</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* CSS Reset for printing */
@media print {
    @page {
        size: 13in 8.5in; /* Folio / Legal Landscape */
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
