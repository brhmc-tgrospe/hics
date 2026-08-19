<template>
  <Modal :show="show" maxWidth="3xl" @close="handleClose">
    <div class="p-6 sm:p-7 text-slate-800">
      <!-- Modal Header -->
      <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4 mb-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">{{ modalTitle }}</h3>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
              Upload your CSV file to batch import or update inventory records.
            </p>
          </div>
        </div>
        <button 
          @click="handleClose" 
          type="button" 
          class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Collapsible Field Instructions Dropdown -->
      <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50/70 overflow-hidden transition-all duration-200 shadow-sm">
        <button
          type="button"
          @click="isInstructionsOpen = !isInstructionsOpen"
          class="w-full px-4 py-3.5 flex items-center justify-between text-left hover:bg-slate-100/80 transition-colors focus:outline-none"
        >
          <div class="flex items-center gap-2.5">
            <span class="p-1 rounded-md bg-blue-100 text-blue-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </span>
            <div>
              <span class="text-sm font-bold text-slate-800">CSV Format & Field Guidelines</span>
              <span class="text-xs text-slate-500 ml-2 hidden sm:inline">(Click to {{ isInstructionsOpen ? 'hide' : 'view' }} required fields & examples)</span>
            </div>
          </div>
          <ChevronDown 
            :class="['w-5 h-5 text-slate-400 transition-transform duration-200', isInstructionsOpen ? 'rotate-180 text-blue-600' : '']" 
          />
        </button>

        <!-- Instructions Content -->
        <div v-show="isInstructionsOpen" class="px-4 pb-4 pt-1 border-t border-slate-200/80 bg-white">
          <div class="mb-3 mt-2 text-xs text-slate-600 space-y-1 bg-amber-50/70 border border-amber-200/60 p-3 rounded-xl">
            <p class="font-semibold text-amber-900 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              Important Instructions:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-amber-800 pl-1">
              <li>Header row (Line 1) must match the column names listed below exactly.</li>
              <li>Fields marked as <span class="font-bold text-red-600">Required</span> must not be empty or 0.</li>
              <li><strong>Stock Number</strong>: If provided and matches an existing record, the record will be updated. If left empty, a new record is created.</li>
            </ul>
          </div>

          <!-- Field Table -->
          <div class="max-h-[300px] overflow-y-auto rounded-xl border border-slate-200 shadow-inner">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-100 text-slate-600 font-semibold sticky top-0 z-10 border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3">Column Name</th>
                  <th class="py-2.5 px-3">Requirement</th>
                  <th class="py-2.5 px-3">Format / Constraint</th>
                  <th class="py-2.5 px-3">Example</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="field in fieldDefinitions" :key="field.name" class="hover:bg-slate-50/70 transition-colors">
                  <td class="py-2.5 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                    {{ field.name }}
                  </td>
                  <td class="py-2.5 px-3 whitespace-nowrap">
                    <span 
                      v-if="field.requirement === 'Required'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200"
                    >
                      Required
                    </span>
                    <span 
                      v-else-if="field.requirement === 'Conditional'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200"
                    >
                      Conditional
                    </span>
                    <span 
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                    >
                      Optional
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-slate-600">
                    {{ field.description }}
                    <div v-if="field.name === 'category' && categoriesList.length" class="mt-1 flex flex-wrap gap-1">
                      <span v-for="c in categoriesList.slice(0, 6)" :key="c.code" class="px-1.5 py-0.2 bg-slate-100 text-slate-600 text-[10px] font-mono rounded">
                        {{ c.code }}
                      </span>
                      <span v-if="categoriesList.length > 6" class="text-[10px] text-slate-400">+{{ categoriesList.length - 6 }} more</span>
                    </div>
                  </td>
                  <td class="py-2.5 px-3 font-mono text-slate-500 bg-slate-50/50">
                    {{ field.example }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Drag and Drop / File Browser Zone -->
      <div 
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'relative rounded-2xl border-2 border-dashed p-6 sm:p-8 text-center transition-all duration-200 flex flex-col items-center justify-center cursor-pointer',
          isDragging 
            ? 'border-blue-500 bg-blue-50/70 ring-4 ring-blue-100 scale-[0.99]' 
            : selectedFile 
              ? 'border-emerald-400 bg-emerald-50/30' 
              : 'border-slate-300 hover:border-slate-400 bg-slate-50/40 hover:bg-slate-50'
        ]"
        @click="triggerFileInput"
      >
        <input 
          type="file" 
          accept=".csv" 
          ref="fileInputRef" 
          class="hidden" 
          @change="handleFileSelect"
        />

        <div v-if="!selectedFile" class="space-y-3">
          <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center mx-auto shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-bold text-slate-800">
              Drag & Drop your CSV file here, or <span class="text-blue-600 hover:underline">Browse from device</span>
            </p>
            <p class="text-xs text-slate-400 mt-1">
              Supports .csv files up to 10MB
            </p>
          </div>
        </div>

        <!-- Selected File Preview -->
        <div v-else class="w-full flex items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-emerald-200 shadow-sm" @click.stop>
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="text-left min-w-0">
              <p class="text-sm font-bold text-slate-800 truncate">{{ selectedFile.name }}</p>
              <p class="text-xs text-slate-400">{{ formatFileSize(selectedFile.size) }} &bull; <span class="text-emerald-600 font-semibold">Ready to import</span></p>
            </div>
          </div>
          <button 
            type="button" 
            @click="removeSelectedFile" 
            class="text-slate-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors"
            title="Remove file"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Client error message -->
      <div v-if="errorMessage" class="mt-3 text-xs text-red-600 font-semibold flex items-center gap-1.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        {{ errorMessage }}
      </div>

      <!-- Modal Actions -->
      <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <button 
          type="button" 
          @click="handleClose" 
          class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-300 rounded-xl shadow-sm hover:bg-slate-50 transition-colors"
          :disabled="isUploading"
        >
          Cancel
        </button>
        <button 
          type="button" 
          @click="submitUpload" 
          :disabled="!selectedFile || isUploading"
          class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="isUploading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          {{ isUploading ? 'Importing CSV...' : 'Upload & Import' }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { X, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: 'supply', // 'supply' | 'equipment'
  },
  importRouteName: {
    type: String,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'success', 'error']);

const isInstructionsOpen = ref(false);
const isDragging = ref(false);
const selectedFile = ref(null);
const fileInputRef = ref(null);
const isUploading = ref(false);
const errorMessage = ref('');

const modalTitle = computed(() => {
  return props.type === 'equipment' ? 'Import Equipment CSV' : 'Import Supplies CSV';
});

const categoriesList = computed(() => props.categories || []);

const fieldDefinitions = computed(() => {
  if (props.type === 'equipment') {
    return [
      { name: 'category', requirement: 'Required', description: 'Equipment category code (e.g. medequip, ictequip)', example: 'medequip' },
      { name: 'description', requirement: 'Required', description: 'Detailed equipment specification and model', example: 'Infusion Pump Model IP-200' },
      { name: 'unit_value', requirement: 'Required', description: 'Numeric cost per unit. Must be strictly > 0', example: '35000.00' },
      { name: 'quantity_per_property_card', requirement: 'Required', description: 'Property card balance. Integer > 0', example: '1' },
      { name: 'quantity_per_physical_count', requirement: 'Required', description: 'Physical count inventory. Integer > 0', example: '1' },
      { name: 'division_id', requirement: 'Required', description: 'Numeric ID of assigned Division', example: '1' },
      { name: 'area_id', requirement: 'Required', description: 'Numeric ID of assigned Area', example: '4' },
      { name: 'article', requirement: 'Optional', description: 'Equipment name / article title', example: 'Infusion Pump' },
      { name: 'property_number', requirement: 'Optional', description: 'Unique property number identifier', example: 'PROP-2024-001' },
      { name: 'serial_number', requirement: 'Optional', description: 'Manufacturer serial number', example: 'SN-9821382' },
      { name: 'date_acquired', requirement: 'Optional', description: 'Acquisition date formatted as YYYY-MM-DD', example: '2024-01-15' },
      { name: 'unit_of_measure', requirement: 'Optional', description: 'Unit descriptor (e.g. unit, set, pc)', example: 'unit' },
      { name: 'remarks', requirement: 'Optional', description: 'Physical condition or location notes', example: 'Operational, ICU Room 2' },
      { name: 'end_user', requirement: 'Optional', description: 'Name of accountable employee/station', example: 'Nurse Station A' },
      { name: 'status', requirement: 'Optional', description: 'Condition status (Available, Unserviceable)', example: 'Available' },
    ];
  }

  return [
    { name: 'category', requirement: 'Required', description: 'Supply category code (e.g. officesup, drmeds, mssup)', example: 'officesup' },
    { name: 'description', requirement: 'Required', description: 'Detailed item specification and brand', example: 'A4 70gsm 500 sheets/ream' },
    { name: 'unit_value', requirement: 'Required', description: 'Numeric price per unit. Must be strictly > 0', example: '250.00' },
    { name: 'balance_per_card', requirement: 'Required', description: 'Card balance inventory. Integer > 0', example: '10' },
    { name: 'on_hand_per_count', requirement: 'Required', description: 'Physical count inventory. Integer > 0', example: '10' },
    { name: 'division_id', requirement: 'Required', description: 'Numeric ID of assigned Division', example: '1' },
    { name: 'area_id', requirement: 'Required', description: 'Numeric ID of assigned Area', example: '4' },
    { name: 'expiry_date', requirement: 'Conditional', description: 'YYYY-MM-DD. Required for Medical/Drugs/Food supplies', example: '2027-06-30' },
    { name: 'stock_number', requirement: 'Optional', description: 'Unique stock code. Updates record if matched; creates new if empty', example: 'STK-00124' },
    { name: 'article', requirement: 'Optional', description: 'Short item name', example: 'Bond Paper' },
    { name: 'unit_of_measure', requirement: 'Optional', description: 'Packaging unit (e.g. ream, box, pc, bottle)', example: 'ream' },
    { name: 'status', requirement: 'Optional', description: 'Status (Available, Depleted). Defaults to Available', example: 'Available' },
  ];
});

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileSelect = (e) => {
  const file = e.target.files?.[0];
  processFile(file);
};

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0];
  processFile(file);
};

const processFile = (file) => {
  errorMessage.value = '';
  if (!file) return;

  if (!file.name.toLowerCase().endsWith('.csv')) {
    errorMessage.value = 'Please select a valid .csv file.';
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    errorMessage.value = 'File size exceeds the 10MB limit.';
    return;
  }

  selectedFile.value = file;
};

const removeSelectedFile = () => {
  selectedFile.value = null;
  errorMessage.value = '';
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const handleClose = () => {
  if (isUploading.value) return;
  removeSelectedFile();
  isInstructionsOpen.value = false;
  emit('close');
};

const submitUpload = () => {
  if (!selectedFile.value || isUploading.value) return;

  isUploading.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('file', selectedFile.value);

  router.post(route(props.importRouteName), formData, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      isUploading.value = false;
      removeSelectedFile();
      emit('success');
      handleClose();
    },
    onError: (errors) => {
      isUploading.value = false;
      const firstError = Object.values(errors)[0] || 'Failed to import CSV. Please review the formatting instructions.';
      emit('error', firstError);
      handleClose();
    },
  });
};
</script>
