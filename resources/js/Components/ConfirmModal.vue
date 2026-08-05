<script setup>
import Modal from '@/Components/Modal.vue';
import { AlertTriangle, X } from 'lucide-vue-next';
import { computed } from 'vue';

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
    },
    requireInput: {
        type: Boolean,
        default: false
    },
    inputLabel: {
        type: String,
        default: 'Remarks / Reason for deletion'
    },
    inputPlaceholder: {
        type: String,
        default: 'Enter remarks here...'
    },
    modelValue: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['close', 'confirm', 'update:modelValue']);

const isConfirmDisabled = computed(() => {
    if (props.requireInput) {
        return !props.modelValue || props.modelValue.trim() === '';
    }
    return false;
});

const close = () => {
    emit('close');
};

const confirm = () => {
    if (isConfirmDisabled.value) return;
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

                <div v-if="requireInput" class="mt-4">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        {{ inputLabel }} <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        :value="modelValue"
                        @input="emit('update:modelValue', $event.target.value)"
                        :placeholder="inputPlaceholder"
                        rows="3"
                        class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 transition-colors placeholder:text-slate-400"
                        required
                    ></textarea>
                    <p v-if="requireInput && (!modelValue || !modelValue.trim())" class="text-xs text-red-500 mt-1">
                        Please provide a remark before proceeding.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button @click="close" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    {{ cancelText }}
                </button>
                <button 
                    @click="confirm" 
                    :disabled="isConfirmDisabled"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    :class="isDanger ? 'bg-red-600 hover:bg-red-700 shadow-sm shadow-red-200' : 'bg-blue-600 hover:bg-blue-700 shadow-sm shadow-blue-200'"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
