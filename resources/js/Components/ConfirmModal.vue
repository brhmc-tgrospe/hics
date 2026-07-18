<script setup>
import Modal from '@/Components/Modal.vue';
import { AlertTriangle, X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action'
    },
    description: {
        type: String,
        default: 'Are you sure you want to proceed?'
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    isDanger: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" maxWidth="md" @close="close">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full" 
                         :class="isDanger ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800">
                        {{ title }}
                    </h2>
                </div>
                <button @click="close" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-slate-100">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <div class="mt-4">
                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">
                    {{ description }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button @click="close" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    {{ cancelText }}
                </button>
                <button @click="confirm" class="px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                        :class="isDanger ? 'bg-red-600 hover:bg-red-700 shadow-sm shadow-red-200' : 'bg-blue-600 hover:bg-blue-700 shadow-sm shadow-blue-200'">
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
