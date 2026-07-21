<script setup>
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateColumnPreferencesForm from './Partials/UpdateColumnPreferencesForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    division_name: {
        type: String,
    },
    area_name: {
        type: String,
    },
    role_name: {
        type: String,
    },
});

const activeTab = ref('info');
</script>

<template>
    <Head title="Profile" />

    <InventoryLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Profile Settings
            </h2>
        </template>

        <div class="py-2 w-full">
            <div class="space-y-6">
                <div class="bg-white/50 backdrop-blur-md p-4 shadow-sm border border-white/60 sm:rounded-xl">
                    <nav class="flex space-x-4 mb-4 border-b border-slate-200 pb-2">
                        <button
                            @click="activeTab = 'info'"
                            :class="[
                                'px-3 py-2 font-medium text-sm rounded-md transition-colors',
                                activeTab === 'info' ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            Info
                        </button>
                        <button
                            @click="activeTab = 'security'"
                            :class="[
                                'px-3 py-2 font-medium text-sm rounded-md transition-colors',
                                activeTab === 'security' ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            Security
                        </button>
                        <button
                            @click="activeTab = 'columns'"
                            :class="[
                                'px-3 py-2 font-medium text-sm rounded-md transition-colors',
                                activeTab === 'columns' ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            Columns
                        </button>
                    </nav>

                    <div v-show="activeTab === 'info'">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            :division-name="division_name"
                            :area-name="area_name"
                            :role-name="role_name"
                            class="max-w-xl"
                        />
                    </div>

                    <div v-show="activeTab === 'security'">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>

                    <div v-show="activeTab === 'columns'">
                        <UpdateColumnPreferencesForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </InventoryLayout>
</template>
