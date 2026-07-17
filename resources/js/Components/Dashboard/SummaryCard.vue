<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: String,
        default: 'cube', // 'cube', 'archive', 'pulse', 'shield'
    },
    valueColorClass: {
        type: String,
        default: 'text-slate-900',
    }
});
</script>

<template>
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
        <div class="flex items-center space-x-2 text-slate-400 text-xs font-semibold uppercase tracking-wider">
            <!-- Icon placeholder -->
            <svg v-if="icon === 'archive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            <svg v-else-if="icon === 'cube'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <svg v-else-if="icon === 'pulse'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <svg v-else-if="icon === 'shield'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            
            <span>{{ title }}</span>
        </div>
        <div :class="['text-4xl font-bold tracking-tight', valueColorClass]">
            {{ value }}
        </div>
    </div>
</template>
