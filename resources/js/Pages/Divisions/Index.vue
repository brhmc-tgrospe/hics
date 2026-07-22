<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2 } from 'lucide-vue-next';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    divisions: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search || '');
const per_page = ref(props.filters?.per_page || 10);
const isAdding = ref(false);
const editingData = ref(null);

const form = ref({
    div_code: '',
    div_name: '',
    head_first_name: '',
    head_middle_initial: '',
    head_last_name: '',
    head_nominal_letters: '',
    head_designation: '',
});

const applyFilters = debounce(() => {
    router.get(route('divisions.index'), {
        search: search.value,
        per_page: per_page.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, per_page], applyFilters);

const openAdd = () => {
    editingData.value = null;
    form.value = {
        div_code: '',
        div_name: '',
        head_first_name: '',
        head_middle_initial: '',
        head_last_name: '',
        head_nominal_letters: '',
        head_designation: '',
    };
    isAdding.value = true;
};

const openEdit = (division) => {
    editingData.value = division;
    form.value = {
        div_code: division.div_code,
        div_name: division.div_name,
        head_first_name: division.head_first_name || '',
        head_middle_initial: division.head_middle_initial || '',
        head_last_name: division.head_last_name || '',
        head_nominal_letters: division.head_nominal_letters || '',
        head_designation: division.head_designation || '',
    };
    isAdding.value = true;
};

const closeForm = () => {
    isAdding.value = false;
    editingData.value = null;
};

const submit = () => {
    if (editingData.value) {
        router.put(route('divisions.update', editingData.value.id), form.value, {
            onSuccess: () => closeForm(),
        });
    } else {
        router.post(route('divisions.store'), form.value, {
            onSuccess: () => closeForm(),
        });
    }
};

const isConfirmDeleteOpen = ref(false);
const divisionToDelete = ref(null);

const deleteDivision = (division) => {
    divisionToDelete.value = division;
    isConfirmDeleteOpen.value = true;
};

const executeDelete = () => {
    if (divisionToDelete.value) {
        router.delete(route('divisions.destroy', divisionToDelete.value.id), {
            onSuccess: () => {
                isConfirmDeleteOpen.value = false;
                divisionToDelete.value = null;
            }
        });
    }
};

const selectedItems = ref([]);

watch(() => props.divisions.data, () => {
    selectedItems.value = [];
}, { deep: true });

const canDeleteDivision = () => {
    return page.props.auth.user?.permissions?.includes('delete_divisions');
};

const selectAll = computed({
    get: () => {
        if (!canDeleteDivision()) return false;
        return props.divisions.data.length > 0 && props.divisions.data.every(item => selectedItems.value.includes(item.id));
    },
    set: (val) => {
        if (val && canDeleteDivision()) {
            selectedItems.value = props.divisions.data.map(item => item.id);
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
    router.delete(route('divisions.bulk_delete'), {
        data: { ids: selectedItems.value },
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmBulkDeleteOpen.value = false;
        }
    });
};

</script>

<template>
    <Head title="Divisions Management" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Divisions</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Manage system divisions</p>
                </div>
                <div>
                    <button 
                        v-if="$page.props.auth.user?.permissions?.includes('create_divisions')"
                        @click="openAdd"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-colors"
                    >
                        <PlusCircle class="w-5 h-5" />
                        Add Division
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
                        placeholder="Search by code or name..."
                    >
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
                                        v-if="canDeleteDivision()"
                                        type="checkbox" 
                                        v-model="selectAll"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Code</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">Head</th>
                                <th class="px-6 py-4 w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <tr v-for="division in divisions.data" :key="division.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-center">
                                    <input 
                                        v-if="canDeleteDivision()"
                                        type="checkbox" 
                                        :value="division.id" 
                                        v-model="selectedItems"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ division.id }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ division.div_code }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ division.div_name }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-700">
                                        {{ division.head_first_name }} <template v-if="division.head_middle_initial">{{ division.head_middle_initial }}. </template>{{ division.head_last_name }}<span v-if="division.head_nominal_letters">, {{ division.head_nominal_letters }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ division.head_designation }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            v-if="$page.props.auth.user?.permissions?.includes('edit_divisions')"
                                            @click="openEdit(division)" 
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors"
                                            title="Edit"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button 
                                            v-if="$page.props.auth.user?.permissions?.includes('delete_divisions')"
                                            @click="deleteDivision(division)" 
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="divisions.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    No divisions found.
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
                            <template v-for="(link, index) in divisions.links" :key="index">
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
                <h2 class="text-xl font-bold text-slate-900 mb-6">{{ editingData ? 'Edit Division' : 'Add Division' }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Division Code</label>
                            <input v-model="form.div_code" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Division Name</label>
                            <input v-model="form.div_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                                <input v-model="form.head_first_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Middle Initial</label>
                                <input v-model="form.head_middle_initial" type="text" maxlength="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                                <input v-model="form.head_last_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Nominal Letters</label>
                                <input v-model="form.head_nominal_letters" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Designation</label>
                                <input v-model="form.head_designation" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeForm" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-200 transition-colors">
                            {{ editingData ? 'Update Division' : 'Create Division' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirm Modals -->
        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Delete Division" 
            description="Are you sure you want to delete this division?" 
            confirmText="Delete"
            @close="isConfirmDeleteOpen = false; divisionToDelete = null" 
            @confirm="executeDelete" 
        />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Divisions" 
            :description="`Are you sure you want to delete ${selectedItems.length} divisions?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </InventoryLayout>
</template>
