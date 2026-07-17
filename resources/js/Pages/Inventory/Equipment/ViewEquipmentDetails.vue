<template>
  <div class="bg-white/60 p-6 rounded-3xl">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-bold text-slate-800">Equipment Details</h3>
      <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Category</p>
        <p class="text-sm font-semibold text-slate-800">{{ getCategoryName(data.category) || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Article</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.article || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm sm:col-span-2">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Description</p>
        <p class="text-sm font-semibold text-slate-800 whitespace-pre-wrap">{{ data.description || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Date Acquired</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.date_acquired || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Property Number</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.property_number || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Serial Number</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.serial_number || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Unit of Measure</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.unit_of_measure || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Unit Value</p>
        <p class="text-sm font-semibold text-slate-800">{{ formatCurrency(data.unit_value) }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Value</p>
        <p class="text-sm font-semibold text-slate-800">{{ formatCurrency(data.total_value) }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Property Card</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.quantity_per_property_card }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Physical Count</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.quantity_per_physical_count }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Qty</p>
        <p class="text-sm font-semibold text-slate-800" :class="{'text-red-600': data.shortage_overage_qty < 0, 'text-green-600': data.shortage_overage_qty > 0}">{{ data.shortage_overage_qty }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Value</p>
        <p class="text-sm font-semibold text-slate-800" :class="{'text-red-600': data.shortage_overage_value < 0, 'text-green-600': data.shortage_overage_value > 0}">{{ formatCurrency(data.shortage_overage_value) }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">End User</p>
        <p class="text-sm font-semibold text-slate-800">{{ data.end_user || '-' }}</p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status</p>
        <p class="text-sm font-semibold text-slate-800">
          <span :class="['px-2 py-1 rounded-full text-[10px] font-bold uppercase', data.status === 'Serviceable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
            {{ data.status || 'Unknown' }}
          </span>
        </p>
      </div>
      <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm sm:col-span-2">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Remarks</p>
        <p class="text-sm font-semibold text-slate-800 whitespace-pre-wrap">{{ data.remarks || '-' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatCurrency } from '@/utils/formatters.js';

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    required: true
  }
});

defineEmits(['close']);

const getCategoryName = (id) => {
  const cat = props.categories.find(c => c.id === id);
  return cat ? cat.name : id;
};
</script>
