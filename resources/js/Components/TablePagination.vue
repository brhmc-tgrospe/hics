<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  pagination: {
    type: Object,
    required: true
  },
  perPage: {
    type: [Number, String],
    default: null
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 25, 50, 100]
  }
});

const emit = defineEmits(['update:perPage', 'update-per-page']);

const currentPerPage = computed(() => {
  return Number(props.perPage ?? props.pagination?.per_page ?? 10);
});

const rangeText = computed(() => {
  const total = props.pagination?.total ?? 0;
  const data = props.pagination?.data;
  const count = Array.isArray(data) ? data.length : 0;

  if (total === 0 || count === 0) {
    return `Showing 0 of ${total} entries`;
  }

  const from = props.pagination?.from ?? 1;
  const to = props.pagination?.to ?? (from + count - 1);
  return `Showing ${from}-${to} of ${total} entries`;
});

const onPerPageChange = (event) => {
  const value = Number(event.target.value);
  emit('update:perPage', value);
  emit('update-per-page', value);
};
</script>

<template>
  <div class="px-6 py-4 border-t border-white/60 bg-slate-900/5 flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Rows per page & Range Indicator -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-slate-500">Rows per page:</span>
        <select
          :value="currentPerPage"
          @change="onPerPageChange"
          class="bg-white/50 backdrop-blur border border-white/80 rounded-lg pl-2 pr-8 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >
          <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
        </select>
      </div>
      <span class="text-xs font-medium text-slate-500">
        {{ rangeText }}
      </span>
    </div>

    <!-- Navigation Links -->
    <div class="flex items-center justify-end flex-1">
      <div class="flex items-center gap-1">
        <template v-for="(link, index) in pagination?.links || []" :key="index">
          <Link
            v-if="link.url"
            :href="link.url"
            :class="[
              'px-3 py-1 rounded-lg text-xs font-medium transition-colors',
              link.active ? 'bg-blue-600 text-white' : 'hover:bg-white/50 text-slate-600'
            ]"
            v-html="link.label"
          />
          <span
            v-else
            class="px-3 py-1 rounded-lg text-xs font-medium text-slate-400"
            v-html="link.label"
          ></span>
        </template>
      </div>
    </div>
  </div>
</template>
