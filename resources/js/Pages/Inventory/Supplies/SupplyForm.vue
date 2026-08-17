<template>
  <div class="bg-white/60 p-6 rounded-3xl">
    <h3 class="text-lg font-bold text-slate-800 mb-4">{{ currentEditingId ? 'Edit' : 'Add' }} Supplies Record</h3>
    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      
      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Category <span class="text-red-500">*</span></label>
        <select required v-model="form.category" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
          <option value="">Select Category</option>
          <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
        </select>
        <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Division <span class="text-red-500">*</span></label>
        <select required v-model="form.division_id" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" :disabled="$page.props.auth.user?.division_id && !($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin'))">
          <option value="">Select Division</option>
          <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <div v-if="form.errors.division_id" class="text-red-500 text-xs mt-1">{{ form.errors.division_id }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Area <span class="text-red-500">*</span></label>
        <select required v-model="form.area_id" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" :disabled="$page.props.auth.user?.area_id && !($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin') || $page.props.auth.user?.roles?.includes('Admin'))">
          <option value="">Select Area</option>
          <option v-for="a in filteredAreas" :key="a.id" :value="a.id">{{ a.name || a.area_name }}</option>
        </select>
        <div v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Article/Product Name <span class="text-red-500">*</span></label>
        <input type="text" required v-model="form.article" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
        <div v-if="form.errors.article" class="text-red-500 text-xs mt-1">{{ form.errors.article }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Stock Number</label>
        <input type="text" v-model="form.stock_number" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div v-if="!isExpiryExempt">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Expiry Date <span class="text-red-500">*</span></label>
        <VueDatePicker 
            v-model="form.expiry_date" 
            :enable-time-picker="false" 
            auto-apply 
            :format="formatDate"
            :preview-format="formatDate"
            class="w-full"
        >
            <template #trigger>
                <div class="relative w-full">
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <input 
                        :value="formatDate(form.expiry_date)" 
                        readonly 
                        class="w-full rounded-xl border border-slate-300 bg-white text-slate-700 px-4 py-2 pl-10 text-sm shadow-sm focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-500 transition-all cursor-pointer"
                        placeholder="Select expiry date"
                    />
                </div>
            </template>
        </VueDatePicker>
        <div v-if="form.errors.expiry_date" class="text-red-500 text-xs mt-1">{{ form.errors.expiry_date }}</div>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Description <span class="text-red-500">*</span></label>
        <textarea rows="3" required v-model="form.description" class="w-full bg-slate-50 border border-slate-300 shadow-inner rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit Value <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" required v-model="form.unit_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit of Measure</label>
        <input type="text" v-model="form.unit_of_measure" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Balance per Card (Qty) <span class="text-red-500">*</span></label>
        <input type="number" required v-model="form.balance_per_card" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">On Hand per Count (Qty) <span class="text-red-500">*</span></label>
        <input type="number" required v-model="form.on_hand_per_count" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
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
import { useForm, usePage } from '@inertiajs/vue3';
import { watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { useSupplyCategoryRules } from '@/Composables/useSupplyCategoryRules';

const { isExpiryExemptSupply } = useSupplyCategoryRules();

const props = defineProps({
  editingData: {
    type: Object,
    default: null,
  },
  editingId: {
    type: Number,
    default: null,
  },
  initialData: {
    type: Object,
    default: null,
  },
  categories: {
    type: Array,
    required: true,
  },
  divisions: {
    type: Array,
    required: true,
  },
  areas: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['close', 'success', 'saved']);

const user = usePage().props.auth?.user;
const normalizeSupplyStatus = (status) => {
  if (!status) return '';
  const s = status.toString().trim().toLowerCase();
  if (s === 'available') return 'Available';
  if (s === 'depleted') return 'Depleted';
  return status;
};

const currentEditingData = computed(() => props.editingData || props.initialData || null);
const currentEditingId = computed(() => props.editingId || currentEditingData.value?.id || null);

const form = useForm({
  category: '',
  division_id: '',
  area_id: '',
  article: '',
  description: '',
  stock_number: '',
  expiry_date: null,
  unit_of_measure: '',
  unit_value: '',
  balance_per_card: '',
  on_hand_per_count: '',
  shortage_overage_qty: '',
  shortage_overage_value: '',
  total_amount: '',
  status: '',
});

const filteredAreas = computed(() => {
  return (props.areas || []).filter(a => {
    const matchesDivision = !form.division_id || a.division_id == form.division_id;
    const name = (a.name || a.area_name || '').toLowerCase().trim();
    return matchesDivision && name !== 'general area';
  });
});

watch(currentEditingData, (newVal) => {
  if (newVal) {
    form.category = newVal.category || '';
    form.division_id = newVal.division_id || (user && !user.roles?.some(r => ['Superadmin', 'Developer'].includes(r)) ? user.division_id : '');
    form.area_id = newVal.area_id || (user && !user.roles?.some(r => ['Superadmin', 'Developer', 'Admin'].includes(r)) ? user.area_id : '');
    form.article = newVal.article || '';
    form.description = newVal.description || '';
    form.stock_number = newVal.stock_number || '';
    form.expiry_date = newVal.expiry_date ? new Date(newVal.expiry_date) : null;
    form.unit_of_measure = newVal.unit_of_measure || '';
    form.unit_value = newVal.unit_value ?? '';
    form.balance_per_card = newVal.balance_per_card ?? '';
    form.on_hand_per_count = newVal.on_hand_per_count ?? '';
    form.shortage_overage_qty = newVal.shortage_overage_qty ?? '';
    form.shortage_overage_value = newVal.shortage_overage_value ?? '';
    form.total_amount = newVal.total_amount ?? '';
    form.status = normalizeSupplyStatus(newVal.status);
  } else {
    form.reset();
    form.clearErrors();
    if (user) {
      if (!user.roles?.some(r => ['Superadmin', 'Developer'].includes(r))) {
        form.division_id = user.division_id || '';
      }
      if (!user.roles?.some(r => ['Superadmin', 'Developer', 'Admin'].includes(r))) {
        const userAreaName = user.area_name?.toLowerCase().trim();
        form.area_id = user.is_general_area || userAreaName === 'general area' ? '' : (user.area_id || '');
      }
    }
  }
}, { deep: true, immediate: true });

const isExpiryExempt = computed(() => {
  return isExpiryExemptSupply(form.category, props.categories);
});

watch(isExpiryExempt, (isExempt) => {
  if (isExempt) {
    form.clearErrors('expiry_date');
  }
});

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

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
  form.total_amount = (uv * oh).toFixed(2);
}, { immediate: true });

const submit = () => {
  form.clearErrors();
  let hasError = false;

  const checkRequired = (field, name = null) => {
    if (!form[field] || form[field].toString().trim() === '') {
      form.setError(field, `${name || 'This field'} is required.`);
      hasError = true;
    }
  };

  checkRequired('category', 'Category');
  checkRequired('division_id', 'Division');
  checkRequired('area_id', 'Area');
  checkRequired('article', 'Article');
  checkRequired('description', 'Description');
  
  if (form.unit_value === '' || form.unit_value === null || Number(form.unit_value) <= 0) {
    form.setError('unit_value', 'Unit value must be greater than 0.');
    hasError = true;
  }
  
  if (!isExpiryExempt.value) {
    if (!form.expiry_date) {
      form.setError('expiry_date', 'Expiry Date is required.');
      hasError = true;
    }
  }

  if (hasError) return;

  form.transform((data) => ({
    ...data,
    expiry_date: data.expiry_date instanceof Date ? data.expiry_date.toLocaleDateString('en-CA') : data.expiry_date,
  }));

  const targetId = currentEditingId.value;
  if (targetId) {
    form.put(route('supplies.update', targetId), {
      onSuccess: () => {
        const articleName = form.article;
        form.reset();
        emit('success', { article: articleName, mode: 'edited' });
        emit('saved', { article: articleName, mode: 'edited' });
        emit('close');
      },
    });
  } else {
    form.post(route('supplies.store'), {
      onSuccess: () => {
        const articleName = form.article;
        form.reset();
        emit('success', { article: articleName, mode: 'added' });
        emit('saved', { article: articleName, mode: 'added' });
        emit('close');
      },
    });
  }
};
</script>
