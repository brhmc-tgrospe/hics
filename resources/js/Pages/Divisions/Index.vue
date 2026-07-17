<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    divisions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const per_page = ref(props.filters?.per_page || 10);
const isAdding = ref(false);
const editingData = ref(null);

const form = ref({
    div_code: '',
    div_name: '',
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
    };
    isAdding.value = true;
};

const openEdit = (division) => {
    editingData.value = division;
    form.value = {
        div_code: division.div_code,
        div_name: division.div_name,
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

const deleteDivision = (division) => {
    if (confirm('Are you sure you want to delete this division?')) {
        router.delete(route('divisions.destroy', division.id));
    }
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
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-500 font-medium">Show:</span>
                    <select 
                        v-model="per_page"
                        class="bg-white/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm py-2 pl-3 pr-8"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white/60 backdrop-blur-xl border border-white rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200/60 text-sm text-slate-500 font-bold bg-slate-50/50">
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Code</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4 w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <tr v-for="division in divisions.data" :key="division.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ division.id }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ division.div_code }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ division.div_name }}</td>
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
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    No divisions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="divisions.links && divisions.links.length > 3" class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/50 flex flex-wrap justify-center gap-1">
                    <Link
                        v-for="(link, i) in divisions.links"
                        :key="i"
                        :href="link.url"
                        v-html="link.label"
                        class="px-3 py-1.5 text-sm rounded-lg border transition-colors"
                        :class="[
                            link.active ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                    />
                </div>
            </div>
        </div>

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
    </InventoryLayout>
</template>
