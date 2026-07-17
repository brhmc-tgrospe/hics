<script setup>
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

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
    role_name: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <InventoryLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-slate-800"
            >
                Profile Settings
            </h2>
        </template>

        <div class="py-2 w-full">
            <div class="space-y-6">
                <div
                    class="bg-white/50 backdrop-blur-md p-4 shadow-sm border border-white/60 sm:rounded-xl sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :division-name="division_name"
                        :role-name="role_name"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white/50 backdrop-blur-md p-4 shadow-sm border border-white/60 sm:rounded-xl sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- <div
                    class="bg-white/50 backdrop-blur-md p-4 shadow-sm border border-white/60 sm:rounded-xl sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div> -->
            </div>
        </div>
    </InventoryLayout>
</template>
