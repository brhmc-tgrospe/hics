<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    activeTab: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

// Map plural tabs to singular type
const getType = () => {
    return props.activeTab === 'supplies' ? 'supply' : 'equipment';
};

const form = useForm({
    code: '',
    name: '',
    type: getType(),
});

const submit = () => {
    form.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
        },
    });
};
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-900">Add Category</h3>
            <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="code" class="block text-sm font-semibold text-slate-700 mb-1">Code</label>
                <input
                    id="code"
                    v-model="form.code"
                    type="text"
                    required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="e.g. CAT-001"
                />
                <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
            </div>

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="e.g. General Equipment"
                />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 mt-6">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 flex items-center gap-2"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Save Category
                </button>
            </div>
        </form>
    </div>
</template>
