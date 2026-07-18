<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2, VenetianMask, Eye, EyeOff } from 'lucide-vue-next';
import Toggle from '@vueform/toggle';
import '@vueform/toggle/themes/default.css';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    divisions: Array,
    areas: Array,
});

const page = usePage();
const search = ref(props.filters.search || '');
const per_page = ref(props.filters.per_page || 10);
const division_only = ref(props.filters.division_only !== undefined ? (props.filters.division_only === 'true' || props.filters.division_only === true) : true);
const isAdding = ref(false);
const editingData = ref(null);
const isViewing = ref(false);
const viewingData = ref(null);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    contact_number: '',
    division_id: '',
    area_id: '',
    role: '',
    password: '',
    password_confirmation: '',
});

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: search.value,
        per_page: per_page.value,
        division_only: division_only.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, per_page, division_only], applyFilters);

const openAdd = () => {
    editingData.value = null;
    form.value = {
        first_name: '',
        last_name: '',
        username: '',
        email: '',
        contact_number: '',
        division_id: (!(page.props.auth?.user?.roles?.some(r => ['Superadmin', 'Developer'].includes(r)))) ? page.props.auth?.user?.division_id || '' : '',
        area_id: (!(page.props.auth?.user?.roles?.some(r => ['Superadmin', 'Developer', 'Admin'].includes(r)))) ? page.props.auth?.user?.area_id || '' : '',
        role: '',
        password: '',
        password_confirmation: '',
    };
    isAdding.value = true;
};

const openEdit = (user) => {
    editingData.value = user;
    form.value = {
        first_name: user.first_name,
        last_name: user.last_name,
        username: user.username,
        email: user.email,
        contact_number: user.contact_number,
        division_id: user.division_id,
        area_id: user.area_id,
        role: user.roles && user.roles.length ? user.roles[0].name : '',
        password: '',
        password_confirmation: '',
    };
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

const submit = () => {
    if (editingData.value) {
        router.put(route('users.update', editingData.value.id), form.value, {
            onSuccess: () => closeForm(),
        });
    } else {
        router.post(route('users.store'), form.value, {
            onSuccess: () => closeForm(),
        });
    }
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
    return page.props.auth.user?.permissions?.includes('delete_users') && 
        (page.props.auth.user?.roles?.some(r => ['Superadmin', 'Developer'].includes(r)) || 
        user.division_id === page.props.auth.user?.division_id);
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
            <div class="bg-white/60 backdrop-blur-xl border border-white p-4 rounded-3xl shadow-sm flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="relative w-full sm:w-96">
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
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2" v-if="$page.props.auth.user?.roles?.some(r => ['Admin'].includes(r))">
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
                                <th class="px-6 py-4">First Name</th>
                                <th class="px-6 py-4">Last Name</th>
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
                                            v-if="$page.props.auth.user?.permissions?.includes('delete_users') && ($page.props.auth.user?.roles?.some(r => ['Superadmin', 'Developer'].includes(r)) || user.division_id === $page.props.auth.user?.division_id)"
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
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                    No users found.
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
                            <template v-for="(link, i) in users.links" :key="i">
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
                <h2 class="text-xl font-bold text-slate-900 mb-6">{{ editingData ? 'Edit User' : 'Add User' }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                            <input v-model="form.first_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                            <input v-model="form.username" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Contact Number</label>
                            <input v-model="form.contact_number" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Division</label>
                            <select 
                                v-model="form.division_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!($page.props.auth.user?.permissions?.includes('assign_division') || $page.props.auth.user?.roles?.some(r => ['Superadmin', 'Developer'].includes(r)))"
                            >
                                <option value="">Select Division</option>
                                <option v-for="dept in divisions" :key="dept.id" :value="dept.id">{{ dept.div_name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Area</label>
                            <select 
                                v-model="form.area_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="$page.props.auth.user?.area_id && !($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin') || $page.props.auth.user?.roles?.includes('Admin'))"
                                required
                            >
                                <option value="">Select Area</option>
                                <option v-for="a in areas.filter(a => a.division_id == form.division_id)" :key="a.id" :value="a.id">{{ a.area_name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                            <select v-model="form.role" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                <option value="">Select Role</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                        </div>

                        <!-- Empty div to force the passwords to the next row (since grid is 2 cols) -->
                        <div class="hidden sm:block"></div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">
                                Password 
                                <span v-if="editingData" class="text-xs font-normal text-slate-400">(Leave blank to keep current)</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" v-model="form.password" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-10" :required="!editingData" minlength="6">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <Eye v-if="!showPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">
                                Confirm Password 
                            </label>
                            <div class="relative">
                                <input :type="showConfirmPassword ? 'text' : 'password'" v-model="form.password_confirmation" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-10" :required="!editingData || form.password.length > 0" minlength="6">
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <Eye v-if="!showConfirmPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeForm" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-200 transition-colors">
                            {{ editingData ? 'Update User' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- View Modal -->
        <Modal :show="isViewing" @close="isViewing = false">
            <div class="p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-6">View User Details</h2>
                <div v-if="viewingData" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.first_name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.last_name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                            <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700">{{ viewingData.username }}</div>
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
