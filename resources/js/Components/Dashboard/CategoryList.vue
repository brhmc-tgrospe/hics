<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    }
});
</script>

<template>
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 h-full">
        <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-6">{{ title }}</h3>
        
        <div class="space-y-4 overflow-y-auto">
            <div v-for="(item, index) in items" :key="index" class="flex justify-between items-center py-2">
                <span class="text-slate-600 font-medium text-sm">{{ item.category }}</span>
                <span class="font-bold text-slate-800 text-sm">{{ item.count }}</span>
            </div>
            <div v-if="items.length === 0" class="text-slate-400 text-sm italic">
                No data available.
            </div>
        </div>
    </div>
</template>
