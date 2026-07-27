<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { Eye, EyeOff } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    editingData: Object,
    roles: Array,
    divisions: Array,
    areas: Array,
});

const emit = defineEmits(['close', 'submit']);
const page = usePage();

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

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.editingData) {
            form.value = {
                first_name: props.editingData.first_name,
                last_name: props.editingData.last_name,
                username: props.editingData.username,
                email: props.editingData.email,
                contact_number: props.editingData.contact_number,
                division_id: props.editingData.division_id,
                area_id: props.editingData.area_id,
                role: props.editingData.roles && props.editingData.roles.length ? props.editingData.roles[0].name : '',
                password: '',
                password_confirmation: '',
            };
        } else {
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
        }
        showPassword.value = false;
        showConfirmPassword.value = false;
    }
});

const submit = () => {
    emit('submit', form.value);
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-6">{{ editingData ? 'Edit User' : 'Add New User' }}</h2>
            
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">First Name</label>
                            <input v-model="form.first_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Last Name</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
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
                        <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                        <select v-model="form.role" class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="">Select Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
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
                    <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-200 transition-colors">
                        {{ editingData ? 'Update User' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
