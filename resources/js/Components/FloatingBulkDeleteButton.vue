<script setup>
import { Trash2 } from 'lucide-vue-next';

defineProps({
    count: {
        type: Number,
        required: true,
        default: 0
    }
});

defineEmits(['delete']);
</script>

<template>
    <div class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50">
        <transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="opacity-0 translate-y-8 scale-90"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-8 scale-90"
        >
            <button
                v-if="count > 0"
                @click="$emit('delete')"
                class="px-6 py-3 bg-red-600 text-white rounded-full font-bold shadow-2xl shadow-red-600/40 hover:bg-red-700 hover:shadow-red-600/50 hover:-translate-y-1 transition-all duration-200 flex items-center gap-2 border border-red-500/30"
            >
                <Trash2 class="w-5 h-5" />
                Delete Selected ({{ count }})
            </button>
        </transition>
    </div>
</template>
