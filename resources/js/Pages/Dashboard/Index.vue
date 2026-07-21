<template>
  <InventoryLayout>
    <div class="space-y-8">
      <div>
        <h2 class="text-xl font-bold text-slate-800">Hospital Overview</h2>
        <p class="text-xs text-slate-500 font-medium">Consolidated view of inventory</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white/60 backdrop-blur-md p-5 rounded-3xl border border-white/80 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-2">
            <HardDriveIcon class="w-3 h-3" /> Total Equipment
          </p>
          <h3 class="text-3xl font-bold text-slate-800">{{ equipment.length }}</h3>
        </div>
        <div class="bg-white/60 backdrop-blur-md p-5 rounded-3xl border border-white/80 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-2">
            <PackageIcon class="w-3 h-3" /> Total Supplies
          </p>
          <h3 class="text-3xl font-bold text-slate-800">{{ supplies.length }}</h3>
        </div>
        <div class="bg-white/60 backdrop-blur-md p-5 rounded-3xl border border-white/80 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-2">
            <ActivityIcon class="w-3 h-3 text-blue-600" /> Equipment Value
          </p>
          <h3 class="text-3xl font-bold text-blue-600">
            {{ formatCurrency(totalEquipmentValue) }}
          </h3>
        </div>
        <div class="bg-white/60 backdrop-blur-md p-5 rounded-3xl border border-white/80 shadow-sm">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-2">
            <ShieldIcon class="w-3 h-3 text-emerald-600" /> Supplies Value
          </p>
          <h3 class="text-3xl font-bold text-emerald-600">
            {{ formatCurrency(totalSuppliesValue) }}
          </h3>
        </div>
      </div>

      <!-- Discrepancy KPI Cards -->
      <div v-if="discrepancyMetrics" class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <div class="bg-amber-500/10 backdrop-blur-xl rounded-3xl border border-amber-500/20 shadow-2xl overflow-hidden">
          <div 
            @click="showDiscrepancyQty = !showDiscrepancyQty"
            class="p-5 cursor-pointer hover:bg-amber-500/20 transition-colors flex items-center justify-between"
          >
            <div>
              <p class="text-[10px] font-bold text-amber-600/80 uppercase tracking-widest mb-1 flex items-center gap-2">
                <ActivityIcon class="w-3 h-3" /> Discrepant Items
              </p>
              <h3 class="text-3xl font-bold text-amber-700">{{ discrepancyMetrics.count }}</h3>
            </div>
            <button type="button" class="text-amber-600/50 hover:text-amber-600 transition-colors">
              <ChevronUpIcon v-if="showDiscrepancyQty" class="w-6 h-6" />
              <ChevronDownIcon v-else class="w-6 h-6" />
            </button>
          </div>
          <Transition name="list">
            <div v-if="showDiscrepancyQty" class="border-t border-amber-500/20 bg-white/30">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                  <thead>
                    <tr class="border-b border-amber-200/50">
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700">Type</th>
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700">Item</th>
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700 text-right">Variance Qty</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-200/30">
                    <tr v-for="item in discrepancyMetrics.items" :key="item.type + item.id" class="hover:bg-amber-500/10 transition-colors">
                      <td class="px-4 py-3 font-medium text-slate-800">{{ item.type }}</td>
                      <td class="px-4 py-3 text-slate-800">{{ item.name }}</td>
                      <td class="px-4 py-3 text-right font-medium" :class="item.qty > 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ item.qty > 0 ? '+' : '' }}{{ item.qty }}
                      </td>
                    </tr>
                    <tr v-if="discrepancyMetrics.items.length === 0">
                      <td colspan="3" class="px-4 py-6 text-center text-slate-500 italic">No discrepancies found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Transition>
        </div>

        <div class="bg-amber-500/10 backdrop-blur-xl rounded-3xl border border-amber-500/20 shadow-2xl overflow-hidden">
          <div 
            @click="showDiscrepancyValue = !showDiscrepancyValue"
            class="p-5 cursor-pointer hover:bg-amber-500/20 transition-colors flex items-center justify-between"
          >
            <div>
              <p class="text-[10px] font-bold text-amber-600/80 uppercase tracking-widest mb-1 flex items-center gap-2">
                <ActivityIcon class="w-3 h-3" /> Net Discrepancy Value
              </p>
              <h3 class="text-3xl font-bold text-amber-700">
                {{ formatCurrency(discrepancyMetrics.value) }}
              </h3>
            </div>
            <button type="button" class="text-amber-600/50 hover:text-amber-600 transition-colors">
              <ChevronUpIcon v-if="showDiscrepancyValue" class="w-6 h-6" />
              <ChevronDownIcon v-else class="w-6 h-6" />
            </button>
          </div>
          <Transition name="list">
            <div v-if="showDiscrepancyValue" class="border-t border-amber-500/20 bg-white/30">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                  <thead>
                    <tr class="border-b border-amber-200/50">
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700">Type</th>
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700">Item</th>
                      <th class="px-4 py-3 font-medium uppercase text-[10px] tracking-wider text-amber-700 text-right">Variance Value</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-200/30">
                    <tr v-for="item in discrepancyMetrics.items" :key="item.type + item.id" class="hover:bg-amber-500/10 transition-colors">
                      <td class="px-4 py-3 font-medium text-slate-800">{{ item.type }}</td>
                      <td class="px-4 py-3 text-slate-800">{{ item.name }}</td>
                      <td class="px-4 py-3 text-right font-bold" :class="item.value > 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ formatCurrency(item.value) }}
                      </td>
                    </tr>
                    <tr v-if="discrepancyMetrics.items.length === 0">
                      <td colspan="3" class="px-4 py-6 text-center text-slate-500 italic">No discrepancies found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <div v-if="divisionTotals && divisionTotals.length > 0" class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Division Values</h3>
          <button type="button" v-if="divisionTotals.length > 5" @click="showAllDivisions = !showAllDivisions" class="text-slate-400 hover:text-slate-600 transition-colors">
            <ChevronUpIcon v-if="showAllDivisions" class="w-5 h-5" />
            <ChevronDownIcon v-else class="w-5 h-5" />
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead>
              <tr class="border-b border-slate-200/50">
                <th class="py-3 font-medium uppercase text-[10px] tracking-wider">Division Name</th>
                <th class="py-3 font-medium uppercase text-[10px] tracking-wider text-right">Equipment Value</th>
                <th class="py-3 font-medium uppercase text-[10px] tracking-wider text-right">Supplies Value</th>
                <th class="py-3 font-medium uppercase text-[10px] tracking-wider text-right text-indigo-600">Total Value</th>
              </tr>
            </thead>
            <TransitionGroup name="list" tag="tbody" class="divide-y divide-slate-200/50">
              <tr v-for="div in visibleDivisions" :key="div.id" class="hover:bg-white/30 transition-colors">
                <td class="py-3 font-medium text-slate-800">{{ div.name }}</td>
                <td class="py-3 text-right">{{ formatCurrency(div.equipment_value) }}</td>
                <td class="py-3 text-right">{{ formatCurrency(div.supplies_value) }}</td>
                <td class="py-3 text-right font-bold text-indigo-600">{{ formatCurrency(div.total) }}</td>
              </tr>
            </TransitionGroup>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Equipment by Category</h3>
            <button type="button" v-if="equipmentByCategoryList.length > 5" @click="showAllEquipmentCats = !showAllEquipmentCats" class="text-slate-400 hover:text-slate-600 transition-colors">
              <ChevronUpIcon v-if="showAllEquipmentCats" class="w-5 h-5" />
              <ChevronDownIcon v-else class="w-5 h-5" />
            </button>
          </div>
          <TransitionGroup name="list" tag="div" class="space-y-3 divide-y divide-white/40">
            <div v-for="cat in visibleEquipmentCats" :key="cat.code" class="flex justify-between items-center pt-3 first:pt-0 text-sm">
              <span class="text-slate-600 font-medium">{{ cat.name }}</span>
              <span class="font-bold text-slate-800">{{ cat.count }}</span>
            </div>
          </TransitionGroup>
        </div>
        <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Supplies by Category</h3>
            <button type="button" v-if="suppliesByCategoryList.length > 5" @click="showAllSupplyCats = !showAllSupplyCats" class="text-slate-400 hover:text-slate-600 transition-colors">
              <ChevronUpIcon v-if="showAllSupplyCats" class="w-5 h-5" />
              <ChevronDownIcon v-else class="w-5 h-5" />
            </button>
          </div>
          <TransitionGroup name="list" tag="div" class="space-y-3 divide-y divide-white/40">
            <div v-for="cat in visibleSupplyCats" :key="cat.code" class="flex justify-between items-center pt-3 first:pt-0 text-sm">
              <span class="text-slate-600 font-medium">{{ cat.name }}</span>
              <span class="font-bold text-slate-800">{{ cat.count }}</span>
            </div>
          </TransitionGroup>
        </div>
      </div>
    </div>
  </InventoryLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { PackageIcon, ActivityIcon, HardDriveIcon, ShieldIcon, ChevronDownIcon, ChevronUpIcon } from 'lucide-vue-next';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import { formatCurrency } from '@/utils/formatters.js';

const props = defineProps({
  equipment: Array,
  supplies: Array,
  equipmentCategories: Array,
  supplyCategories: Array,
  divisionTotals: Array,
  discrepancyMetrics: {
    type: Object,
    default: () => ({ count: 0, value: 0, items: [] })
  },
});

const totalEquipmentValue = computed(() => {
  return props.equipment.reduce((sum, item) => sum + (Number(item.total_value) || 0), 0);
});

const totalSuppliesValue = computed(() => {
  return props.supplies.reduce((sum, item) => sum + (Number(item.total_amount) || 0), 0);
});

const showAllDivisions = ref(false);
const showAllEquipmentCats = ref(false);
const showAllSupplyCats = ref(false);
const showDiscrepancyQty = ref(false);
const showDiscrepancyValue = ref(false);

const visibleDivisions = computed(() => {
  return showAllDivisions.value ? props.divisionTotals : props.divisionTotals.slice(0, 5);
});

const equipmentByCategoryList = computed(() => {
  return props.equipmentCategories.map(cat => ({
    code: cat.code,
    name: cat.name,
    count: props.equipment.filter(e => e.category === cat.code).length
  }));
});

const visibleEquipmentCats = computed(() => {
  return showAllEquipmentCats.value ? equipmentByCategoryList.value : equipmentByCategoryList.value.slice(0, 5);
});

const suppliesByCategoryList = computed(() => {
  return props.supplyCategories.map(cat => {
    const suppliesInCat = props.supplies.filter(s => s.category === cat.code);
    return {
      code: cat.code,
      name: cat.name,
      count: suppliesInCat.length,
      totalItems: suppliesInCat.reduce((sum, s) => sum + (Number(s.quantity) || 0), 0)
    };
  });
});

const visibleSupplyCats = computed(() => {
  return showAllSupplyCats.value ? suppliesByCategoryList.value : suppliesByCategoryList.value.slice(0, 5);
});
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(-15px);
}
</style>
