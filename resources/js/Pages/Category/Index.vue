<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import CategoryTable from './CategoryTable.vue';
import CategoryForm from './CategoryForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const userRoles = computed(() => authUser.value?.roles || []);
const isSuperadminOrDeveloper = computed(() => userRoles.value.includes('Superadmin') || userRoles.value.includes('Developer'));

const activeTab = ref(props.filters.tab || 'equipment');
const search = ref(props.filters.search || '');
const isAdding = ref(false);

const applyFilters = debounce(() => {
    router.get(route('categories.index'), {
        tab: activeTab.value,
        search: search.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, activeTab], applyFilters);

const setTab = (tab) => {
    activeTab.value = tab;
};

const openAdd = () => {
    isAdding.value = true;
};

const closeForm = () => {
    isAdding.value = false;
};

const handleSaved = () => {
    closeForm();
};
</script>

<template>
    <Head title="Categories" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Categories</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Manage equipment and supplies categories</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        v-if="isSuperadminOrDeveloper"
                        @click="openAdd"
                        class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 flex items-center gap-2 hover:bg-slate-800 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Category
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-4 border-b border-slate-300/50 pb-2">
                <button 
                    @click="setTab('equipment')"
                    :class="['px-4 py-2 rounded-t-xl font-bold text-sm transition-colors', activeTab === 'equipment' ? 'bg-white text-blue-700 shadow-sm border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50']"
                >
                    Equipment
                </button>
                <button 
                    @click="setTab('supplies')"
                    :class="['px-4 py-2 rounded-t-xl font-bold text-sm transition-colors', activeTab === 'supplies' ? 'bg-white text-blue-700 shadow-sm border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50']"
                >
                    Supplies
                </button>
            </div>

            <Modal :show="isAdding" maxWidth="md" @close="closeForm">
                <CategoryForm 
                    v-if="isAdding" 
                    :activeTab="activeTab"
                    @close="closeForm"
                    @saved="handleSaved"
                />
            </Modal>

            <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl overflow-hidden flex flex-col">
                <div class="p-4 border-b border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input 
                                type="text" 
                                placeholder="Search by code or name..." 
                                v-model="search"
                                class="w-full pl-9 pr-4 py-2 bg-white/50 backdrop-blur border border-white/80 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
                            />
                        </div>
                    </div>
                </div>

                <CategoryTable 
                    :categories="categories"
                    :isSuperadminOrDeveloper="isSuperadminOrDeveloper"
                    @update-per-page="(size) => router.get(route('categories.index'), { tab: activeTab, search: search, per_page: size }, { preserveState: true, replace: true, preserveScroll: true })"
                />
            </div>
        </div>
    </InventoryLayout>


</template>
