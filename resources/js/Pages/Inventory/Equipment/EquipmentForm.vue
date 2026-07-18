<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const props = defineProps({
    editingData: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        required: true
    },
    divisions: {
        type: Array,
        required: true
    },
    areas: {
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
    remarks: '',
    division_id: '',
    area_id: ''
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
        
        // Auto-fill division and area for non-admins
        const user = usePage().props.auth?.user;
        if (user) {
            if (!user.roles?.some(r => ['Superadmin', 'Developer'].includes(r))) {
                form.division_id = user.division_id || '';
            }
            if (!user.roles?.some(r => ['Superadmin', 'Developer', 'Admin'].includes(r))) {
                form.area_id = user.area_id || '';
            }
        }
    }
}, { immediate: true });

watch([
    () => form.quantity_per_property_card,
    () => form.quantity_per_physical_count,
    () => form.unit_value
], ([propCard, physCount, unitValue]) => {
    const card = Number(propCard) || 0;
    const count = Number(physCount) || 0;
    const val = Number(unitValue) || 0;
    
    form.shortage_overage_qty = card - count;
    form.shortage_overage_value = parseFloat((form.shortage_overage_qty * val).toFixed(2));
    form.total_value = parseFloat((card * val).toFixed(2));
});

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
    checkRequired('serial_number', 'Serial Number');
    checkRequired('description', 'Description');
    checkRequired('status', 'Status');

    if (!form.unit_value || Number(form.unit_value) <= 0) {
        form.setError('unit_value', 'Value must be greater than zero.');
        hasError = true;
    }
    if (!form.quantity_per_property_card || Number(form.quantity_per_property_card) <= 0) {
        form.setError('quantity_per_property_card', 'Value must be greater than zero.');
        hasError = true;
    }
    if (!form.quantity_per_physical_count || Number(form.quantity_per_physical_count) <= 0) {
        form.setError('quantity_per_physical_count', 'Value must be greater than zero.');
        hasError = true;
    }

    if (hasError) return;

    form.transform((data) => ({
        ...data,
        date_acquired: data.date_acquired instanceof Date 
            ? data.date_acquired.toLocaleDateString('en-CA') 
            : data.date_acquired
    }));

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
            
            <!-- Row 1: Category | Division -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Category <span class="text-red-500">*</span></label>
                <select v-model="form.category" class="w-full bg-white border border-slate-300 shadow-sm rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
                    <option value="">Select Category</option>
                    <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                </select>
                <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Division <span class="text-red-500">*</span></label>
                <select v-model="form.division_id" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" :disabled="$page.props.auth.user?.division_id && !($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin'))">
                    <option value="">Select Division</option>
                    <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <div v-if="form.errors.division_id" class="text-red-500 text-xs mt-1">{{ form.errors.division_id }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Area <span class="text-red-500">*</span></label>
                <select v-model="form.area_id" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" :disabled="$page.props.auth.user?.area_id && !($page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin') || $page.props.auth.user?.roles?.includes('Admin'))">
                    <option value="">Select Area</option>
                    <option v-for="a in areas.filter(a => a.division_id == form.division_id)" :key="a.id" :value="a.id">{{ a.name || a.area_name }}</option>
                </select>
                <div v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</div>
            </div>

            <!-- Row 2: Article | Serial Number -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Article <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.article" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.article" class="text-red-500 text-xs mt-1">{{ form.errors.article }}</div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Serial Number <span class="text-red-500">*</span></label>
                <input type="text" v-model="form.serial_number" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.serial_number" class="text-red-500 text-xs mt-1">{{ form.errors.serial_number }}</div>
            </div>

            <!-- Row 3: Description -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Description <span class="text-red-500">*</span></label>
                <textarea rows="3" v-model="form.description" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
            </div>

            <!-- Row 4: Date Acquired | Property Number -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Date Acquired</label>
                <VueDatePicker 
                    v-model="form.date_acquired" 
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
                                :value="formatDate(form.date_acquired)" 
                                readonly 
                                class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 pl-10 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800 cursor-pointer"
                                placeholder="Select date"
                            />
                        </div>
                    </template>
                </VueDatePicker>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Property Number</label>
                <input type="text" v-model="form.property_number" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>

            <!-- Row 5: Unit Value | Unit of Measure -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit Value <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" v-model="form.unit_value" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.unit_value" class="text-red-500 text-xs mt-1">{{ form.errors.unit_value }}</div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Unit of Measure</label>
                <input type="text" v-model="form.unit_of_measure" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>

            <!-- Row 6: Qty per Property Card | Qty per Physical Count -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Property Card <span class="text-red-500">*</span></label>
                <input type="number" v-model="form.quantity_per_property_card" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.quantity_per_property_card" class="text-red-500 text-xs mt-1">{{ form.errors.quantity_per_property_card }}</div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Qty per Physical Count <span class="text-red-500">*</span></label>
                <input type="number" v-model="form.quantity_per_physical_count" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
                <div v-if="form.errors.quantity_per_physical_count" class="text-red-500 text-xs mt-1">{{ form.errors.quantity_per_physical_count }}</div>
            </div>

            <!-- Row 7: Shortage/Overage Qty | Shortage/Overage Value -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Qty</label>
                <input type="number" v-model="form.shortage_overage_qty" readonly class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-500 cursor-not-allowed" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Shortage/Overage Value</label>
                <input type="number" step="0.01" v-model="form.shortage_overage_value" readonly class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-500 cursor-not-allowed" />
            </div>

            <!-- Row 8: Total Value | Status -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Value</label>
                <input type="number" step="0.01" v-model="form.total_value" readonly class="w-full bg-slate-100 border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-500 cursor-not-allowed" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Status <span class="text-red-500">*</span></label>
                <select v-model="form.status" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800">
                    <option value="">Select Status</option>
                    <option value="Serviceable">Serviceable</option>
                    <option value="Unserviceable">Unserviceable</option>
                </select>
                <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
            </div>

            <!-- Row 9: End User | Remarks -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">End User</label>
                <input type="text" v-model="form.end_user" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Remarks</label>
                <textarea rows="1" v-model="form.remarks" class="w-full bg-white border border-slate-300 shadow-sm rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 text-slate-800"></textarea>
            </div>
            
            <!-- Actions -->
            <div class="sm:col-span-2 flex justify-end gap-3 mt-4">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-white/50 border border-white/80 rounded-lg disabled:opacity-50" :disabled="form.processing">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold shadow-xl shadow-slate-200 disabled:opacity-50" :disabled="form.processing">Save Record</button>
            </div>
        </form>
    </div>
</template>
