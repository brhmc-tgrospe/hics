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
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Article</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Stock No.</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Unit Val</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">On Hand Qty</th>
            <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
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
                    v-if="canDeleteItem(item)"
                    type="checkbox" 
                    :value="item.id" 
                    v-model="selectedItems"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
            </td>
            <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ item.article }}</td>
            <td class="px-6 py-4 text-xs text-slate-600 font-medium">{{ getCategoryName(item.category) }}</td>
            <td class="px-6 py-4 text-xs font-mono font-bold text-blue-700">{{ item.stock_number }}</td>
            <td class="px-6 py-4 text-xs font-bold text-slate-800 text-right">{{ formatCurrency(item.unit_value) }}</td>
            <td class="px-6 py-4 text-xs text-slate-800 font-semibold text-right">{{ item.on_hand_per_count }}</td>
            <td class="px-6 py-4 text-center">
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
              <button v-if="canEditItem(item)" @click="$emit('edit', item)" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors ml-1" title="Edit">
                <Edit2Icon class="w-4 h-4" />
              </button>
              <button v-if="canDeleteItem(item)" @click="$emit('delete', item.id)" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors ml-1" title="Delete">
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
  </div>
</template>

<script setup>
import { Edit2Icon, Trash2Icon, ChevronLeftIcon, ChevronRightIcon, EyeIcon } from 'lucide-vue-next';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatCurrency } from '@/utils/formatters.js';
import FloatingBulkDeleteButton from '@/Components/FloatingBulkDeleteButton.vue';

const props = defineProps({
  supplies: Object,
  categories: Array,
  isSuperadmin: {
    type: Boolean,
    default: false
  },
  isSecretary: {
    type: Boolean,
    default: false
  },
  userDivisionId: {
    type: Number,
    default: null
  },
  userAreaId: {
    type: Number,
    default: null
  },
  filters: Object,
});

defineEmits(['edit', 'delete', 'update-per-page', 'view']);

const page = usePage();
const userPermissions = computed(() => page.props.auth.user?.permissions || []);
const userRoles = computed(() => page.props.auth.user?.roles || []);
const isAdmin = computed(() => userRoles.value.includes('Admin'));
const isEncoder = computed(() => userRoles.value.includes('Encoder'));

const getCategoryName = (catId) => {
  const cat = props.categories.find(c => c.id === catId);
  return cat ? cat.name : catId;
};

const canEditItem = (item) => {
    if (props.isSuperadmin) return true;
    if (props.isSecretary) return false;
    if (!userPermissions.value.includes('edit_supplies')) return false;

    if (isAdmin.value) return item.division_id == props.userDivisionId;
    if (isEncoder.value) return item.division_id == props.userDivisionId && item.area_id == props.userAreaId;
    
    return false;
};

const canDeleteItem = (item) => {
    if (props.isSuperadmin) return true;
    if (props.isSecretary) return false;
    if (!userPermissions.value.includes('delete_supplies')) return false;

    if (isAdmin.value) return item.division_id == props.userDivisionId;
    if (isEncoder.value) return item.division_id == props.userDivisionId && item.area_id == props.userAreaId;
    
    return false;
};

const selectedItems = ref([]);

// Clear selection when data changes (e.g., page change)
watch(() => props.supplies.data, () => {
    selectedItems.value = [];
}, { deep: true });

const selectAll = computed({
    get: () => {
        const deletableItems = props.supplies.data.filter(canDeleteItem);
        return deletableItems.length > 0 && deletableItems.every(item => selectedItems.value.includes(item.id));
    },
    set: (val) => {
        if (val) {
            selectedItems.value = props.supplies.data.filter(canDeleteItem).map(item => item.id);
        } else {
            selectedItems.value = [];
        }
    }
});

const handleBulkDelete = () => {
    if (selectedItems.value.length === 0) return;
    if (confirm(`Are you sure you want to delete ${selectedItems.value.length} supplies?`)) {
        router.delete(route('supplies.bulk_delete'), {
            data: { ids: selectedItems.value },
            onSuccess: () => {
                selectedItems.value = [];
            }
        });
    }
};
</script>
