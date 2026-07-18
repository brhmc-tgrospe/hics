<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import EquipmentTable from './EquipmentTable.vue';
import EquipmentForm from './EquipmentForm.vue';
import ViewEquipmentDetails from './ViewEquipmentDetails.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search } from 'lucide-vue-next';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    equipment: Object,
    filters: Object,
    categories: Array,
    divisions: Array,
    areas: Array,
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const userRoles = computed(() => authUser.value?.roles || []);
const isSuperadmin = computed(() => userRoles.value.includes('Superadmin') || userRoles.value.includes('Developer'));
const isSecretary = computed(() => userRoles.value.includes('Secretary'));
const canCreate = computed(() => authUser.value?.permissions?.includes('create_equipment'));

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'All');
const myDivisionOnly = ref(props.filters.my_division_only === '1' || props.filters.my_division_only === true);
const myAreaOnly = ref(props.filters.my_area_only === '1' || props.filters.my_area_only === true);
const isAdding = ref(false);
const editingData = ref(null);
const isReporting = ref(false);
const isViewing = ref(false);
const viewingData = ref(null);

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const reportData = ref({
    category: props.categories.length ? props.categories[0].code : '',
    date_of_accountability: new Date('2021-05-28'),
    year_of_report: new Date().getFullYear(),
    report_type: 'General',
    scope_id: null,
});

const availableReportTypes = computed(() => {
    if (isSuperadmin.value || userRoles.value.includes('Admin')) {
        return ['General', 'Division', 'Area'];
    }
    return ['General', 'Area'];
});

const reportDivisions = computed(() => {
    if (isSuperadmin.value) return props.divisions;
    return props.divisions.filter(d => d.id === authUser.value?.division_id);
});

const reportAreas = computed(() => {
    if (isSuperadmin.value) return props.areas;
    if (userRoles.value.includes('Admin')) {
        return props.areas.filter(a => a.division_id === authUser.value?.division_id);
    }
    return props.areas.filter(a => a.id === authUser.value?.area_id);
});

// Auto-select scope when changing report_type
watch(() => reportData.value.report_type, (newType) => {
    if (newType === 'General') {
        reportData.value.scope_id = null;
    } else if (newType === 'Division' && reportDivisions.value.length === 1) {
        reportData.value.scope_id = reportDivisions.value[0].id;
    } else if (newType === 'Area' && reportAreas.value.length === 1) {
        reportData.value.scope_id = reportAreas.value[0].id;
    } else {
        reportData.value.scope_id = null;
    }
});

const generateReport = async () => {
    if (!reportData.value.category || !reportData.value.date_of_accountability || !reportData.value.year_of_report) {
        alert("Please fill in all required fields.");
        return;
    }
    if (reportData.value.report_type !== 'General' && !reportData.value.scope_id) {
        alert(`Please select a ${reportData.value.report_type}.`);
        return;
    }

    try {
        const payload = {
            ...reportData.value,
            date_of_accountability: reportData.value.date_of_accountability instanceof Date 
                ? reportData.value.date_of_accountability.toLocaleDateString('en-CA') // YYYY-MM-DD
                : reportData.value.date_of_accountability
        };
        const response = await axios.post(route('equipment.report.generate'), payload);
        if (response.data && response.data.id) {
            window.open(route('equipment.report.show', response.data.id), '_blank');
            isReporting.value = false;
        }
    } catch (error) {
        console.error("Error generating report", error);
        alert("Failed to generate report. Please check your inputs.");
    }
};

const applyFilters = debounce(() => {
    router.get(route('equipment.index'), {
        search: search.value,
        category: category.value,
        my_division_only: myDivisionOnly.value ? '1' : '0',
        my_area_only: myAreaOnly.value ? '1' : '0',
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, category], applyFilters);

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

const openEdit = (data) => {
    editingData.value = data;
    isAdding.value = true;
};

const openAdd = () => {
    editingData.value = null;
    isAdding.value = true;
};

const openView = (data) => {
    viewingData.value = data;
    isViewing.value = true;
};

const closeForm = () => {
    isAdding.value = false;
    editingData.value = null;
};

const fileInput = ref(null);
const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    router.post(route('equipment.import'), formData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (fileInput.value) fileInput.value.value = '';
            toastMessage.value = 'CSV Imported successfully!';
            showToast.value = true;
            setTimeout(() => { showToast.value = false; }, 3000);
        },
        onError: (errors) => {
            if (fileInput.value) fileInput.value.value = '';
            errorMessageContent.value = Object.values(errors)[0] || 'Failed to import CSV. Please ensure the format matches the template.';
            showErrorModal.value = true;
        }
    });
};

const showErrorModal = ref(false);
const errorMessageContent = ref('');

const handleSaved = (data) => {
    // The GlobalToast handles this now via Inertia flash.
    // Kept the function in case we need to trigger other client-side logic on save.
};

const currentYear = new Date().getFullYear();
const reportYears = Array.from({length: 10}, (_, i) => currentYear - 5 + i);

</script>

<template>
    <Head title="Equipment Inventory" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Equipment Inventory</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Manage and track hospital equipment</p>
                </div>
                <div class="flex items-center gap-2">
                    <input 
                        type="file" 
                        accept=".csv" 
                        ref="fileInput" 
                        class="hidden" 
                        @change="handleFileUpload" 
                    />
                    <button 
                        v-if="canCreate"
                        @click="openAdd"
                        class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 flex items-center gap-2 hover:bg-slate-800 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add
                    </button>
                    <a 
                        v-if="canCreate"
                        :href="route('equipment.template')" 
                        class="px-4 py-2 bg-white/50 text-slate-700 border border-slate-300 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 hover:bg-slate-50 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Template
                    </a>
                    <button 
                        v-if="canCreate"
                        @click="$refs.fileInput.click()"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        Import CSV
                    </button>
                    <button 
                        v-if="$page.props.auth.user.permissions.includes('generate_reports')"
                        @click="isReporting = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Generate Report
                    </button>
                </div>
            </div>

            <Modal :show="isAdding" maxWidth="2xl" @close="closeForm">
                <EquipmentForm 
                    v-if="isAdding" 
                    :editingData="editingData"
                    :categories="categories"
                    :divisions="divisions"
                    :areas="areas"
                    @close="closeForm"
                    @saved="handleSaved"
                />
            </Modal>

            <Modal :show="isViewing" maxWidth="2xl" @close="isViewing = false">
                <ViewEquipmentDetails 
                    v-if="isViewing" 
                    :data="viewingData"
                    :categories="categories"
                    :divisions="divisions"
                    @close="isViewing = false"
                />
            </Modal>

            <Modal :show="showErrorModal" maxWidth="sm" @close="showErrorModal = false">
                <div class="p-6">
                    <div class="flex items-center gap-4 text-red-600 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h3 class="text-lg font-bold">Import Failed</h3>
                    </div>
                    <p class="text-slate-600 mb-6 font-medium">{{ errorMessageContent }}</p>
                    <div class="flex justify-end">
                        <button @click="showErrorModal = false" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 hover:bg-slate-800 transition-colors">Close</button>
                    </div>
                </div>
            </Modal>

            <Modal :show="isReporting" maxWidth="md" @close="isReporting = false">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Generate Equipment Report</h3>
                    <div class="space-y-4 max-h-[70vh] overflow-y-auto px-1">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Report Type</label>
                            <select v-model="reportData.report_type" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                                <option v-for="type in availableReportTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        <div v-if="reportData.report_type === 'Division'">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Division</label>
                            <select v-model="reportData.scope_id" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                                <option :value="null" disabled>Select Division</option>
                                <option v-for="d in reportDivisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div v-if="reportData.report_type === 'Area'">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Area</label>
                            <select v-model="reportData.scope_id" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                                <option :value="null" disabled>Select Area</option>
                                <option v-for="a in reportAreas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                            <select v-model="reportData.category" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                                <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date of Accountability</label>
                            <VueDatePicker 
                                v-model="reportData.date_of_accountability" 
                                :enable-time-picker="false" 
                                auto-apply 
                                :format="formatDate"
                                :preview-format="formatDate"
                                class="w-full"
                            >
                                <template #trigger>
                                    <div class="relative w-full">
                                        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <input 
                                            :value="formatDate(reportData.date_of_accountability)" 
                                            readonly 
                                            class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 pl-10 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer"
                                            placeholder="Select date"
                                        />
                                    </div>
                                </template>
                            </VueDatePicker>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Year of Report</label>
                            <select v-model="reportData.year_of_report" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                                <option v-for="y in reportYears" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <div class="pt-4 flex justify-end gap-3 sticky bottom-0 bg-white border-t mt-4 py-2">
                            <button @click="isReporting = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancel</button>
                            <button @click="generateReport" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md">Generate</button>
                        </div>
                    </div>
                </div>
            </Modal>

            <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl overflow-hidden flex flex-col">
                <div class="p-4 border-b border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input 
                                type="text" 
                                placeholder="Search..." 
                                v-model="search"
                                class="w-full pl-9 pr-4 py-2 bg-white/50 backdrop-blur border border-white/80 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
                            />
                        </div>
                        <select 
                            v-model="category"
                            class="w-full sm:w-auto bg-white/50 backdrop-blur border border-white/80 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
                        >
                            <option value="All">All Categories</option>
                            <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                        </select>
                        <!-- Division Filter Toggle -->
                        <button 
                            @click="toggleDivisionFilter"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 border whitespace-nowrap',
                                myDivisionOnly 
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200' 
                                    : 'bg-white/50 text-slate-600 border-white/80 hover:bg-white/70'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            My Division
                        </button>

                        <!-- Area Filter Toggle -->
                        <button 
                            @click="toggleAreaFilter"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 border whitespace-nowrap',
                                myAreaOnly 
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200' 
                                    : 'bg-white/50 text-slate-600 border-white/80 hover:bg-white/70'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            My Area
                        </button>
                    </div>

                </div>

                <EquipmentTable 
                    :equipment="equipment"
                    :categories="categories"
                    :isSuperadmin="isSuperadmin"
                    :isSecretary="isSecretary"
                    :userDivisionId="authUser?.division_id"
                    :userAreaId="authUser?.area_id"
                    @edit="openEdit"
                    @view="openView"
                />
            </div>
        </div>
    </InventoryLayout>
</template>
