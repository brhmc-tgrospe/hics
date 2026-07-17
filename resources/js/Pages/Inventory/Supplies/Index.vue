<template>
  <InventoryLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Supply Inventory</h2>
          <p class="text-sm text-slate-500 font-medium mt-2">Manage and track hospital supplies</p>
        </div>
        <div>
          <button 
              v-if="$page.props.auth.user.permissions.includes('generate_reports')"
              @click="isReporting = true"
              class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-colors w-full sm:w-auto justify-center"
          >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Generate Report
          </button>
        </div>
      </div>

      <!-- Add/Edit Form -->
      <Modal :show="isFormOpen" maxWidth="2xl" @close="closeForm">
          <SupplyForm
            v-if="isFormOpen"
            :editing-id="editingId"
            :initial-data="formData"
            :categories="categories"
            @close="closeForm"
            @success="handleSuccess"
          />
      </Modal>

      <Modal :show="isViewing" maxWidth="2xl" @close="isViewing = false">
          <ViewSupplyDetails
            v-if="isViewing"
            :data="viewingData"
            :categories="categories"
            @close="isViewing = false"
          />
      </Modal>

      <Modal :show="isReporting" maxWidth="md" @close="isReporting = false">
          <div class="p-6">
              <h3 class="text-lg font-bold text-slate-800 mb-4">Generate Supply Report</h3>
              <div class="space-y-5">
                  <div>
                      <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                      <select v-model="reportData.category" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                          <option v-for="c in categories" :key="c.code" :value="c.code">{{ c.name }}</option>
                      </select>
                  </div>
                  <div>
                      <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date of Accountability</label>
                      <VueDatePicker 
                          v-model="reportData.date_of_accountability" 
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
                                      :value="formatDate(reportData.date_of_accountability)" 
                                      readonly 
                                      class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 pl-10 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer"
                                      placeholder="Select date"
                                  />
                              </div>
                          </template>
                      </VueDatePicker>
                  </div>
                  <div>
                      <label class="block text-sm font-semibold text-slate-700 mb-1.5">Year of Report</label>
                      <select v-model="reportData.year_of_report" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all cursor-pointer">
                          <option v-for="y in reportYears" :key="y" :value="y">{{ y }}</option>
                      </select>
                  </div>
                  <div>
                      <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fund Cluster</label>
                      <input 
                          type="text" 
                          v-model="reportData.fund_cluster" 
                          placeholder="e.g. 05"
                          class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-4 py-2.5 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                      />
                  </div>
                  <div class="pt-4 flex justify-end gap-3">
                      <button @click="isReporting = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancel</button>
                      <button @click="generateReport" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md">Generate</button>
                  </div>
              </div>
          </div>
      </Modal>

      <!-- Toolbar & Table Container -->
      <div class="bg-white/50 backdrop-blur-xl rounded-3xl border border-white/80 shadow-2xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <SearchIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input 
                type="text" 
                placeholder="Search..." 
                v-model="searchQuery"
                @input="handleSearch"
                class="w-full pl-9 pr-4 py-2 bg-white/50 backdrop-blur border border-white/80 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
              />
            </div>
            <select 
              v-model="filterCat"
              @change="handleSearch"
              class="w-full sm:w-auto bg-white/50 backdrop-blur border border-white/80 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
            >
              <option value="All">All Categories</option>
              <option v-for="cat in categories" :key="cat.code" :value="cat.code">{{ cat.name }}</option>
            </select>
          </div>
          <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
            <input 
                type="file" 
                accept=".csv" 
                ref="fileInput" 
                class="hidden" 
                @change="handleFileUpload" 
            />
            <a 
                v-if="$page.props.auth.user.permissions.includes('create_supplies')"
                :href="route('supplies.template')" 
                class="px-4 py-2 bg-white/50 text-slate-700 border border-slate-300 rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 hover:bg-slate-50 transition-colors w-full sm:w-auto justify-center"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Template
            </a>
            <button 
                v-if="$page.props.auth.user.permissions.includes('create_supplies')"
                @click="$refs.fileInput.click()"
                class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold shadow-xl shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700 transition-colors w-full sm:w-auto justify-center"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Import CSV
            </button>
            <button 
              v-if="$page.props.auth.user.permissions.includes('create_supplies')"
              @click="openAddForm"
              class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-xl shadow-slate-200 flex items-center gap-2 hover:bg-slate-800 transition-colors w-full sm:w-auto justify-center"
            >
              <PlusIcon class="w-4 h-4" /> Add
            </button>
          </div>
        </div>

        <!-- Table -->
        <SuppliesTable
          :supplies="supplies"
          :categories="categories"
          :can-edit="canEdit"
          :filters="filters"
          @edit="openEditForm"
          @view="openView"
          @delete="handleDelete"
          @update-per-page="handlePerPage"
        />
      </div>
    </div>
  </InventoryLayout>

  <!-- Toast Notification -->
  <transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="showToast" class="fixed bottom-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg font-medium flex items-center gap-3">
      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
      {{ toastMessage }}
    </div>
  </transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { SearchIcon, PlusIcon } from 'lucide-vue-next';
import InventoryLayout from '@/Layouts/InventoryLayout.vue';
import SupplyForm from './SupplyForm.vue';
import SuppliesTable from './SuppliesTable.vue';
import ViewSupplyDetails from './ViewSupplyDetails.vue';
import Modal from '@/Components/Modal.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  supplies: Object,
  filters: Object,
  categories: Array,
});

// For now, mock role check. RBAC will be added later.
const canEdit = ref(true); 

const searchQuery = ref(props.filters.search || '');
const filterCat = ref(props.filters.category || 'All');

const isFormOpen = ref(false);
const editingId = ref(null);
const formData = ref({});

const isViewing = ref(false);
const viewingData = ref(null);

const isReporting = ref(false);
const reportData = ref({
    category: props.categories.length ? props.categories[0].code : '',
    date_of_accountability: new Date(),
    year_of_report: new Date().getFullYear(),
    fund_cluster: '',
});

const currentYear = new Date().getFullYear();
const reportYears = Array.from({length: 10}, (_, i) => currentYear - 5 + i);

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const generateReport = async () => {
    if (!reportData.value.category || !reportData.value.date_of_accountability || !reportData.value.year_of_report || !reportData.value.fund_cluster || String(reportData.value.fund_cluster).trim() === '') {
        alert("Please fill in all required fields.");
        return;
    }
    try {
        const payload = {
            ...reportData.value,
            date_of_accountability: reportData.value.date_of_accountability instanceof Date 
                ? reportData.value.date_of_accountability.toLocaleDateString('en-CA') // YYYY-MM-DD
                : reportData.value.date_of_accountability
        };
        const response = await axios.post(route('supplies.report.generate'), payload);
        if (response.data && response.data.id) {
            window.open(route('supplies.report.show', response.data.id), '_blank');
            isReporting.value = false;
        }
    } catch (error) {
        console.error("Error generating report", error);
        alert("Failed to generate report. Please check your inputs.");
    }
};

const handleSearch = debounce(() => {
  router.get('/supplies', {
    search: searchQuery.value,
    category: filterCat.value,
    per_page: props.supplies.per_page
  }, {
    preserveState: true,
    replace: true
  });
}, 300);

const handlePerPage = (size) => {
  router.get('/supplies', {
    search: searchQuery.value,
    category: filterCat.value,
    per_page: size
  }, {
    preserveState: true,
    replace: true
  });
};

const openAddForm = () => {
  formData.value = {};
  editingId.value = null;
  isFormOpen.value = true;
};

const openEditForm = (item) => {
  formData.value = { ...item };
  editingId.value = item.id;
  isFormOpen.value = true;
};

const openView = (item) => {
  viewingData.value = { ...item };
  isViewing.value = true;
};

const closeForm = () => {
  isFormOpen.value = false;
  editingId.value = null;
  formData.value = {};
};

const fileInput = ref(null);
const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const uploadData = new FormData();
    uploadData.append('file', file);

    router.post(route('supplies.import'), uploadData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (fileInput.value) fileInput.value.value = '';
            toastMessage.value = 'CSV Imported successfully!';
            showToast.value = true;
            setTimeout(() => { showToast.value = false; }, 3000);
        },
        onError: () => {
            if (fileInput.value) fileInput.value.value = '';
            alert('Failed to import CSV. Please ensure the format matches the template.');
        }
    });
};

const toastMessage = ref('');
const showToast = ref(false);

const handleSuccess = (data) => {
  if (data && data.article && data.mode) {
      toastMessage.value = `${data.article} has been successfully ${data.mode}`;
      showToast.value = true;
      setTimeout(() => {
          showToast.value = false;
      }, 3000);
  }
  closeForm();
};

const handleDelete = (id) => {
  if (confirm('Are you sure you want to delete this record?')) {
    router.delete(`/supplies/${id}`);
  }
};
</script>
