<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import { FileText, Eye, Trash2 } from 'lucide-vue-next';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    reports: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    categories: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const category = ref(props.filters.category || 'All');
const dateFrom = ref(props.filters.date_from || null);
const dateTo = ref(props.filters.date_to || null);
const myDivisionOnly = ref(props.filters.my_division_only === '1' || props.filters.my_division_only === true);
const myAreaOnly = ref(props.filters.my_area_only === '1' || props.filters.my_area_only === true);
const perPage = ref(props.filters.per_page || 10);

const selectedReports = ref([]);

// Clear selection when data changes
watch(() => props.reports.data, () => {
    selectedReports.value = [];
}, { deep: true });

const toggleAll = computed({
    get: () => {
        const deletableItems = props.reports.data.filter(r => r.can_delete);
        return deletableItems.length > 0 && deletableItems.every(r => isSelected(r.id, r.type));
    },
    set: (val) => {
        if (val) {
            selectedReports.value = props.reports.data.filter(r => r.can_delete).map(r => ({ id: r.id, type: r.type }));
        } else {
            selectedReports.value = [];
        }
    }
});

const isSelected = (id, type) => {
    return selectedReports.value.some(r => r.id === id && r.type === type);
};

const toggleSelection = (id, type) => {
    const index = selectedReports.value.findIndex(r => r.id === id && r.type === type);
    if (index === -1) {
        selectedReports.value.push({ id, type });
    } else {
        selectedReports.value.splice(index, 1);
    }
};

const applyFilters = debounce(() => {
    router.get(route('reports.index'), {
        category: category.value,
        date_from: dateFrom.value ? (dateFrom.value instanceof Date ? dateFrom.value.toLocaleDateString('en-CA') : dateFrom.value) : null,
        date_to: dateTo.value ? (dateTo.value instanceof Date ? dateTo.value.toLocaleDateString('en-CA') : dateTo.value) : null,
        my_division_only: myDivisionOnly.value ? '1' : '0',
        my_area_only: myAreaOnly.value ? '1' : '0',
        per_page: perPage.value
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([category, dateFrom, dateTo, perPage], applyFilters);

const toggleDivisionFilter = () => {
    myDivisionOnly.value = !myDivisionOnly.value;
    if (myDivisionOnly.value) myAreaOnly.value = false;
    applyFilters();
};

const toggleAreaFilter = () => {
    myAreaOnly.value = !myAreaOnly.value;
    if (myAreaOnly.value) myDivisionOnly.value = false;
    applyFilters();
};

const viewReport = (report) => {
    if (report.type === 'supply') {
        window.open(route('supplies.report.show', report.id), '_blank');
    } else {
        window.open(route('equipment.report.show', report.id), '_blank');
    }
};

const isConfirmDeleteOpen = ref(false);
const reportToDelete = ref(null);

const deleteReport = (report) => {
    reportToDelete.value = report;
    isConfirmDeleteOpen.value = true;
};

const executeDelete = () => {
    if (reportToDelete.value) {
        router.delete(route('reports.destroy', { type: reportToDelete.value.type, id: reportToDelete.value.id }), {
            onSuccess: () => {
                isConfirmDeleteOpen.value = false;
                reportToDelete.value = null;
            }
        });
    }
};

const isConfirmBulkDeleteOpen = ref(false);

const deleteSelected = () => {
    if (selectedReports.value.length === 0) return;
    isConfirmBulkDeleteOpen.value = true;
};

const executeBulkDelete = () => {
    router.post(route('reports.bulk_delete'), {
        reports: selectedReports.value
    }, {
        onSuccess: () => {
            selectedReports.value = [];
            isConfirmBulkDeleteOpen.value = false;
        }
    });
};

const formatDateForPicker = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head title="Reports" />

    <InventoryLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/50 backdrop-blur-md p-6 rounded-2xl border border-white/60 shadow-sm">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <FileText class="w-6 h-6 text-blue-600" />
                        Generated Reports
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Manage and view your generated reports across all categories.</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white/50 backdrop-blur-xl rounded-2xl border border-white/80 shadow-sm p-4 flex flex-wrap gap-4 items-center relative z-20">
                <!-- Category Filter -->
                <div class="w-full sm:w-auto">
                    <select 
                        v-model="category"
                        class="w-full sm:w-48 bg-white border border-slate-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700 shadow-sm"
                    >
                        <option value="All">All Categories</option>
                        <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </select>
                </div>
                
                <!-- Date Filters -->
                <div class="w-full sm:w-auto flex items-center gap-2">
                    <VueDatePicker 
                        v-model="dateFrom" 
                        :enable-time-picker="false" 
                        auto-apply 
                        :format="formatDateForPicker"
                        :preview-format="formatDateForPicker"
                        placeholder="From Date"
                        class="w-40"
                    />
                    <span class="text-slate-400">-</span>
                    <VueDatePicker 
                        v-model="dateTo" 
                        :enable-time-picker="false" 
                        auto-apply 
                        :format="formatDateForPicker"
                        :preview-format="formatDateForPicker"
                        placeholder="To Date"
                        class="w-40"
                    />
                </div>

                <!-- Division/Area Toggles -->
                <div class="flex items-center gap-2">
                    <button 
                        @click="toggleDivisionFilter"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 border whitespace-nowrap',
                            myDivisionOnly 
                                ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200' 
                                : 'bg-white/70 text-slate-600 border-slate-300 hover:bg-white'
                        ]"
                    >
                        My Division
                    </button>
                    <button 
                        @click="toggleAreaFilter"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 border whitespace-nowrap',
                            myAreaOnly 
                                ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200' 
                                : 'bg-white/70 text-slate-600 border-slate-300 hover:bg-white'
                        ]"
                    >
                        My Area
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white/70 backdrop-blur-xl border border-white/80 rounded-2xl shadow-sm overflow-hidden flex flex-col relative z-10">
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 backdrop-blur border-b border-slate-200/60 sticky top-0 z-10 text-slate-600 font-semibold">
                            <tr>
                                <th class="p-4 w-12 text-center">
                                    <input 
                                        type="checkbox" 
                                        v-model="toggleAll"
                                        class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </th>
                                <th class="p-4">Report Name</th>
                                <th class="p-4">Created By</th>
                                <th class="p-4">Date Created</th>
                                <th class="p-4 text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/60">
                            <tr v-if="reports.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500">
                                    No reports generated yet.
                                </td>
                            </tr>
                            <tr 
                                v-for="report in reports.data" 
                                :key="`${report.type}-${report.id}`"
                                class="hover:bg-blue-50/30 transition-colors group"
                            >
                                <td class="p-4 text-center">
                                    <input 
                                        v-if="report.can_delete"
                                        type="checkbox" 
                                        class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        :checked="isSelected(report.id, report.type)"
                                        @change="toggleSelection(report.id, report.type)"
                                    >
                                </td>
                                <td class="p-4 font-medium text-slate-700">
                                    {{ report.name }}
                                </td>
                                <td class="p-4 text-slate-600">
                                    {{ report.created_by }}
                                </td>
                                <td class="p-4 text-slate-500">
                                    {{ report.created_at }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        <button 
                                            @click="viewReport(report)"
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View Report"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button 
                                            v-if="report.can_delete"
                                            @click="deleteReport(report)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Rows per page:</span>
                        <select
                          v-model="perPage"
                          class="bg-white border border-slate-300 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 shadow-sm"
                        >
                          <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-end flex-1">
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in reports.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="['px-3 py-1 rounded-lg text-xs font-medium transition-colors', link.active ? 'bg-blue-600 text-white' : 'hover:bg-slate-200 text-slate-600']"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 rounded-lg text-xs font-medium text-slate-400"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <FloatingBulkDeleteButton :count="selectedReports.length" @delete="deleteSelected" />

        <!-- Delete Confirm Modals -->
        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Delete Report" 
            description="Are you sure you want to delete this report?" 
            confirmText="Delete"
            @close="isConfirmDeleteOpen = false; reportToDelete = null" 
            @confirm="executeDelete" 
        />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Reports" 
            :description="`Are you sure you want to delete ${selectedReports.length} selected report(s)?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </InventoryLayout>
</template>
