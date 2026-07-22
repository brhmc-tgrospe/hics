<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    activities: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);
const userRoles = computed(() => authUser.value?.roles || []);
const canDelete = computed(() => userRoles.value.includes('Developer') || userRoles.value.includes('Superadmin')); // Superadmin isn't supposed to permanently delete, wait, prompt said "Only the Developer should be able to delete the permanently logs by date range."
const isDeveloper = computed(() => userRoles.value.includes('Developer'));

const search = ref(props.filters.search || '');
const actionType = ref(props.filters.action_type || 'All');
const perPage = ref(props.filters.per_page ? Number(props.filters.per_page) : 10);
const dateFrom = ref(props.filters.date_from || null);
const dateTo = ref(props.filters.date_to || null);

const isDeleting = ref(false);
const deleteStartDate = ref(null);
const deleteEndDate = ref(null);

const applyFilters = debounce(() => {
    router.get(route('activity-logs.index'), {
        search: search.value,
        action_type: actionType.value,
        per_page: perPage.value,
        date_from: dateFrom.value ? (dateFrom.value instanceof Date ? dateFrom.value.toLocaleDateString('en-CA') : dateFrom.value) : null,
        date_to: dateTo.value ? (dateTo.value instanceof Date ? dateTo.value.toLocaleDateString('en-CA') : dateTo.value) : null
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, actionType, perPage, dateFrom, dateTo], applyFilters);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
};

const formatJustDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const isConfirmDeleteOpen = ref(false);

const openConfirmDelete = () => {
    if (!deleteStartDate.value || !deleteEndDate.value) {
        return;
    }
    isConfirmDeleteOpen.value = true;
};

const deleteLogs = () => {
    router.delete(route('activity-logs.destroy'), {
        data: {
            start_date: deleteStartDate.value instanceof Date ? deleteStartDate.value.toLocaleDateString('en-CA') : deleteStartDate.value,
            end_date: deleteEndDate.value instanceof Date ? deleteEndDate.value.toLocaleDateString('en-CA') : deleteEndDate.value
        },
        onSuccess: () => {
            isDeleting.value = false;
            isConfirmDeleteOpen.value = false;
            deleteStartDate.value = null;
            deleteEndDate.value = null;
        }
    });
};

const getEventColor = (event, description) => {
    if (description === 'Login') return 'bg-blue-100 text-blue-700';
    if (description === 'Logout') return 'bg-slate-100 text-slate-700';
    if (event === 'created') return 'bg-emerald-100 text-emerald-700';
    if (event === 'updated') return 'bg-amber-100 text-amber-700';
    if (event === 'deleted') return 'bg-red-100 text-red-700';
    return 'bg-gray-100 text-gray-700';
};

const getSubjectName = (log) => {
    if (log.description === 'Login' || log.description === 'Logout') return 'Authentication';
    if (!log.subject_type) return 'System';
    return log.subject_type.split('\\').pop();
};

const getCauserName = (log) => {
    if (!log.causer) return 'System';
    return `${log.causer.first_name} ${log.causer.last_name}`;
};
</script>

<template>
    <Head title="Activity Logs" />

    <InventoryLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Activity Logs</h2>
                    <p class="text-sm text-slate-500 font-medium mt-2">Track all actions performed within the system</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        v-if="isDeveloper"
                        @click="isDeleting = true"
                        class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-red-200 flex items-center gap-2 hover:bg-red-700 transition-colors w-full sm:w-auto justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Delete Logs
                    </button>
                </div>
            </div>

            <Modal :show="isDeleting" maxWidth="md" @close="isDeleting = false">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Permanently Delete Logs</h3>
                    <p class="text-sm text-slate-500 mb-4">Select a date range to permanently remove activity logs. This action cannot be undone.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date</label>
                            <VueDatePicker 
                                v-model="deleteStartDate" 
                                :enable-time-picker="false" 
                                auto-apply 
                                :format="formatJustDate"
                                :preview-format="formatJustDate"
                                class="w-full"
                            >
                                <template #trigger>
                                    <div class="relative w-full">
                                        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <input 
                                            :value="formatJustDate(deleteStartDate)" 
                                            readonly 
                                            class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 pl-10 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer"
                                            placeholder="Select start date"
                                        />
                                    </div>
                                </template>
                            </VueDatePicker>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">End Date</label>
                            <VueDatePicker 
                                v-model="deleteEndDate" 
                                :enable-time-picker="false" 
                                auto-apply 
                                :format="formatJustDate"
                                :preview-format="formatJustDate"
                                class="w-full"
                            >
                                <template #trigger>
                                    <div class="relative w-full">
                                        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <input 
                                            :value="formatJustDate(deleteEndDate)" 
                                            readonly 
                                            class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 pl-10 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer"
                                            placeholder="Select end date"
                                        />
                                    </div>
                                </template>
                            </VueDatePicker>
                        </div>
                        <div class="pt-4 flex justify-end gap-3">
                            <button @click="isDeleting = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancel</button>
                            <button @click="openConfirmDelete" :disabled="!deleteStartDate || !deleteEndDate" :class="(!deleteStartDate || !deleteEndDate) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-700 shadow-md'" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl transition-all">Delete</button>
                        </div>
                    </div>
                </div>
            </Modal>

            <ConfirmModal 
                :show="isConfirmDeleteOpen" 
                title="Delete Activity Logs" 
                description="Are you sure you want to permanently delete these logs? This action cannot be undone." 
                confirmText="Delete Logs"
                @close="isConfirmDeleteOpen = false" 
                @confirm="deleteLogs" 
            />

            <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl overflow-hidden flex flex-col">
                <div class="p-4 border-b border-white/60 bg-slate-900/5 flex flex-col lg:flex-row items-center justify-between gap-4">
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input 
                                type="text" 
                                placeholder="Global search..." 
                                v-model="search"
                                class="w-full pl-9 pr-4 py-2 bg-white/50 backdrop-blur border border-white/80 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
                            />
                        </div>
                        <select 
                            v-model="actionType"
                            class="w-full sm:w-auto bg-white/50 backdrop-blur border border-white/80 rounded-xl pl-4 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
                        >
                            <option value="All">All Actions</option>
                            <option value="Created">Created</option>
                            <option value="Updated">Updated</option>
                            <option value="Deleted">Deleted</option>
                            <option value="Login">Login</option>
                            <option value="Logout">Logout</option>
                        </select>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-44">
                            <VueDatePicker 
                                v-model="dateFrom" 
                                :enable-time-picker="false" 
                                auto-apply 
                                :format="formatJustDate"
                                :preview-format="formatJustDate"
                                :clearable="true"
                            >
                                <template #trigger>
                                    <div class="relative w-full">
                                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <input 
                                            :value="dateFrom ? formatJustDate(dateFrom) : ''" 
                                            readonly 
                                            class="w-full bg-white/50 backdrop-blur border border-white/80 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700 cursor-pointer"
                                            placeholder="From date"
                                        />
                                        <!-- Custom Clear Button when value exists -->
                                        <button 
                                            v-if="dateFrom" 
                                            @click.stop="dateFrom = null"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </VueDatePicker>
                        </div>
                        <div class="relative w-full sm:w-44">
                            <VueDatePicker 
                                v-model="dateTo" 
                                :enable-time-picker="false" 
                                auto-apply 
                                :format="formatJustDate"
                                :preview-format="formatJustDate"
                                :clearable="true"
                            >
                                <template #trigger>
                                    <div class="relative w-full">
                                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <input 
                                            :value="dateTo ? formatJustDate(dateTo) : ''" 
                                            readonly 
                                            class="w-full bg-white/50 backdrop-blur border border-white/80 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700 cursor-pointer"
                                            placeholder="To date"
                                        />
                                        <button 
                                            v-if="dateTo" 
                                            @click.stop="dateTo = null"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </VueDatePicker>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col">
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-900/5 border-b border-white/60">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">User</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Action</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Subject</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                <tr v-if="activities.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500 font-medium">No activity logs found.</td>
                                </tr>
                                <tr v-for="log in activities.data" :key="log.id" class="hover:bg-white/40 transition-colors">
                                    <td class="px-6 py-4 text-xs font-medium text-slate-600">{{ formatDate(log.created_at) }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ getCauserName(log) }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="['px-2 py-1 rounded-full text-[10px] font-bold uppercase', getEventColor(log.event, log.description)]">
                                            {{ log.description === 'Login' || log.description === 'Logout' ? log.description : log.event }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-bold text-slate-700">{{ getSubjectName(log) }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-500">
                                        <div class="max-w-md truncate" :title="JSON.stringify(log.properties, null, 2)">
                                            {{ log.description !== 'Login' && log.description !== 'Logout' ? log.description : 'Authentication event' }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <!-- Pagination Controls -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Rows per page:</span>
                            <select
                              v-model="perPage"
                              class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            >
                              <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end flex-1">
                            <div class="flex items-center gap-1">
                                <template v-for="(link, index) in activities.links" :key="index">
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
        </div>
    </InventoryLayout>
</template>
