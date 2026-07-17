<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    editingData: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    category: '',
    article: '',
    description: '',
    date_acquired: '',
    property_number: '',
    serial_number: '',
    unit_of_measure: '',
    unit_value: 0,
    total_value: 0,
    quantity_per_property_card: 0,
    quantity_per_physical_count: 0,
    shortage_overage_qty: 0,
    shortage_overage_value: 0,
    end_user: '',
    status: '',
    remarks: ''
});

watch(() => props.editingData, (newVal) => {
    if (newVal) {
        Object.keys(form).forEach(key => {
            if (newVal[key] !== undefined) {
                form[key] = newVal[key];
            }
        });
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    form.clearErrors();
    if (!form.article || form.article.toString().trim() === '') {
        form.setError('article', 'This field is required.');
        return;
    }

    if (props.editingData && props.editingData.id) {
        form.put(route('equipment.update', props.editingData.id), {
            onSuccess: () => {
                const articleName = form.article;
                form.reset();
                emit('saved', { article: articleName, mode: 'edited' });
                emit('close');
            }
        });
    } else {
        form.post(route('equipment.store'), {
            onSuccess: () => {
                const articleName = form.article;
                form.reset();
                emit('saved', { article: articleName, mode: 'added' });
                emit('close');
            }
        });
    }
};
</script>

<template>
    <div class="bg-white/60 p-6 rounded-3xl">
        <h3 class="text-lg font-bold text-slate-800 mb-4">{{ editingData ? 'Edit' : 'Add' }} Equipment Record</h3>
        <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Category</label>
                <select required v-model="form.category" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
                    <option value="">Select Category</option>
                    <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Article</label>
                <input type="text" v-model="form.article" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.article" class="text-red-500 text-xs mt-1">{{ form.errors.article }}</div>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Description</label>
                <textarea rows="3" required v-model="form.description" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Date Acquired</label>
                <input type="date" v-model="form.date_acquired" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Property Number</label>
                <input type="text" v-model="form.property_number" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Serial Number</label>
                <input type="text" v-model="form.serial_number" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit of Measure</label>
                <input type="text" v-model="form.unit_of_measure" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit Value</label>
                <input type="number" step="0.01" v-model="form.unit_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Value</label>
                <input type="number" step="0.01" v-model="form.total_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Property Card</label>
                <input type="number" v-model="form.quantity_per_property_card" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Physical Count</label>
                <input type="number" v-model="form.quantity_per_physical_count" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Qty</label>
                <input type="number" v-model="form.shortage_overage_qty" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Value</label>
                <input type="number" step="0.01" v-model="form.shortage_overage_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">End User</label>
                <input type="text" v-model="form.end_user" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Status</label>
                <select v-model="form.status" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
                    <option value="">Select Status</option>
                    <option value="Serviceable">Serviceable</option>
                    <option value="Unserviceable">Unserviceable</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Remarks</label>
                <textarea rows="2" v-model="form.remarks" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
            </div>
            
            <div class="sm:col-span-2 flex justify-end gap-3 mt-4">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-white/50 border border-white/80 rounded-lg disabled:opacity-50" :disabled="form.processing">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold shadow-xl shadow-slate-200 disabled:opacity-50" :disabled="form.processing">Save Record</button>
            </div>
        </form>
    </div>
</template>
