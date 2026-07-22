<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Trash2, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    tab: String,
    data: Object,
    filters: Object,
});

const per_page = ref(props.filters?.per_page || 10);

watch(per_page, (value) => {
    router.get(route('recycle-bin.index'), { tab: props.tab, per_page: value }, { preserveState: true, replace: true });
});

const selectedItems = ref([]);

const selectAll = computed({
    get: () => {
        if (!props.data || props.data.data.length === 0) return false;
        return props.data.data.every(item => selectedItems.value.some(s => s.id === item.id && s.type === item.type));
    },
    set: (value) => {
        if (value) {
            props.data.data.forEach(item => {
                if (!selectedItems.value.some(s => s.id === item.id && s.type === item.type)) {
                    selectedItems.value.push({ id: item.id, type: item.type, report_model_type: item.report_model_type });
                }
            });
        } else {
            selectedItems.value = selectedItems.value.filter(
                s => !props.data.data.some(item => item.id === s.id && item.type === s.type)
            );
        }
    }
});

const toggleSelect = (item) => {
    const index = selectedItems.value.findIndex(s => s.id === item.id && s.type === item.type);
    if (index === -1) {
        selectedItems.value.push({ id: item.id, type: item.type, report_model_type: item.report_model_type });
    } else {
        selectedItems.value.splice(index, 1);
    }
};

const setTab = (newTab) => {
    selectedItems.value = [];
    router.get(route('recycle-bin.index'), { tab: newTab, per_page: per_page.value }, { preserveState: true, replace: true });
};

const isConfirmRestoreOpen = ref(false);
const isConfirmDeleteOpen = ref(false);
const actionItems = ref([]);

const restore = (item = null) => {
    actionItems.value = item ? [{ id: item.id, type: item.type, report_model_type: item.report_model_type }] : selectedItems.value;
    isConfirmRestoreOpen.value = true;
};

const executeRestore = () => {
    router.post(route('recycle-bin.restore'), { items: actionItems.value }, {
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmRestoreOpen.value = false;
        }
    });
};

const forceDelete = (item = null) => {
    actionItems.value = item ? [{ id: item.id, type: item.type, report_model_type: item.report_model_type }] : selectedItems.value;
    isConfirmDeleteOpen.value = true;
};

const executeForceDelete = () => {
    router.delete(route('recycle-bin.force-delete'), {
        data: { items: actionItems.value },
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmDeleteOpen.value = false;
        }
    });
};

const tabs = [
    { id: 'categories', label: 'Categories' },
    { id: 'equipment', label: 'Equipment' },
    { id: 'supplies', label: 'Supplies' },
    { id: 'reports', label: 'Reports' },
    { id: 'users', label: 'Users' },
    { id: 'divisions', label: 'Divisions' },
    { id: 'areas', label: 'Areas' }
];

</script>

<template>
    <Head title="Recycle Bin" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <Trash2 class="w-8 h-8 text-red-500" />
                        Recycle Bin
                    </h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Restore or permanently delete removed items</p>
                </div>
                <div class="flex items-center gap-2" v-if="selectedItems.length > 0">
                    <button 
                        @click="restore(null)"
                        class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-green-200 flex items-center gap-2 hover:bg-green-700 transition-colors w-full sm:w-auto justify-center"
                    >
                        <RotateCcw class="w-4 h-4" />
                        Restore Selected ({{ selectedItems.length }})
                    </button>
                    <button 
                        @click="forceDelete(null)"
                        class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 transition-colors w-full sm:w-auto justify-center"
                    >
                        <Trash2 class="w-4 h-4" />
                        Delete Selected ({{ selectedItems.length }})
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-300/50 pb-2">
                <button 
                    v-for="t in tabs" 
                    :key="t.id"
                    @click="setTab(t.id)"
                    :class="['px-4 py-2 rounded-t-xl font-bold text-sm transition-colors', tab === t.id ? 'bg-white text-red-600 shadow-sm border-b-2 border-red-600' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50']"
                >
                    {{ t.label }}
                </button>
            </div>

            <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl overflow-hidden flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/5 text-slate-700 border-b border-slate-300">
                                <th class="py-4 px-6 font-bold text-sm w-12">
                                    <input type="checkbox" v-model="selectAll" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500 w-4 h-4" />
                                </th>
                                <!-- Dynamic Headers based on tab -->
                                <template v-if="tab === 'categories'">
                                    <th class="py-4 px-6 font-bold text-sm">Code</th>
                                    <th class="py-4 px-6 font-bold text-sm">Name</th>
                                </template>
                                <template v-else-if="tab === 'equipment' || tab === 'supplies'">
                                    <th class="py-4 px-6 font-bold text-sm">Category</th>
                                    <th class="py-4 px-6 font-bold text-sm">Article</th>
                                </template>
                                <template v-else-if="tab === 'reports'">
                                    <th class="py-4 px-6 font-bold text-sm">Report Name</th>
                                    <th class="py-4 px-6 font-bold text-sm">Date Created</th>
                                </template>
                                <template v-else-if="tab === 'users'">
                                    <th class="py-4 px-6 font-bold text-sm">First Name</th>
                                    <th class="py-4 px-6 font-bold text-sm">Last Name</th>
                                    <th class="py-4 px-6 font-bold text-sm">Username</th>
                                </template>
                                <template v-else-if="tab === 'divisions'">
                                    <th class="py-4 px-6 font-bold text-sm">Code</th>
                                    <th class="py-4 px-6 font-bold text-sm">Division Name</th>
                                </template>
                                <template v-else-if="tab === 'areas'">
                                    <th class="py-4 px-6 font-bold text-sm">Area Name</th>
                                    <th class="py-4 px-6 font-bold text-sm">Division</th>
                                </template>

                                <th class="py-4 px-6 font-bold text-sm text-right w-48">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in data.data" :key="`${item.type}-${item.id}`" class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-6">
                                    <input 
                                        type="checkbox" 
                                        :checked="selectedItems.some(s => s.id === item.id && s.type === item.type)"
                                        @change="toggleSelect(item)"
                                        class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500 w-4 h-4"
                                    />
                                </td>
                                
                                <!-- Dynamic Columns -->
                                <template v-if="tab === 'categories'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.code }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.name }}</td>
                                </template>
                                <template v-else-if="tab === 'equipment' || tab === 'supplies'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.category }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.article }}</td>
                                </template>
                                <template v-else-if="tab === 'reports'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.name }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.created_at }}</td>
                                </template>
                                <template v-else-if="tab === 'users'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.first_name }}</td>
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.last_name }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.username }}</td>
                                </template>
                                <template v-else-if="tab === 'divisions'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.code }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.name }}</td>
                                </template>
                                <template v-else-if="tab === 'areas'">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-800">{{ item.area_name }}</td>
                                    <td class="py-3 px-6 text-sm text-slate-600">{{ item.division }}</td>
                                </template>

                                <td class="py-3 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="restore(item)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Restore"
                                        >
                                            <RotateCcw class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="forceDelete(item)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Permanently Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="data.data.length === 0">
                                <td colspan="100%" class="py-8 text-center text-slate-500 font-medium">
                                    <div class="flex flex-col items-center justify-center">
                                        <Trash2 class="w-12 h-12 text-slate-300 mb-2" />
                                        <p>Recycle Bin is empty for this category.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4" v-if="data.data.length > 0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Rows per page:</span>
                        <select
                            v-model="per_page"
                            class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                            <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in data.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="['px-3 py-1 rounded-lg text-xs font-medium transition-colors', link.active ? 'bg-blue-600 text-white' : 'hover:bg-white/50 text-slate-600']"
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

        <ConfirmModal 
            :show="isConfirmRestoreOpen" 
            title="Restore Item(s)" 
            :description="`Are you sure you want to restore ${actionItems.length} item(s)?`" 
            confirmText="Restore"
            @close="isConfirmRestoreOpen = false" 
            @confirm="executeRestore" 
        />

        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Permanently Delete Item(s)" 
            :description="`Are you sure you want to permanently delete ${actionItems.length} item(s)? This action cannot be undone.`" 
            confirmText="Permanently Delete"
            @close="isConfirmDeleteOpen = false" 
            @confirm="executeForceDelete" 
        />
    </InventoryLayout>
</template>
