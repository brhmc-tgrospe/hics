<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    report: Object,
    equipment: Array,
    categoryName: String,
});

const totalAmount = computed(() => {
    return props.equipment.reduce((sum, item) => sum + (Number(item.total_value) || 0), 0);
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear().toString().slice(-2)}`;
};

// Automatically print when page loads
onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Equipment Report" />

    <div class="print-container bg-white text-black min-h-screen">
        <div class="print-page p-4">
            <table class="w-full border-collapse border-[3px] border-black text-xs">
                <thead>
                    <!-- Header Row -->
                    <tr>
                        <th colspan="10" class="border-[3px] border-black p-0">
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
                                <div class="absolute right-0 flex items-stretch">
                                    <div class="flex items-center px-2 py-1 pr-4">
                                        <img src="/bp_logo.png" alt="BP Logo" class="h-20 w-auto object-contain" />
                                    </div>
                                    <div class="flex flex-col text-[12px] font-medium w-52 border-l-[3px] border-black">
                                        <div class="flex border-b-[3px] border-black flex-1">
                                            <div class="w-1/3 border-r-[3px] border-black flex items-center justify-start px-2 font-normal">Form Code</div>
                                            <div class="w-2/3 flex items-center justify-center font-bold">FM-ADM-PSS-17</div>
                                        </div>
                                        <div class="flex border-b-[3px] border-black flex-1">
                                            <div class="w-1/3 border-r-[3px] border-black flex items-center justify-start px-2 font-normal">Effectivity</div>
                                            <div class="w-2/3 flex items-center justify-center font-bold">September 30, 2013</div>
                                        </div>
                                        <div class="flex flex-1">
                                            <div class="w-1/3 border-r-[3px] border-black flex items-center justify-start px-2 font-normal">Revicion</div>
                                            <div class="w-2/3 flex items-center justify-center font-bold">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </th>
                    </tr>
                    <!-- Title Row -->
                    <tr>
                        <th colspan="10" class="border-[3px] border-black p-2 text-center">
                            <div class="text-[15px] font-bold uppercase">REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT</div>
                            <div class="text-[14px] font-bold uppercase">{{ categoryName }}</div>
                            <div class="text-[13px] font-normal">As of December 31, {{ report.year_of_report }}</div>
                        </th>
                    </tr>
                    <!-- Accountability Row -->
                    <tr>
                        <th colspan="10" class="border-[3px] border-black p-2 text-left text-[12px] font-normal leading-tight">
                            For which <span class="font-bold underline">ERIC RAYMOND N. RABORAR, MD,MPA-HEDM,MMHoA,FPSMS</span>, Medical Center Chief II, <span class="font-bold underline">BICOL REGIONAL TRAINING AND TEACHING HOSPITAL</span>, is accountable, having assumed such accountability on <span class="font-bold">{{ formatDate(report.date_of_accountability) }}</span>.
                        </th>
                    </tr>
                    <!-- Table Columns -->
                    <tr class="font-bold text-center">
                        <th class="border-[3px] border-black px-1 py-2 align-middle">ARTICLE</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">DESCRIPTION</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">DATE<br/>ACQD.</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">PROPERTY<br/>NUMBER</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">UNIT OF<br/>MEASURE</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">UNIT<br/>VALUE</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">TOTAL<br/>VALUE</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">QUANTITY<br/>per PROPERTY<br/>CARD</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">QUANTITY<br/>per PHYSICAL<br/>COUNT</th>
                        <th class="border-[3px] border-black px-1 py-2 align-middle">REMARKS/<br/>WHEREABOUTS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in equipment" :key="item.id">
                        <td class="border-[3px] border-black px-1 py-1 font-semibold">{{ item.article }}</td>
                        <td class="border-[3px] border-black px-1 py-1">{{ item.description }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-center">{{ formatDateShort(item.date_acquired) }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-center">{{ item.property_number }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-center">{{ item.unit_of_measure }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-right">{{ Number(item.unit_value).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-right">{{ Number(item.total_value).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-center">{{ item.quantity_per_property_card }}</td>
                        <td class="border-[3px] border-black px-1 py-1 text-center">{{ item.quantity_per_physical_count }}</td>
                        <td class="border-[3px] border-black px-1 py-1">{{ item.remarks }}</td>
                    </tr>
                    <tr v-if="equipment.length === 0">
                        <td colspan="10" class="border-[3px] border-black px-1 py-4 text-center italic">No equipment found for this category.</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="border-[3px] border-black px-1 py-1 font-bold">***Nothing follows***</td>
                        <td colspan="7" class="border-[3px] border-black px-1 py-1"></td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="6" class="border-[3px] border-black px-1 py-1 text-center uppercase">TOTAL</td>
                        <td class="border-[3px] border-black px-1 py-1 text-right">{{ totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                        <td colspan="3" class="border-[3px] border-black px-1 py-1"></td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatories -->
            <div class="mt-8 grid grid-cols-3 gap-8 text-[13px]">
                <div>
                    <p class="mb-8 font-semibold">Certified Correct by:</p>
                    <div class="w-11/12">
                        <div class="border-b border-black w-full mb-1"></div>
                        <div class="text-center">
                            <p class="font-bold">BARBY ANN VILLAMOR-BENITEZ</p>
                            <p>Administrative Officer V</p>
                            <p>Chairman, Inventory Committee</p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <p class="mb-8 font-semibold text-left">Approved by:</p>
                    <div class="w-full">
                        <div class="border-b border-black mx-auto w-11/12 mb-1"></div>
                        <div class="text-center">
                            <p class="font-bold text-xs">ERIC RAYMOND N. RABORAR, MD, MPA-HEDM, MMHoA, FPSMS</p>
                            <p>Medical Center Chief II</p>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="mb-8 font-semibold">Verified by:</p>
                    <div class="w-11/12">
                        <div class="border-b border-black w-full mb-1"></div>
                        <div class="text-center">
                            <p class="font-bold">JEMALYN G. OCAY</p>
                            <p>State Auditor III</p>
                            <p>Audit Team Leader</p>
                        </div>
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
