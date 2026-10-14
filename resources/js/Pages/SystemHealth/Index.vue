<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Modal from '@/Components/Modal.vue';
import { 
    HardDrive, 
    Database, 
    Server, 
    Clock, 
    Download, 
    Trash2, 
    Play, 
    CheckCircle2, 
    AlertTriangle, 
    XCircle, 
    RefreshCw, 
    Calendar, 
    ShieldCheck, 
    Layers, 
    Cpu,
    Search,
    ArrowUpRight,
    X
} from 'lucide-vue-next';

const props = defineProps({
    health: Object,
    schedule: Object,
    formattedLastRun: String,
    backupHistories: Array,
    hasActiveBackup: Boolean,
});

// Polling interval for active backups
let pollInterval = null;

const startPollingIfNeeded = () => {
    if (props.hasActiveBackup && !pollInterval) {
        pollInterval = setInterval(() => {
            router.reload({ 
                only: ['backupHistories', 'hasActiveBackup', 'health'], 
                preserveScroll: true 
            });
        }, 3000);
    } else if (!props.hasActiveBackup && pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    startPollingIfNeeded();
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

// Manual refresh
const isRefreshing = ref(false);
const refreshData = () => {
    isRefreshing.value = true;
    router.reload({ 
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

// Schedule Form
const scheduleForm = useForm({
    is_enabled: Boolean(props.schedule?.is_enabled),
    frequency: props.schedule?.frequency || 'weekly',
    day_of_week: props.schedule?.day_of_week ?? 0,
    day_of_month: props.schedule?.day_of_month ?? 1,
    execution_time: props.schedule?.execution_time || '02:00',
});

const submitSchedule = () => {
    scheduleForm.put(route('system-health.schedule.update'), {
        preserveScroll: true,
    });
};

// Backup Creation
const isTriggeringBackup = ref(false);
const createBackup = (sync = false) => {
    isTriggeringBackup.value = true;
    router.post(route('system-health.backup.store'), { sync }, {
        preserveScroll: true,
        onFinish: () => {
            isTriggeringBackup.value = false;
            startPollingIfNeeded();
        }
    });
};

// Delete Backup Modal
const isDeleteModalOpen = ref(false);
const backupToDelete = ref(null);

const confirmDeleteBackup = (backup) => {
    backupToDelete.value = backup;
    isDeleteModalOpen.value = true;
};

const executeDeleteBackup = () => {
    if (!backupToDelete.value) return;

    router.delete(route('system-health.backup.destroy', backupToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            backupToDelete.value = null;
        }
    });
};

const daysOfWeek = [
    { value: 0, label: 'Sunday' },
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
];

const completedBackupsCount = computed(() => {
    return (props.backupHistories || []).filter(b => b.status === 'completed').length;
});

// Database Table Breakdown State
const isTableBreakdownOpen = ref(false);
const tableSearchQuery = ref('');

const filteredTables = computed(() => {
    const tables = props.health?.database?.tables || [];
    if (!tableSearchQuery.value.trim()) {
        return tables;
    }
    const q = tableSearchQuery.value.toLowerCase().trim();
    return tables.filter(t => t.name.toLowerCase().includes(q));
});
</script>

<template>
    <Head title="System Health & Database Backup" />

    <InventoryLayout>
        <div class="space-y-8">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/70 backdrop-blur-md p-6 rounded-2xl border border-white/80 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-blue-100 text-blue-700 rounded-xl">
                            <ShieldCheck class="w-6 h-6" />
                        </span>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">System Health & Database Backup</h1>
                            <p class="text-sm text-slate-500">
                                Live system monitoring, zero-lock MySQL backups, and automated schedule configuration.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        @click="refreshData" 
                        :disabled="isRefreshing"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition-colors shadow-sm disabled:opacity-50"
                    >
                        <RefreshCw :class="['w-4 h-4', isRefreshing ? 'animate-spin text-blue-600' : '']" />
                        Refresh Metrics
                    </button>

                    <button 
                        @click="createBackup(false)"
                        :disabled="hasActiveBackup || isTriggeringBackup"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <RefreshCw v-if="hasActiveBackup || isTriggeringBackup" class="w-4 h-4 animate-spin" />
                        <Play v-else class="w-4 h-4 fill-current" />
                        <span>{{ hasActiveBackup ? 'Backup Processing...' : 'Create Backup Now' }}</span>
                    </button>
                </div>
            </div>

            <!-- Active backup running alert banner -->
            <div v-if="hasActiveBackup" class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-2xl flex items-center gap-3 animate-pulse shadow-sm">
                <RefreshCw class="w-5 h-5 animate-spin text-blue-600 shrink-0" />
                <div class="text-sm">
                    <span class="font-bold">A database and system health backup is currently in progress.</span>
                    <span class="ml-1 text-blue-700">The bundle is being assembled with zero table locking. This table updates automatically every 3 seconds.</span>
                </div>
            </div>

            <!-- System Health Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Storage Card -->
                <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-white/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <HardDrive class="w-5 h-5" />
                                </span>
                                <h2 class="text-base font-bold text-slate-800">Drive Storage</h2>
                            </div>
                            <span 
                                :class="[
                                    'text-xs font-semibold px-2.5 py-1 rounded-full',
                                    health.storage.is_low_space 
                                        ? 'bg-amber-100 text-amber-800' 
                                        : 'bg-emerald-100 text-emerald-800'
                                ]"
                            >
                                {{ health.storage.is_low_space ? 'Low Disk Space' : 'Healthy' }}
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Free Space</span>
                                <span class="font-bold text-slate-800">{{ health.storage.free_formatted }}</span>
                            </div>
                            <!-- Progress bar -->
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div 
                                    :class="[
                                        'h-2.5 rounded-full transition-all duration-500',
                                        health.storage.used_percentage > 85 ? 'bg-amber-500' : 'bg-indigo-600'
                                    ]"
                                    :style="{ width: `${health.storage.used_percentage}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-slate-400">
                                <span>Used: {{ health.storage.used_formatted }} ({{ health.storage.used_percentage }}%)</span>
                                <span>Total: {{ health.storage.total_formatted }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Vitality Card -->
                <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-white/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                                    <Database class="w-5 h-5" />
                                </span>
                                <h2 class="text-base font-bold text-slate-800">Database Vitality</h2>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                                Connected
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                <p class="text-xs text-slate-400 font-medium">Database Size</p>
                                <p class="text-base font-bold text-slate-800 mt-0.5">{{ health.database.total_size_formatted }}</p>
                            </div>
                            <div 
                                @click="isTableBreakdownOpen = true"
                                class="bg-slate-50/80 p-3 rounded-xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/40 cursor-pointer transition-all group"
                                title="Click to view full database tables breakdown"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-slate-400 font-medium group-hover:text-emerald-700">Total Tables</p>
                                    <ArrowUpRight class="w-3.5 h-3.5 text-slate-300 group-hover:text-emerald-600 transition-colors" />
                                </div>
                                <p class="text-base font-bold text-slate-800 mt-0.5 group-hover:text-emerald-900">{{ health.database.total_tables }}</p>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                <p class="text-xs text-slate-400 font-medium">Estimated Rows</p>
                                <p class="text-base font-bold text-slate-800 mt-0.5">{{ Number(health.database.total_rows).toLocaleString() }}</p>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                <p class="text-xs text-slate-400 font-medium">Active Threads</p>
                                <p class="text-base font-bold text-slate-800 mt-0.5">{{ health.database.active_threads ?? 1 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 text-xs flex items-center justify-between gap-2">
                        <span class="text-slate-400 truncate">Engine: <span class="text-slate-600 font-semibold">MySQL {{ health.database.version }}</span></span>
                        <button 
                            @click="isTableBreakdownOpen = true"
                            type="button"
                            class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100/80 px-2.5 py-1 rounded-lg transition-colors border border-emerald-200/60 shrink-0"
                            title="Open Database Tables Breakdown"
                        >
                            <Layers class="w-3.5 h-3.5 text-emerald-600" />
                            <span>View Tables</span>
                        </button>
                    </div>
                </div>

                <!-- Server Environment Card -->
                <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-white/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                                    <Server class="w-5 h-5" />
                                </span>
                                <h2 class="text-base font-bold text-slate-800">Environment</h2>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">
                                PHP {{ health.server.php_version }}
                            </span>
                        </div>

                        <div class="space-y-2.5 text-sm mb-4">
                            <div class="flex justify-between py-1 border-b border-slate-100/80">
                                <span class="text-slate-500">Framework</span>
                                <span class="font-semibold text-slate-700">Laravel {{ health.application.laravel_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100/80">
                                <span class="text-slate-500">Memory Limit</span>
                                <span class="font-semibold text-slate-700">{{ health.server.memory_limit }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100/80">
                                <span class="text-slate-500">Queue Driver</span>
                                <span class="font-semibold text-slate-700 uppercase font-mono text-xs">{{ health.application.queue_connection }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Current RAM Use</span>
                                <span class="font-semibold text-slate-700">{{ health.server.current_memory_usage }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 text-xs text-slate-400 truncate">
                        OS: <span class="text-slate-600">{{ health.server.os_description }}</span>
                    </div>
                </div>
            </div>

            <!-- Automated Backup Schedule Configuration -->
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-white/80 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-4">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-purple-50 text-purple-600 rounded-xl">
                            <Calendar class="w-5 h-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Automated Backup Schedule</h2>
                            <p class="text-xs text-slate-500">Configure weekly or monthly automated snapshots during off-peak hours.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                            Strict Auto-Retention: 4 Backups Max
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitSchedule" class="mt-6 space-y-6">
                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            id="is_enabled" 
                            v-model="scheduleForm.is_enabled"
                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                        />
                        <label for="is_enabled" class="text-sm font-semibold text-slate-700 cursor-pointer">
                            Enable Automated Backups
                        </label>
                    </div>

                    <div :class="['grid grid-cols-1 md:grid-cols-3 gap-6 transition-opacity', scheduleForm.is_enabled ? 'opacity-100' : 'opacity-40 pointer-events-none']">
                        <!-- Frequency Selection -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Frequency
                            </label>
                            <select 
                                v-model="scheduleForm.frequency"
                                class="w-full text-sm border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 bg-slate-50/50"
                            >
                                <option value="weekly">Weekly (Specific Day of Week)</option>
                                <option value="monthly">Monthly (Specific Date of Month)</option>
                            </select>
                        </div>

                        <!-- Day Selector (Weekly vs Monthly) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                {{ scheduleForm.frequency === 'weekly' ? 'Day of the Week' : 'Day of the Month' }}
                            </label>

                            <!-- Weekly Day of Week -->
                            <select 
                                v-if="scheduleForm.frequency === 'weekly'"
                                v-model="scheduleForm.day_of_week"
                                class="w-full text-sm border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 bg-slate-50/50"
                            >
                                <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">
                                    {{ day.label }}
                                </option>
                            </select>

                            <!-- Monthly Date of Month -->
                            <select 
                                v-else
                                v-model="scheduleForm.day_of_month"
                                class="w-full text-sm border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 bg-slate-50/50"
                            >
                                <option v-for="d in 31" :key="d" :value="d">
                                    {{ d }}{{ d === 1 ? 'st' : d === 2 ? 'nd' : d === 3 ? 'rd' : 'th' }} of every month
                                </option>
                            </select>
                        </div>

                        <!-- Execution Time -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Execution Time (HH:MM, UTC+8)
                            </label>
                            <input 
                                type="time" 
                                v-model="scheduleForm.execution_time"
                                class="w-full text-sm border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 bg-slate-50/50"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <div class="text-xs text-slate-400 flex items-center gap-1.5">
                            <Clock class="w-4 h-4 text-slate-400" />
                            <span>Last executed: {{ formattedLastRun || 'Never' }}</span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="scheduleForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition-colors shadow-sm disabled:opacity-50"
                        >
                            {{ scheduleForm.processing ? 'Saving...' : 'Save Schedule Settings' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Backup Archives Table (Max 4 slots) -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white/80 shadow-sm overflow-hidden">
                <div class="p-6 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-800">Retained Backup Archives</h2>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                {{ completedBackupsCount }} / 4 Retained
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Each archive packages the raw MySQL dump, system health report (.json & .md), and SHA-256 verification manifest.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/70 border-b border-slate-100 text-xs text-slate-500 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="py-3 px-6">Archive Filename</th>
                                <th class="py-3 px-6">Date Created (UTC+8)</th>
                                <th class="py-3 px-6">File Size</th>
                                <th class="py-3 px-6">Triggered By</th>
                                <th class="py-3 px-6">Status</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-if="!backupHistories || backupHistories.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <Database class="w-8 h-8 mx-auto mb-2 opacity-30" />
                                    <p class="text-sm font-medium">No backups created yet.</p>
                                    <p class="text-xs text-slate-400 mt-1">Click "Create Backup Now" above to generate your first snapshot.</p>
                                </td>
                            </tr>

                            <tr v-for="backup in backupHistories" :key="backup.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-6 font-medium text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <Layers class="w-4 h-4 text-blue-600 shrink-0" />
                                        <span class="font-mono text-xs">{{ backup.filename }}</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-6 text-slate-600 text-xs">
                                    <div>{{ backup.created_at }}</div>
                                    <div class="text-[10px] text-slate-400">{{ backup.created_at_relative }}</div>
                                </td>

                                <td class="py-3.5 px-6 text-slate-700 font-semibold text-xs">
                                    {{ backup.file_size_formatted || '—' }}
                                </td>

                                <td class="py-3.5 px-6 text-xs text-slate-600">
                                    {{ backup.triggered_by_name }}
                                </td>

                                <td class="py-3.5 px-6">
                                    <span 
                                        v-if="backup.status === 'completed'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        Completed
                                    </span>
                                    <span 
                                        v-else-if="backup.status === 'running' || backup.status === 'pending'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 animate-pulse"
                                    >
                                        <RefreshCw class="w-3.5 h-3.5 animate-spin" />
                                        Processing...
                                    </span>
                                    <span 
                                        v-else-if="backup.status === 'failed'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                        :title="backup.error_message"
                                    >
                                        <XCircle class="w-3.5 h-3.5" />
                                        Failed
                                    </span>
                                </td>

                                <td class="py-3.5 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Download Button -->
                                        <a 
                                            v-if="backup.status === 'completed'"
                                            :href="route('system-health.backup.download', backup.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200/60 shadow-sm"
                                            title="Download Backup ZIP"
                                        >
                                            <Download class="w-3.5 h-3.5" />
                                            <span>Download</span>
                                        </a>

                                        <!-- Delete Button -->
                                        <button 
                                            v-if="backup.status !== 'running' && backup.status !== 'pending'"
                                            @click="confirmDeleteBackup(backup)"
                                            class="inline-flex items-center p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete Archive"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Confirm Deletion Modal -->
        <ConfirmModal 
            :show="isDeleteModalOpen"
            title="Delete Backup Archive"
            :description="`Are you sure you want to permanently delete archive '${backupToDelete?.filename}'? This action cannot be undone.`"
            confirm-text="Delete Archive"
            :is-danger="true"
            @close="isDeleteModalOpen = false"
            @confirm="executeDeleteBackup"
        />

        <!-- Database Tables Breakdown Modal -->
        <Modal :show="isTableBreakdownOpen" @close="isTableBreakdownOpen = false" max-width="4xl">
            <div class="p-6">
                <!-- Header matching uploaded screenshot -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">
                            <Layers class="w-5 h-5 text-emerald-600" />
                        </span>
                        <h3 class="text-base font-bold text-slate-800">Database Tables Breakdown</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-slate-500 font-normal">
                            {{ health.database.total_tables }} tables recorded via information_schema
                        </span>
                        <button 
                            @click="isTableBreakdownOpen = false" 
                            class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                            title="Close modal"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Search / filter bar -->
                <div class="mt-4 mb-3">
                    <div class="relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                        <input 
                            v-model="tableSearchQuery"
                            type="text"
                            placeholder="Filter tables by name..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50/60"
                        />
                    </div>
                </div>

                <!-- Tables Container & Table matching screenshot -->
                <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-sm bg-white">
                    <div class="max-h-[460px] overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#f8fafc] border-b border-slate-200/80 text-slate-600 font-semibold sticky top-0 z-10">
                                <tr>
                                    <th class="py-3 px-5 text-left font-semibold">Table</th>
                                    <th class="py-3 px-5 text-right font-semibold">Est. Rows</th>
                                    <th class="py-3 px-5 text-right font-semibold">Data Size</th>
                                    <th class="py-3 px-5 text-right font-semibold">Index Size</th>
                                    <th class="py-3 px-5 text-right font-semibold">Total Size</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr 
                                    v-for="table in filteredTables" 
                                    :key="table.name"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="py-3 px-5 font-mono text-slate-700 text-xs">
                                        {{ table.name }}
                                    </td>
                                    <td class="py-3 px-5 text-right text-slate-600 text-xs font-normal">
                                        {{ Number(table.rows).toLocaleString() }}
                                    </td>
                                    <td class="py-3 px-5 text-right text-slate-600 text-xs font-normal">
                                        {{ table.data_size_formatted }}
                                    </td>
                                    <td class="py-3 px-5 text-right text-slate-600 text-xs font-normal">
                                        {{ table.index_size_formatted }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-bold text-slate-900 text-xs">
                                        {{ table.total_size_formatted }}
                                    </td>
                                </tr>

                                <tr v-if="filteredTables.length === 0">
                                    <td colspan="5" class="py-8 text-center text-slate-400">
                                        No database tables found matching "{{ tableSearchQuery }}"
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer with summary and close button -->
                <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-4">
                        <span>Total Database Size: <strong class="text-slate-800">{{ health.database.total_size_formatted }}</strong></span>
                        <span>Estimated Total Rows: <strong class="text-slate-800">{{ Number(health.database.total_rows).toLocaleString() }}</strong></span>
                    </div>

                    <button 
                        @click="isTableBreakdownOpen = false"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </InventoryLayout>
</template>
