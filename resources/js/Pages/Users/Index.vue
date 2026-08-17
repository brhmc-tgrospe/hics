<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2, VenetianMask, Eye, EyeOff, ChevronUp, ChevronDown } from 'lucide-vue-next';
import Toggle from '@vueform/toggle';
import '@vueform/toggle/themes/default.css';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import UserFormModal from './Partials/UserFormModal.vue';
import TablePagination from '@/Components/TablePagination.vue';
import DivisionAreaFilter from '@/Components/DivisionAreaFilter.vue';
import { useInventoryPermissions } from '@/Composables/useInventoryPermissions';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    divisions: Array,
    areas: Array,
});

const page = usePage();
const { canFilterDivisionArea, authUser } = useInventoryPermissions();

const search = ref(props.filters.search || '');
const per_page = ref(props.filters.per_page || 10);
const division_only = ref(props.filters.division_only !== undefined ? (props.filters.division_only === 'true' || props.filters.division_only === true || props.filters.division_only === '1' || props.filters.division_only === 1) : true);
const divisionId = ref(props.filters.division_id !== undefined ? String(props.filters.division_id) : (props.filters.division_filter || (division_only.value && authUser.value?.division_id ? String(authUser.value.division_id) : '')));
const areaId = ref(props.filters.area_id !== undefined ? String(props.filters.area_id) : '');
const sort_field = ref(props.filters.sort_field || 'created_at');
const sort_direction = ref(props.filters.sort_direction || 'desc');
const isAdding = ref(false);
const editingData = ref(null);
const isViewing = ref(false);
const viewingData = ref(null);

const syncTogglesFromDropdowns = () => {
    const userDiv = authUser.value?.division_id ? String(authUser.value.division_id) : null;
    if (divisionId.value && userDiv && String(divisionId.value) === userDiv) {
        division_only.value = true;
    } else {
        division_only.value = false;
    }
};

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: search.value,
        per_page: per_page.value,
        division_only: division_only.value ? '1' : '0',
        division_id: divisionId.value,
        area_id: areaId.value,
        sort_field: sort_field.value,
        sort_direction: sort_direction.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

const sortBy = (field) => {
    if (sort_field.value === field) {
        sort_direction.value = sort_direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort_field.value = field;
        sort_direction.value = 'asc';
    }
};

watch([search, per_page, sort_field, sort_direction], applyFilters);

watch(division_only, (val) => {
    if (val) {
        divisionId.value = authUser.value?.division_id ? String(authUser.value.division_id) : '';
    } else if (authUser.value?.division_id && String(divisionId.value) === String(authUser.value.division_id)) {
        divisionId.value = '';
    }
    applyFilters();
});

watch([divisionId, areaId], () => {
    syncTogglesFromDropdowns();
    applyFilters();
});

const openAdd = () => {
    editingData.value = null;
    isAdding.value = true;
};

const openEdit = (user) => {
    editingData.value = user;
    isAdding.value = true;
};

const openView = (user) => {
    viewingData.value = user;
    isViewing.value = true;
};

const closeForm = () => {
    isAdding.value = false;
    editingData.value = null;
};

const isConfirmDeleteOpen = ref(false);
const userToDelete = ref(null);

const deleteUser = (user) => {
    userToDelete.value = user;
    isConfirmDeleteOpen.value = true;
};

const executeDelete = () => {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value.id), {
            onSuccess: () => {
                isConfirmDeleteOpen.value = false;
                userToDelete.value = null;
            }
        });
    }
};

const selectedItems = ref([]);

watch(() => props.users.data, () => {
    selectedItems.value = [];
}, { deep: true });

const canDeleteUser = (user) => {
    const currentUser = page.props.auth.user;
    if (!currentUser) return false;
    
    // Developer cannot delete other developers
    const isCurrentUserDeveloper = currentUser.roles?.includes('Developer');
    const isTargetDeveloper = user.roles?.some(r => r.name === 'Developer');
    
    if (isCurrentUserDeveloper && isTargetDeveloper) {
        return false;
    }

    return currentUser.permissions?.includes('delete_users') && 
        (currentUser.roles?.some(r => ['Superadmin', 'Developer'].includes(r)) || 
        user.division_id === currentUser.division_id);
};

const selectAll = computed({
    get: () => {
        const deletableItems = props.users.data.filter(canDeleteUser);
        return deletableItems.length > 0 && deletableItems.every(item => selectedItems.value.includes(item.id));
    },
    set: (val) => {
        if (val) {
            selectedItems.value = props.users.data.filter(canDeleteUser).map(item => item.id);
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
    router.delete(route('users.bulk_delete'), {
        data: { ids: selectedItems.value },
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmBulkDeleteOpen.value = false;
        }
    });
};

</script>

<template>
    <Head title="Users Management" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Users</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Manage system users</p>
                </div>
                <div>
                    <button 
                        v-if="$page.props.auth.user?.permissions?.includes('create_users')"
                        @click="openAdd"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-colors"
                    >
                        <PlusCircle class="w-5 h-5" />
                        Add User
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white/60 backdrop-blur-xl border border-white p-4 rounded-3xl shadow-sm flex flex-col sm:flex-row gap-4 items-center justify-between flex-wrap">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto flex-wrap">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="w-5 h-5 text-slate-400" />
                        </div>
                        <input 
                            v-model="search"
                            type="text" 
                            class="w-full pl-10 pr-4 py-2 bg-white/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="Search by name, username, email..."
                        >
                    </div>

                    <!-- Division & Area Filters (Admin / Superadmin / Developer) -->
                    <DivisionAreaFilter
                        v-if="canFilterDivisionArea"
                        v-model:divisionId="divisionId"
                        v-model:areaId="areaId"
                        :divisions="divisions"
                        :areas="areas"
                    />
                </div>
                <div class="flex items-center gap-6" v-if="authUser?.division_id">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500 font-medium">My Division Only</span>
                        <Toggle v-model="division_only" class="toggle-blue" />
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
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100/50 transition-colors" @click="sortBy('id')">
                                    <div class="flex items-center gap-1">
                                        ID
                                        <span v-if="sort_field === 'id'">
                                            <ChevronUp v-if="sort_direction === 'asc'" class="w-4 h-4" />
                                            <ChevronDown v-else class="w-4 h-4" />
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100/50 transition-colors" @click="sortBy('first_name')">
                                    <div class="flex items-center gap-1">
                                        First Name
                                        <span v-if="sort_field === 'first_name'">
                                            <ChevronUp v-if="sort_direction === 'asc'" class="w-4 h-4" />
                                            <ChevronDown v-else class="w-4 h-4" />
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100/50 transition-colors" @click="sortBy('last_name')">
                                    <div class="flex items-center gap-1">
                                        Last Name
                                        <span v-if="sort_field === 'last_name'">
                                            <ChevronUp v-if="sort_direction === 'asc'" class="w-4 h-4" />
                                            <ChevronDown v-else class="w-4 h-4" />
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Division</th>
                                <th class="px-6 py-4">Area</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4 w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-center">
                                    <input 
                                        v-if="canDeleteUser(user)"
                                        type="checkbox" 
                                        :value="user.id" 
                                        v-model="selectedItems"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ user.id }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-blue-600 cursor-pointer hover:underline" @click="openView(user)">{{ user.first_name }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-blue-600 cursor-pointer hover:underline" @click="openView(user)">{{ user.last_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ user.username }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ user.email }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span v-if="user.division" class="px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-bold">{{ user.division.div_name }}</span>
                                    <span v-else class="text-slate-400 italic">None</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span v-if="user.area" class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">{{ user.area.area_name }}</span>
                                    <span v-else class="text-slate-400 italic">None</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span v-if="user.roles && user.roles.length" class="px-2 py-1 bg-purple-100 text-purple-700 rounded-md text-xs font-bold">{{ user.roles[0].name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            v-if="$page.props.auth.user?.permissions?.includes('edit_users') && ($page.props.auth.user?.roles?.some(r => ['Superadmin', 'Developer'].includes(r)) || user.division_id === $page.props.auth.user?.division_id)"
                                            @click="openEdit(user)" 
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors"
                                            title="Edit"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button 
                                            v-if="canDeleteUser(user)"
                                            @click="deleteUser(user)" 
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                        <a 
                                            v-if="$page.props.auth.user?.roles?.includes('Developer') && user.id !== $page.props.auth.user.id"
                                            :href="route('impersonate.start', user.id)" 
                                            class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-colors"
                                            title="Impersonate User"
                                        >
                                            <VenetianMask class="w-4 h-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <TablePagination :pagination="users" v-model:perPage="per_page" />
            </div>
        </div>

        <FloatingBulkDeleteButton :count="selectedItems.length" @delete="handleBulkDelete" />

        <UserFormModal
            :show="isAdding"
            :editingData="editingData"
            :roles="roles"
            :divisions="divisions"
            :areas="areas"
            @close="closeForm"
        />

        <!-- View Modal -->
        <Modal :show="isViewing" @close="isViewing = false">
            <div class="p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-6">View User Details</h2>
                <div v-if="viewingData" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">User ID</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.id }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.username }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.first_name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.last_name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.email }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Contact Number</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.contact_number || 'N/A' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Division</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.division ? viewingData.division.div_name : 'N/A' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Area</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.area ? viewingData.area.area_name : 'N/A' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.roles && viewingData.roles.length ? viewingData.roles[0].name : 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="isViewing = false" class="px-4 py-2 text-sm font-bold text-white bg-slate-600 hover:bg-slate-700 rounded-xl transition-colors">Close</button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirm Modals -->
        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Delete User" 
            description="Are you sure you want to delete this user?" 
            confirmText="Delete"
            @close="isConfirmDeleteOpen = false; userToDelete = null" 
            @confirm="executeDelete" 
        />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Users" 
            :description="`Are you sure you want to delete ${selectedItems.length} users?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </InventoryLayout>
</template>
