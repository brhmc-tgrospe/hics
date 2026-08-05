<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import TablePagination from '@/Components/TablePagination.vue';

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    isSuperadminOrDeveloper: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update-per-page']);

const selectedItems = ref([]);

// Clear selection when data changes (e.g., page change)
watch(() => props.categories.data, () => {
    selectedItems.value = [];
}, { deep: true });

const selectAll = computed({
    get: () => {
        const deletableItems = props.categories.data;
        return deletableItems.length > 0 && deletableItems.every(item => selectedItems.value.includes(item.id));
    },
    set: (val) => {
        if (val) {
            selectedItems.value = props.categories.data.map(item => item.id);
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
    router.delete(route('categories.bulk_delete'), {
        data: { ids: selectedItems.value },
        onSuccess: () => {
            selectedItems.value = [];
            isConfirmBulkDeleteOpen.value = false;
        }
    });
};
</script>

<template>
    <div class="flex-1 flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-900/5 border-b border-white/60">
                        <th v-if="isSuperadminOrDeveloper" class="px-6 py-4 w-12 text-center">
                            <input 
                                type="checkbox" 
                                v-model="selectAll"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Code</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Name</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    <tr v-if="categories.data.length === 0">
                        <td :colspan="isSuperadminOrDeveloper ? 4 : 3" class="px-6 py-8 text-center text-sm text-slate-500 font-medium">No categories found matching criteria.</td>
                    </tr>
                    <tr v-for="item in categories.data" :key="item.id" class="hover:bg-white/40 transition-colors">
                        <td v-if="isSuperadminOrDeveloper" class="px-6 py-4 text-center">
                            <input 
                                type="checkbox" 
                                :value="item.id" 
                                v-model="selectedItems"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-6 py-4 text-xs font-mono font-bold text-slate-500">{{ item.id }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ item.code }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ item.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TablePagination 
            :pagination="categories" 
            @update-per-page="(val) => $emit('update-per-page', val)" 
        />

        <FloatingBulkDeleteButton v-if="isSuperadminOrDeveloper" :count="selectedItems.length" @delete="handleBulkDelete" />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Categories" 
            :description="`Are you sure you want to delete ${selectedItems.length} categories?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </div>
</template>
