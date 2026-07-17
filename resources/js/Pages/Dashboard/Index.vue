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

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl p-6">
          <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Equipment by Category</h3>
          <div class="space-y-3 divide-y divide-white/40">
            <div v-for="cat in equipmentCategories" :key="cat.code" class="flex justify-between items-center pt-3 first:pt-0 text-sm">
              <span class="text-slate-600 font-medium">{{ cat.name }}</span>
              <span class="font-bold text-slate-800">{{ equipment.filter(e => e.category === cat.code).length }}</span>
            </div>
          </div>
        </div>
        <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl p-6">
          <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Supplies by Category</h3>
          <div class="space-y-3 divide-y divide-white/40">
            <div v-for="cat in supplyCategories" :key="cat.code" class="flex justify-between items-center pt-3 first:pt-0 text-sm">
              <span class="text-slate-600 font-medium">{{ cat.name }}</span>
              <span class="font-bold text-slate-800">{{ supplies.filter(s => s.category === cat.code).length }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </InventoryLayout>
</template>

<script setup>
import { computed } from 'vue';
import { PackageIcon, ActivityIcon, HardDriveIcon, ShieldIcon } from 'lucide-vue-next';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import { formatCurrency } from '@/utils/formatters.js';

const props = defineProps({
  equipment: Array,
  supplies: Array,
  equipmentCategories: Array,
  supplyCategories: Array,
});

const totalEquipmentValue = computed(() => {
  return props.equipment.reduce((sum, item) => sum + (Number(item.total_value) || 0), 0);
});

const totalSuppliesValue = computed(() => {
  return props.supplies.reduce((sum, item) => sum + (Number(item.total_amount) || 0), 0);
});

const equipmentByCategory = computed(() => {
  const grouped = {};
  props.equipmentCategories.forEach(cat => {
    grouped[cat.name] = props.equipment.filter(e => e.category === cat.code).length;
  });
  return grouped;
});

const suppliesByCategory = computed(() => {
  const grouped = {};
  props.supplyCategories.forEach(cat => {
    const suppliesInCat = props.supplies.filter(s => s.category === cat.code);
    grouped[cat.name] = {
      count: suppliesInCat.length,
      totalItems: suppliesInCat.reduce((sum, s) => sum + (Number(s.quantity) || 0), 0)
    };
  });
  return grouped;
});
</script>
