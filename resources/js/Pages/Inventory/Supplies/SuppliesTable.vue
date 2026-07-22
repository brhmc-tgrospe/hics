<template>
  <div class="flex-1 flex flex-col">
    <div class="overflow-x-auto">
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
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                <button @click="$emit('sort', 'article')" class="flex items-center gap-1 hover:text-slate-800 focus:outline-none">
                    Article
                    <span class="flex flex-col">
                        <svg class="w-2.5 h-2.5 -mb-1" :class="{ 'text-blue-600': sortField === 'article' && sortDirection === 'asc', 'text-slate-400': !(sortField === 'article' && sortDirection === 'asc') }" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path></svg>
                        <svg class="w-2.5 h-2.5" :class="{ 'text-blue-600': sortField === 'article' && sortDirection === 'desc', 'text-slate-400': !(sortField === 'article' && sortDirection === 'desc') }" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </span>
                </button>
            </th>
            <th v-if="isColumnVisible('category')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
            <th v-if="isColumnVisible('description')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Description</th>
            <th v-if="isColumnVisible('stock_number')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Stock No.</th>
            <th v-if="isColumnVisible('unit_of_measure')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">UOM</th>
            <th v-if="isColumnVisible('unit_value')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Unit Val</th>
            <th v-if="isColumnVisible('balance_per_card')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Bal Per Card</th>
            <th v-if="isColumnVisible('on_hand_per_count')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">On Hand Qty</th>
            <th v-if="isColumnVisible('shortage_overage_qty')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Short/Over Qty</th>
            <th v-if="isColumnVisible('shortage_overage_value')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Short/Over Val</th>
            <th v-if="isColumnVisible('total_amount')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Total Amt</th>
            <th v-if="isColumnVisible('status')" class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/40">
          <tr v-if="supplies.data.length === 0">
            <td colspan="8" class="px-6 py-8 text-center text-sm text-slate-500 font-medium">
              No supplies found matching criteria.
            </td>
          </tr>
          <tr v-for="item in supplies.data" :key="item.id" class="hover:bg-white/40 transition-colors">
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
            <td v-if="isColumnVisible('category')" class="px-6 py-4 text-xs text-slate-600 font-medium">{{ getCategoryName(item.category) }}</td>
            <td v-if="isColumnVisible('description')" class="px-6 py-4 text-xs text-slate-600">{{ item.description }}</td>
            <td v-if="isColumnVisible('stock_number')" class="px-6 py-4 text-xs font-mono font-bold text-blue-700">{{ item.stock_number }}</td>
            <td v-if="isColumnVisible('unit_of_measure')" class="px-6 py-4 text-xs text-slate-600">{{ item.unit_of_measure }}</td>
            <td v-if="isColumnVisible('unit_value')" class="px-6 py-4 text-xs font-bold text-slate-800 text-right">{{ formatCurrency(item.unit_value) }}</td>
            <td v-if="isColumnVisible('balance_per_card')" class="px-6 py-4 text-xs text-slate-800 font-semibold text-right">{{ item.balance_per_card }}</td>
            <td v-if="isColumnVisible('on_hand_per_count')" class="px-6 py-4 text-xs text-slate-800 font-semibold text-right">{{ item.on_hand_per_count }}</td>
            <td v-if="isColumnVisible('shortage_overage_qty')" class="px-6 py-4 text-xs text-slate-800 font-semibold text-right">{{ item.shortage_overage_qty }}</td>
            <td v-if="isColumnVisible('shortage_overage_value')" class="px-6 py-4 text-xs font-bold text-slate-800 text-right">{{ formatCurrency(item.shortage_overage_value) }}</td>
            <td v-if="isColumnVisible('total_amount')" class="px-6 py-4 text-xs font-bold text-slate-800 text-right">{{ formatCurrency(item.total_amount) }}</td>
            <td v-if="isColumnVisible('status')" class="px-6 py-4 text-center">
              <span 
                class="px-2 py-1 rounded-full text-[10px] font-bold uppercase"
                :class="item.status === 'Available' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'"
              >
                {{ item.status || 'Unknown' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="$emit('view', item)" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors" title="View">
                <EyeIcon class="w-4 h-4" />
              </button>
              <button v-if="canEdit(item)" @click="$emit('edit', item)" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors ml-1" title="Edit">
                <Edit2Icon class="w-4 h-4" />
              </button>
              <button v-if="canDelete(item)" @click="handleDelete(item.id)" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors ml-1" title="Delete">
                <Trash2Icon class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Controls -->
    <div class="px-6 py-4 border-t border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-slate-500">Rows per page:</span>
        <select
          :value="supplies.per_page"
          @change="(e) => $emit('update-per-page', Number(e.target.value))"
          class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >
          <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
        </select>
      </div>
      
      <div class="flex items-center justify-end flex-1">
        <div class="flex items-center gap-1">
            <template v-for="(link, index) in supplies.links" :key="index">
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
        title="Delete Supply" 
        description="Are you sure you want to delete this supply record?" 
        confirmText="Delete"
        @close="isConfirmDeleteOpen = false; itemToDelete = null" 
        @confirm="executeDelete" 
    />

    <ConfirmModal 
        :show="isConfirmBulkDeleteOpen" 
        title="Delete Selected Supplies" 
        :description="`Are you sure you want to delete ${selectedItems.length} records?`" 
        confirmText="Delete Selected"
        @close="isConfirmBulkDeleteOpen = false" 
        @confirm="executeBulkDelete" 
    />
  </div>
</template>

<script setup>
import { Edit2Icon, Trash2Icon, EyeIcon } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency } from '@/utils/formatters.js';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useInventoryPermissions } from '@/Composables/useInventoryPermissions';
import { useInventoryTable } from '@/Composables/useInventoryTable';

const props = defineProps({
    supplies: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    // Keep these if passed by parent
    isSuperadmin: { type: Boolean, default: false },
    isSecretary: { type: Boolean, default: false },
    userDivisionId: { type: Number, default: null },
    userAreaId: { type: Number, default: null },
    filters: Object,
    sortField: { type: String, default: 'id' },
    sortDirection: { type: String, default: 'desc' }
});

const emit = defineEmits(['edit', 'update-per-page', 'view', 'sort']);

const { canEditItem, canDeleteItem } = useInventoryPermissions();

const canEdit = (item) => canEditItem(item, 'edit_supplies');
const canDelete = (item) => canDeleteItem(item, 'delete_supplies');

const suppliesData = computed(() => props.supplies.data);

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
    items: suppliesData,
    canDeleteItem: canDelete,
    destroyRouteName: 'supplies.destroy',
    bulkDeleteRouteName: 'supplies.bulk_delete'
});

const getCategoryName = (catId) => {
    const cat = props.categories.find(c => c.id === catId);
    return cat ? cat.name : catId;
};

const userSettings = usePage().props.auth.user?.settings || {};
const visibleColumns = computed(() => {
    return userSettings.supplies_columns || [
        'article', 'category', 'description', 'stock_number', 'unit_of_measure', 
        'unit_value', 'balance_per_card', 'on_hand_per_count', 'shortage_overage_qty', 
        'shortage_overage_value', 'total_amount', 'status'
    ];
});

const isColumnVisible = (column) => {
    return visibleColumns.value.includes(column);
};
</script>
