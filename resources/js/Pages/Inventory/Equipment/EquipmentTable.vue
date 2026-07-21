<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency } from '@/utils/formatters.js';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useInventoryPermissions } from '@/Composables/useInventoryPermissions';
import { useInventoryTable } from '@/Composables/useInventoryTable';

const props = defineProps({
    equipment: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    // The following props are no longer strictly needed but kept if passed from parent
    isSuperadmin: { type: Boolean, default: false },
    isSecretary: { type: Boolean, default: false },
    userDivisionId: { type: Number, default: null },
    userAreaId: { type: Number, default: null }
});

const emit = defineEmits(['edit', 'view', 'update-per-page']);

const { canEditItem, canDeleteItem } = useInventoryPermissions();

const canEdit = (item) => canEditItem(item, 'edit_equipment');
const canDelete = (item) => canDeleteItem(item, 'delete_equipment');

const equipmentData = computed(() => props.equipment.data);

const {
    selectedItems,
    selectAll,
    isConfirmDeleteOpen,
    itemToDelete,
    handleDelete,
    executeDelete,
    isConfirmBulkDeleteOpen,
    handleBulkDelete,
    executeBulkDelete
} = useInventoryTable({
    items: equipmentData,
    canDeleteItem: canDelete,
    destroyRouteName: 'equipment.destroy',
    bulkDeleteRouteName: 'equipment.bulk_delete'
});

const getCategoryName = (id, categories) => {
    const cat = categories.find(c => c.id === id);
    return cat ? cat.name : id;
};

const userSettings = usePage().props.auth.user?.settings || {};
const visibleColumns = computed(() => {
    return userSettings.equipment_columns || ['article', 'category', 'property_number', 'unit_value', 'quantity', 'status'];
});

const isColumnVisible = (column) => {
    return visibleColumns.value.includes(column);
};
</script>
<template>
    <div class="flex-1 flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-900/5 border-b border-white/60">
                        <th class="px-6 py-4 w-12 text-center">
                            <input 
                                type="checkbox" 
                                v-model="selectAll"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                        </th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Article</th>
                        <th v-if="isColumnVisible('category')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
                        <th v-if="isColumnVisible('property_number')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Property No.</th>
                        <th v-if="isColumnVisible('unit_value')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Unit Val</th>
                        <th v-if="isColumnVisible('quantity')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Physical Qty</th>
                        <th v-if="isColumnVisible('status')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    <tr v-if="equipment.data.length === 0">
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-slate-500 font-medium">No equipment found matching criteria.</td>
                    </tr>
                    <tr v-for="item in equipment.data" :key="item.id" class="hover:bg-white/40 transition-colors">
                        <td class="px-6 py-4 text-center">
                            <input 
                                v-if="canDelete(item)"
                                type="checkbox" 
                                :value="item.id" 
                                v-model="selectedItems"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ item.article }}</td>
                        <td v-if="isColumnVisible('category')" class="px-6 py-4 text-xs text-slate-600 font-medium">{{ getCategoryName(item.category, categories) }}</td>
                        <td v-if="isColumnVisible('property_number')" class="px-6 py-4 text-xs font-mono font-bold text-blue-700">{{ item.property_number }}</td>
                        <td v-if="isColumnVisible('unit_value')" class="px-6 py-4 text-xs font-bold text-slate-800 text-right">{{ formatCurrency(item.unit_value) }}</td>
                        <td v-if="isColumnVisible('quantity')" class="px-6 py-4 text-xs text-slate-800 font-semibold text-right">{{ item.quantity_per_physical_count }}</td>
                        <td v-if="isColumnVisible('status')" class="px-6 py-4 text-center">
                            <span :class="['px-2 py-1 rounded-full text-[10px] font-bold uppercase', item.status === 'Serviceable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                {{ item.status || 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="$emit('view', item)" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors" title="View">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                            <button v-if="canEdit(item)" @click="$emit('edit', item)" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors ml-1" title="Edit">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button v-if="canDelete(item)" @click="handleDelete(item.id)" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors ml-1" title="Delete">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
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
                  :value="equipment.per_page"
                  @change="(e) => $emit('update-per-page', Number(e.target.value))"
                  class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
                  <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                </select>
            </div>
            
            <div class="flex items-center justify-end flex-1">
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in equipment.links" :key="index">
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

        <FloatingBulkDeleteButton :count="selectedItems.length" @delete="handleBulkDelete" />

        <ConfirmModal 
            :show="isConfirmDeleteOpen" 
            title="Delete Equipment" 
            description="Are you sure you want to delete this equipment record?" 
            confirmText="Delete"
            @close="isConfirmDeleteOpen = false; itemToDelete = null" 
            @confirm="executeDelete" 
        />

        <ConfirmModal 
            :show="isConfirmBulkDeleteOpen" 
            title="Delete Selected Equipment" 
            :description="`Are you sure you want to delete ${selectedItems.length} records?`" 
            confirmText="Delete Selected"
            @close="isConfirmBulkDeleteOpen = false" 
            @confirm="executeBulkDelete" 
        />
    </div>
</template>
