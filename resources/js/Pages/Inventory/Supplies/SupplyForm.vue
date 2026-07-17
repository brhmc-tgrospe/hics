<template>
  <div class="bg-white/60 p-6 rounded-3xl">
    <h3 class="text-lg font-bold text-slate-800 mb-4">{{ editingId ? 'Edit' : 'Add' }} Supplies Record</h3>
    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      
      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Category</label>
        <select required v-model="form.category" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
          <option value="">Select Category</option>
          <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
        </select>
        <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Article</label>
        <input type="text" v-model="form.article" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
        <div v-if="form.errors.article" class="text-red-500 text-xs mt-1">{{ form.errors.article }}</div>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Stock Number</label>
        <input type="text" v-model="form.stock_number" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Description</label>
        <textarea rows="3" required v-model="form.description" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit Value</label>
        <input type="number" step="0.01" v-model="form.unit_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit of Measure</label>
        <input type="text" v-model="form.unit_of_measure" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Balance per Card (Qty)</label>
        <input type="number" v-model="form.balance_per_card" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">On Hand per Count (Qty)</label>
        <input type="number" v-model="form.on_hand_per_count" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Qty</label>
        <input type="number" readonly v-model="form.shortage_overage_qty" class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm text-slate-600 cursor-not-allowed focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Value</label>
        <input type="number" step="0.01" readonly v-model="form.shortage_overage_value" class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm text-slate-600 cursor-not-allowed focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Amount</label>
        <input type="number" step="0.01" readonly v-model="form.total_amount" class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm text-slate-600 cursor-not-allowed focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Status</label>
        <select v-model="form.status" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
          <option value="">Select Status</option>
          <option value="Available">Available</option>
          <option value="Depleted">Depleted</option>
        </select>
      </div>

      <div class="sm:col-span-2 flex justify-end gap-3 mt-4">
        <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-300 rounded-xl shadow-sm">Cancel</button>
        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 disabled:opacity-50">
          {{ form.processing ? 'Saving...' : 'Save Record' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  editingId: Number,
  initialData: Object,
  categories: Array,
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
  category: props.initialData.category || '',
  article: props.initialData.article || '',
  description: props.initialData.description || '',
  stock_number: props.initialData.stock_number || '',
  unit_of_measure: props.initialData.unit_of_measure || '',
  unit_value: props.initialData.unit_value || '',
  balance_per_card: props.initialData.balance_per_card || '',
  on_hand_per_count: props.initialData.on_hand_per_count || '',
  shortage_overage_qty: props.initialData.shortage_overage_qty || '',
  shortage_overage_value: props.initialData.shortage_overage_value || '',
  total_amount: props.initialData.total_amount || '',
  status: props.initialData.status || '',
});

watch([
  () => form.balance_per_card, 
  () => form.on_hand_per_count, 
  () => form.unit_value
], ([balance, onHand, unitValue]) => {
  const bal = Number(balance) || 0;
  const oh = Number(onHand) || 0;
  const uv = Number(unitValue) || 0;

  const shortageQty = bal - oh;
  form.shortage_overage_qty = shortageQty;
  form.shortage_overage_value = (shortageQty * uv).toFixed(2);
  form.total_amount = (uv * bal).toFixed(2);
}, { immediate: true });

const submit = () => {
  form.clearErrors();
  if (!form.article || form.article.toString().trim() === '') {
    form.setError('article', 'This field is required.');
    return;
  }

  if (props.editingId) {
    form.put(`/supplies/${props.editingId}`, {
      onSuccess: () => {
        const articleName = form.article;
        emit('success', { article: articleName, mode: 'edited' });
      },
    });
  } else {
    form.post('/supplies', {
      onSuccess: () => {
        const articleName = form.article;
        emit('success', { article: articleName, mode: 'added' });
      },
    });
  }
};
</script>
