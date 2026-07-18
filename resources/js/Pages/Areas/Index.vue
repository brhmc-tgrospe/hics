<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2 } from 'lucide-vue-next';
import Toggle from '@vueform/toggle';
import '@vueform/toggle/themes/default.css';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    areas: Object,
    filters: Object,
    divisions: Array,
    userDivisionId: Number,
});

const search = ref(props.filters?.search || '');
const per_page = ref(props.filters?.per_page || 10);
const my_division_only = ref(props.filters?.my_division_only !== undefined ? props.filters.my_division_only : true);
const isAdding = ref(false);
const editingData = ref(null);

const form = ref({
    area_name: '',
    division_id: props.userDivisionId || '',
});

const applyFilters = debounce(() => {
    router.get(route('areas.index'), {
        search: search.value,
        per_page: per_page.value,
        my_division_only: my_division_only.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, per_page, my_division_only], applyFilters);

const openAdd = () => {
    editingData.value = null;
    form.value = {
        area_name: '',
        division_id: props.userDivisionId || '',
    };
    isAdding.value = true;
};

const openEdit = (area) => {
    editingData.value = area;
    form.value = {
        area_name: area.area_name,
        division_id: area.division_id,
    };
    isAdding.value = true;
};

const closeForm = () => {
    isAdding.value = false;
    editingData.value = null;
};

const submit = () => {
    if (editingData.value) {
        router.put(route('areas.update', editingData.value.id), form.value, {
            onSuccess: () => closeForm(),
        });
    } else {
        router.post(route('areas.store'), form.value, {
            onSuccess: () => closeForm(),
        });
    }
};

const isConfirmDeleteOpen = ref(false);
const areaToDelete = ref(null);

const deleteArea = (area) => {
    areaToDelete.value = area;
    isConfirmDeleteOpen.value = true;
};

const executeDelete = () => {
    if (areaToDelete.value) {
        router.delete(route('areas.destroy', areaToDelete.value.id), {
            onSuccess: () => {
                isConfirmDeleteOpen.value = false;
                areaToDelete.value = null;
            }
        });
    }
};

// Check if current user can edit/delete this area
const canManageArea = (area) => {
    const user = usePage().props.auth.user;
    if (user?.roles?.includes('Developer') || user?.roles?.includes('Superadmin')) {
        return true;
    }
    return area.division_id === user?.division_id;
};

const selectedItems = ref([]);

watch(() => props.areas.data, () => {
    selectedItems.value = [];
}, { deep: true });

const selectAll = computed({
    get: () => {
        const deletableItems = props.areas.data.filter(canManageArea);
        return deletableItems.length > 0 && deletableItems.every(item => selectedItems.value.includes(item.id));
    },
    set: (val) => {
        if (val) {
            selectedItems.value = props.areas.data.filter(canManageArea).map(item => item.id);
        } else {
            selectedItems.value = [];
        }
    }
});

const isConfirmBulkDeleteOpen = ref(false);

const handleBulkDelete = () => {
    if (selectedItems.value.length === 0) return;
    isConfirmBulkDeleteOpen.value = true;
};

const executeBulkDelete = () => {
    router.delete(route('areas.bulk_delete'), {
        data: { ids: selectedItems.value },
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmBulkDeleteOpen.value = false;
        }
    });
};

</script>

<template>
    <Head title="Areas Management" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Areas</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Manage division areas</p>
                </div>
                <div>
                    <button 
                        v-if="$page.props.auth.user?.permissions?.includes('create_areas')"
                        @click="openAdd"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-colors"
                    >
                        <PlusCircle class="w-5 h-5" />
                        Add Area
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white/60 backdrop-blur-xl border border-white p-4 rounded-3xl shadow-sm flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="w-5 h-5 text-slate-400" />
                    </div>
                    <input 
                        v-model="search"
                        type="text" 
                        class="w-full pl-10 pr-4 py-2 bg-white/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Search by name..."
                    >
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2" v-if="userDivisionId">
                        <span class="text-sm text-slate-500 font-medium">My Division Only</span>
                        <Toggle v-model="my_division_only" class="toggle-blue" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white/60 backdrop-blur-xl border border-white rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200/60 text-sm text-slate-500 font-bold bg-slate-50/50">
                                <th class="px-6 py-4 w-12 text-center">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectAll"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Area Name</th>
                                <th class="px-6 py-4">Division</th>
                                <th class="px-6 py-4 w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <tr v-for="area in areas.data" :key="area.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-center">
                                    <input 
                                        v-if="canManageArea(area)"
                                        type="checkbox" 
                                        :value="area.id" 
                                        v-model="selectedItems"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ area.id }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ area.area_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ area.division?.div_name || '-' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2" v-if="canManageArea(area)">
                                        <button 
                                            v-if="$page.props.auth.user?.permissions?.includes('edit_areas')"
                                            @click="openEdit(area)" 
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors"
                                            title="Edit"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button 
                                            v-if="$page.props.auth.user?.permissions?.includes('delete_areas')"
                                            @click="deleteArea(area)" 
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="areas.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    No areas found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Rows per page:</span>
                        <select
                            v-model="per_page"
                            class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                            <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end flex-1">
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in areas.links" :key="index">
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
        </div>

        <FloatingBulkDeleteButton :count="selectedItems.length" @delete="handleBulkDelete" />

        <!-- Add/Edit Modal -->
        <Modal :show="isAdding" @close="closeForm">
            <div class="p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-6">{{ editingData ? 'Edit Area' : 'Add Area' }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Area Name</label>
                            <input v-model="form.area_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Division</label>
                            <select 
                                v-model="form.division_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm disabled:opacity-50 disabled:bg-slate-100" 
                                required
                                :disabled="!($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin'))"
                            >
                                <option value="" disabled>Select Division</option>
                                <option v-for="division in divisions" :key="division.id" :value="division.id">
                                    {{ division.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeForm" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-200 transition-colors">
                            {{ editingData ? 'Update Area' : 'Create Area' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirm Modals -->
        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Delete Area" 
            description="Are you sure you want to delete this area?" 
            confirmText="Delete"
            @close="isConfirmDeleteOpen = false; areaToDelete = null" 
            @confirm="executeDelete" 
        />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Areas" 
            :description="`Are you sure you want to delete ${selectedItems.length} areas?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </InventoryLayout>
</template>
