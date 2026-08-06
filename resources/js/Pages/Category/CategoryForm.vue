<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    activeTab: {
        type: String,
        default: 'equipment'
    }
});

const emit = defineEmits(['close', 'saved']);

const resolveInitialType = (tab) => {
    return tab === 'supplies' || tab === 'supply' ? 'supply' : 'equipment';
};

const form = useForm({
    type: resolveInitialType(props.activeTab),
    name: '',
    code: '',
    has_expiration_date: false,
});

watch(() => props.activeTab, (newTab) => {
    form.type = resolveInitialType(newTab);
    if (form.type !== 'supply') {
        form.has_expiration_date = false;
    }
});

const setType = (type) => {
    form.type = type;
    if (type !== 'supply') {
        form.has_expiration_date = false;
    }
    form.clearErrors('type');
};

const submit = () => {
    if (!form.name || form.name.trim() === '') {
        form.setError('name', 'Category name is required.');
        return;
    }

    form.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const savedType = form.type;
            form.reset();
            emit('saved', { type: savedType });
        },
    });
};
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
            <div>
                <h3 class="text-xl font-bold text-slate-900">Add Category</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Create a new category for equipment or supplies</p>
            </div>
            <button 
                type="button" 
                @click="$emit('close')" 
                class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors"
                aria-label="Close modal"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Category Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Category Type <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="setType('equipment')"
                        :class="[
                            'flex items-center justify-center gap-2.5 px-4 py-3 rounded-2xl border text-sm font-semibold transition-all',
                            form.type === 'equipment'
                                ? 'bg-blue-50/80 border-blue-600 text-blue-700 shadow-sm ring-2 ring-blue-500/20'
                                : 'bg-slate-50/70 border-slate-200 text-slate-600 hover:bg-slate-100 hover:border-slate-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Equipment</span>
                    </button>

                    <button
                        type="button"
                        @click="setType('supply')"
                        :class="[
                            'flex items-center justify-center gap-2.5 px-4 py-3 rounded-2xl border text-sm font-semibold transition-all',
                            form.type === 'supply'
                                ? 'bg-blue-50/80 border-blue-600 text-blue-700 shadow-sm ring-2 ring-blue-500/20'
                                : 'bg-slate-50/70 border-slate-200 text-slate-600 hover:bg-slate-100 hover:border-slate-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Supplies</span>
                    </button>
                </div>
                <div v-if="form.errors.type" class="mt-1.5 text-xs text-red-600 font-medium">
                    {{ form.errors.type }}
                </div>
            </div>

            <!-- Category Name Input -->
            <div>
                <label for="category_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    {{ form.type === 'supply' ? 'Supply' : 'Equipment' }} Category Name <span class="text-red-500">*</span>
                </label>
                <input
                    id="category_name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all shadow-inner"
                    :placeholder="form.type === 'supply' ? 'e.g. Medical and Surgical Supplies' : 'e.g. Information & Communication Technology'"
                />
                <div v-if="form.errors.name" class="mt-1.5 text-xs text-red-600 font-medium">
                    {{ form.errors.name }}
                </div>
            </div>

            <!-- Expiration Date Toggle (Supplies Only) -->
            <div v-if="form.type === 'supply'" class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 transition-all">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Has Expiration date?</span>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            Require expiration date for supplies under this category.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="form.has_expiration_date = !form.has_expiration_date"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                            form.has_expiration_date ? 'bg-blue-600' : 'bg-slate-300'
                        ]"
                        role="switch"
                        :aria-checked="form.has_expiration_date"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                form.has_expiration_date ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 mt-6">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl shadow-lg shadow-slate-900/10 focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-all disabled:opacity-50 flex items-center gap-2"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Save Category</span>
                </button>
            </div>
        </form>
    </div>
</template>
