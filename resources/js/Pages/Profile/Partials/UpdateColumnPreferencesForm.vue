<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Transition } from 'vue';

const user = usePage().props.auth.user;
const currentSettings = user.settings || {};

const form = useForm({
    settings: {
        equipment_columns: currentSettings.equipment_columns || [
            'article', 'category', 'description', 'date_acquired', 'property_number', 
            'serial_number', 'unit_of_measure', 'unit_value', 'quantity_per_property_card', 
            'quantity_per_physical_count', 'shortage_overage_qty', 'shortage_overage_value', 
            'total_value', 'end_user', 'status', 'remarks'
        ],
        supplies_columns: currentSettings.supplies_columns || [
            'article', 'category', 'description', 'stock_number', 'unit_of_measure', 
            'unit_value', 'balance_per_card', 'on_hand_per_count', 'shortage_overage_qty', 
            'shortage_overage_value', 'total_amount', 'status'
        ],
    }
});

const equipmentColumns = [
    { key: 'article', label: 'Article' },
    { key: 'category', label: 'Category' },
    { key: 'description', label: 'Description' },
    { key: 'date_acquired', label: 'Date Acquired' },
    { key: 'property_number', label: 'Property Number' },
    { key: 'serial_number', label: 'Serial Number' },
    { key: 'unit_of_measure', label: 'Unit of Measure' },
    { key: 'unit_value', label: 'Unit Value' },
    { key: 'quantity_per_property_card', label: 'Qty per Property Card' },
    { key: 'quantity_per_physical_count', label: 'Qty per Physical Count' },
    { key: 'shortage_overage_qty', label: 'Shortage/Overage Qty' },
    { key: 'shortage_overage_value', label: 'Shortage/Overage Value' },
    { key: 'total_value', label: 'Total Value' },
    { key: 'end_user', label: 'End User' },
    { key: 'status', label: 'Status' },
    { key: 'remarks', label: 'Remarks' },
];

const suppliesColumns = [
    { key: 'article', label: 'Article' },
    { key: 'category', label: 'Category' },
    { key: 'description', label: 'Description' },
    { key: 'stock_number', label: 'Stock Number' },
    { key: 'unit_of_measure', label: 'Unit of Measure' },
    { key: 'unit_value', label: 'Unit Value' },
    { key: 'balance_per_card', label: 'Balance per Card' },
    { key: 'on_hand_per_count', label: 'On-hand per Count' },
    { key: 'shortage_overage_qty', label: 'Shortage/Overage Qty' },
    { key: 'shortage_overage_value', label: 'Shortage/Overage Value' },
    { key: 'total_amount', label: 'Total Amount' },
    { key: 'status', label: 'Status' },
];

const saveSettings = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-slate-900">Column Preferences</h2>

            <p class="mt-1 text-sm text-slate-600">
                Customize which columns are visible in the Equipment and Supplies inventory tables.
            </p>
        </header>

        <form @submit.prevent="saveSettings" class="mt-6 space-y-6">
            <div>
                <h3 class="text-md font-medium text-slate-900 mb-3">Equipment Table Columns</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="column in equipmentColumns" :key="column.key" class="flex items-center">
                        <input
                            type="checkbox"
                            :id="'eq_' + column.key"
                            :value="column.key"
                            v-model="form.settings.equipment_columns"
                            :disabled="column.key === 'article'"
                            class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        />
                        <label :for="'eq_' + column.key" class="ml-2 text-sm text-slate-700">
                            {{ column.label }}
                        </label>
                    </div>
                </div>
            </div>

            <hr class="border-white/60" />

            <div>
                <h3 class="text-md font-medium text-slate-900 mb-3">Supplies Table Columns</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="column in suppliesColumns" :key="column.key" class="flex items-center">
                        <input
                            type="checkbox"
                            :id="'sup_' + column.key"
                            :value="column.key"
                            v-model="form.settings.supplies_columns"
                            :disabled="column.key === 'article'"
                            class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        />
                        <label :for="'sup_' + column.key" class="ml-2 text-sm text-slate-700">
                            {{ column.label }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save Preferences</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-slate-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
