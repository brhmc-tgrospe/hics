<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusCircle, Search, Edit, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const per_page = ref(props.filters.per_page || 10);
const isAdding = ref(false);
const editingData = ref(null);

const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    contact_number: '',
    department_id: '',
    role: '',
    password: '',
});

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: search.value,
        per_page: per_page.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, per_page], applyFilters);

const openAdd = () => {
    editingData.value = null;
    form.value = {
        first_name: '',
        last_name: '',
        username: '',
        email: '',
        contact_number: '',
        department_id: '',
        role: '',
        password: '',
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
        department_id: user.department_id,
        role: user.roles && user.roles.length ? user.roles[0].name : '',
        password: '',
    };
    isAdding.value = true;
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

const deleteUser = (user) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('users.destroy', user.id));
    }
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
                                <th class="px-6 py-4">First Name</th>
                                <th class="px-6 py-4">Last Name</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4 w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ user.first_name }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ user.last_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ user.username }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ user.email }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span v-if="user.department" class="px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-bold">{{ user.department.dept_name }}</span>
                                    <span v-else class="text-slate-400 italic">None</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span v-if="user.roles && user.roles.length" class="px-2 py-1 bg-purple-100 text-purple-700 rounded-md text-xs font-bold">{{ user.roles[0].name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            v-if="$page.props.auth.permissions.includes('edit_users')"
                                            @click="openEdit(user)" 
                                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors"
                                            title="Edit"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button 
                                            v-if="$page.props.auth.permissions.includes('delete_users')"
                                            @click="deleteUser(user)" 
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/50 flex flex-wrap justify-center gap-1">
                    <Link
                        v-for="(link, i) in users.links"
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
                        
                        <div v-if="$page.props.auth.user.permissions.includes('assign_department') || $page.props.auth.user.roles.find(r => ['Superadmin', 'Developer'].includes(r.name))">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Department</label>
                            <select v-model="form.department_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Select Department</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.dept_name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                            <select v-model="form.role" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                <option value="">Select Role</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">
                                Password 
                                <span v-if="editingData" class="text-xs font-normal text-slate-400">(Leave blank to keep current)</span>
                            </label>
                            <input v-model="form.password" type="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" :required="!editingData" minlength="6">
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
    </InventoryLayout>
</template>
