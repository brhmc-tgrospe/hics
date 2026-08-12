<template>
  <InventoryLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Bulk Edit Unit Values (Equipment)</h2>
          <p class="text-sm text-slate-500 font-medium mt-2">
            {{ equipment.length }} record{{ equipment.length !== 1 ? 's' : '' }} with missing unit values
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link 
            :href="route('equipment.index')" 
            class="px-4 py-2 bg-white/50 text-slate-700 border border-slate-300 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 hover:bg-slate-50 transition-colors"
          >
            <ArrowLeftIcon class="w-4 h-4" /> Back
          </Link>
          <button 
            @click="saveAll"
            :disabled="form.processing || editedCount === 0"
            class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 flex items-center gap-2 hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <SaveIcon class="w-4 h-4" />
            Save {{ editedCount }} Record{{ editedCount !== 1 ? 's' : '' }}
          </button>
        </div>
      </div>

      <!-- Info Banner -->
      <div v-if="equipment.length === 0" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
        <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-3" />
        <h3 class="text-lg font-bold text-green-800">All Clear!</h3>
        <p class="text-sm text-green-600 mt-1">All equipment records have unit values assigned.</p>
      </div>

      <!-- Table -->
      <div v-else class="bg-white/40 backdrop-blur-sm border border-white/60 rounded-2xl shadow-lg shadow-slate-200/50 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/80">
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">#</th>
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Property No.</th>
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Article</th>
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Description</th>
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
                <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">On Hand</th>
                <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Division</th>
                <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest min-w-[160px]">
                  Unit Value <span class="text-red-500">*</span>
                </th>
                <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Computed Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="(item, index) in equipment" 
                :key="item.id"
                :class="[
                  'transition-colors',
                  editedValues[item.id] ? 'bg-blue-50/50' : 'hover:bg-slate-50/50'
                ]"
              >
                <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-sm font-bold text-slate-800 whitespace-nowrap">{{ item.property_number }}</td>
                <td class="px-4 py-3 text-sm font-bold text-slate-800">{{ item.article }}</td>
                <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px] truncate" :title="item.description">{{ item.description }}</td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold uppercase">{{ item.category }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-800 font-semibold text-right">{{ item.on_hand_per_count }}</td>
                <td class="px-4 py-3 text-xs text-slate-600">{{ item.division?.div_name || '—' }}</td>
                <td class="px-4 py-3">
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">₱</span>
                    <input 
                      type="number" 
                      step="0.01" 
                      min="0.01"
                      :value="editedValues[item.id] ?? ''"
                      @input="handleInput(item.id, $event.target.value)"
                      placeholder="Enter value"
                      class="w-full bg-white border rounded-lg pl-7 pr-3 py-1.5 text-sm text-right font-mono focus:outline-none focus:ring-2 transition-all"
                      :class="[
                        editedValues[item.id] 
                          ? 'border-blue-400 focus:ring-blue-300 bg-blue-50/30' 
                          : 'border-slate-300 focus:ring-slate-400',
                        errors[item.id] ? 'border-red-400 focus:ring-red-300 bg-red-50/30' : ''
                      ]"
                    />
                    <div v-if="errors[item.id]" class="text-red-500 text-[10px] mt-0.5">{{ errors[item.id] }}</div>
                  </div>
                </td>
                <td class="px-4 py-3 text-xs font-bold text-right" :class="computedTotal(item) ? 'text-emerald-700' : 'text-slate-300'">
                  {{ computedTotal(item) ? formatCurrency(computedTotal(item)) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Fill All Helper -->
      <div v-if="equipment.length > 0" class="bg-white/40 backdrop-blur-sm border border-white/60 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="flex-1">
          <p class="text-xs text-slate-500">
            <strong class="text-slate-700">Tip:</strong> Enter the unit value for each item above, then click Save. 
            Total amounts and shortage/overage values will be automatically computed.
          </p>
        </div>
        <div class="text-xs text-slate-400">
          {{ editedCount }} of {{ equipment.length }} filled
        </div>
      </div>
    </div>
  </InventoryLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import { ArrowLeftIcon, SaveIcon, CheckCircleIcon } from 'lucide-vue-next';

const props = defineProps({
  equipment: Array,
});

const editedValues = ref({});
const errors = ref({});

const handleInput = (id, value) => {
  if (value === '' || value === null) {
    delete editedValues.value[id];
    delete errors.value[id];
  } else {
    const num = parseFloat(value);
    if (isNaN(num) || num <= 0) {
      errors.value[id] = 'Must be greater than 0';
    } else {
      delete errors.value[id];
    }
    editedValues.value[id] = value;
  }
};

const editedCount = computed(() => {
  return Object.keys(editedValues.value).filter(id => {
    const val = parseFloat(editedValues.value[id]);
    return !isNaN(val) && val > 0;
  }).length;
});

const computedTotal = (item) => {
  const unitValue = parseFloat(editedValues.value[item.id]);
  if (isNaN(unitValue) || unitValue <= 0) return null;
  return unitValue * (item.on_hand_per_count || 0);
};

const formatCurrency = (value) => {
  return '₱' + Number(value || 0).toLocaleString(undefined, { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  });
};

const form = useForm({});

const saveAll = () => {
  // Validate all entries
  let hasErrors = false;
  const items = [];

  for (const [id, value] of Object.entries(editedValues.value)) {
    const num = parseFloat(value);
    if (isNaN(num) || num <= 0) {
      errors.value[id] = 'Must be greater than 0';
      hasErrors = true;
    } else {
      items.push({ id: parseInt(id), unit_value: num });
    }
  }

  if (hasErrors || items.length === 0) return;

  form.transform(() => ({ items })).put(route('equipment.bulk_update_values'), {
    preserveScroll: true,
  });
};
</script>
