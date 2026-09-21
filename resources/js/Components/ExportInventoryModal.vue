<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import { Download, FileSpreadsheet, X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Export Inventory',
    },
    exportRoute: {
        type: String,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => ['Serviceable', 'Unserviceable'],
    },
});

const emit = defineEmits(['close']);

const form = ref({
    format: 'template', // 'template' or 'full'
    division_id: '',
    area_id: '',
    category: '',
    status: '',
});

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.value = {
            format: 'template',
            division_id: '',
            area_id: '',
            category: '',
            status: '',
        };
    }
});

// When division changes, reset area if the selected area doesn't belong to the division
watch(() => form.value.division_id, (newDiv) => {
    if (!newDiv) {
        form.value.area_id = '';
    } else {
        const areaExistsInDiv = props.areas.some(
            a => String(a.division_id) === String(newDiv) && String(a.id) === String(form.value.area_id)
        );
        if (!areaExistsInDiv) {
            form.value.area_id = '';
        }
    }
});

// Available areas based on selected division
const filteredAreas = computed(() => {
    if (!form.value.division_id) {
        return [];
    }
    return props.areas.filter(a => String(a.division_id) === String(form.value.division_id));
});

const handleExport = () => {
    const params = new URLSearchParams();
    if (form.value.format) params.append('format', form.value.format);
    if (form.value.division_id) params.append('division_id', form.value.division_id);
    if (form.value.area_id) params.append('area_id', form.value.area_id);
    if (form.value.category) params.append('category', form.value.category);
    if (form.value.status) params.append('status', form.value.status);

    const queryString = params.toString();
    const url = queryString ? `${route(props.exportRoute)}?${queryString}` : route(props.exportRoute);

    // Trigger browser download via direct URL
    window.location.href = url;
    emit('close');
};
</script>

<template>
    <Modal :show="show" maxWidth="lg" @close="$emit('close')">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                        <FileSpreadsheet class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ title }}</h3>
                        <p class="text-xs text-slate-500 font-medium">Extract records in CSV format</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="$emit('close')"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="space-y-4 max-h-[70vh] overflow-y-auto px-1">
                <!-- Export Format Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                        Export Format
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <label 
                            :class="[
                                'relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all duration-200',
                                form.format === 'template' 
                                    ? 'border-emerald-500 bg-emerald-50/40 shadow-sm ring-2 ring-emerald-500/20' 
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold text-slate-900">Re-importable Template</span>
                                <input 
                                    type="radio" 
                                    name="export_format" 
                                    value="template" 
                                    v-model="form.format" 
                                    class="text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                                />
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Columns match the import template for quick batch editing and re-upload.
                            </p>
                        </label>

                        <label 
                            :class="[
                                'relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all duration-200',
                                form.format === 'full' 
                                    ? 'border-emerald-500 bg-emerald-50/40 shadow-sm ring-2 ring-emerald-500/20' 
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold text-slate-900">Full Audit Data</span>
                                <input 
                                    type="radio" 
                                    name="export_format" 
                                    value="full" 
                                    v-model="form.format" 
                                    class="text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                                />
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Includes IDs, names, shortage/overage, calculated totals, and timestamps.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Division Selection -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Division
                    </label>
                    <select 
                        v-model="form.division_id"
                        class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-800 px-3.5 py-2.5 text-sm shadow-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all cursor-pointer"
                    >
                        <option value="">All Divisions (Entire Hospital)</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                </div>

                <!-- Area Selection -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Area / Unit
                    </label>
                    <select 
                        v-model="form.area_id"
                        :disabled="!form.division_id"
                        :class="[
                            'w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition-all',
                            form.division_id 
                                ? 'border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 cursor-pointer' 
                                : 'border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed'
                        ]"
                    >
                        <option value="">
                            {{ form.division_id ? 'All Areas in selected Division' : 'Select a Division first to filter by Area' }}
                        </option>
                        <option v-for="a in filteredAreas" :key="a.id" :value="a.id">
                            {{ a.name }}
                        </option>
                    </select>
                </div>

                <!-- Optional Filters (Grid of 2) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Category <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                        </label>
                        <select 
                            v-model="form.category"
                            class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-800 px-3 py-2 text-sm shadow-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all cursor-pointer"
                        >
                            <option value="">All Categories</option>
                            <option v-for="c in categories" :key="c.code || c.id" :value="c.code || c.name">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Status <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                        </label>
                        <select 
                            v-model="form.status"
                            class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-800 px-3 py-2 text-sm shadow-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all cursor-pointer"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in statusOptions" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2.5 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-sm font-semibold transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleExport"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-emerald-200 flex items-center gap-2 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                    <Download class="w-4 h-4" />
                    Download CSV
                </button>
            </div>
        </div>
    </Modal>
</template>
