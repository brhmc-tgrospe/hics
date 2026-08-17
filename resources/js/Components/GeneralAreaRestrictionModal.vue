<script setup>
import Modal from '@/Components/Modal.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);
</script>

<template>
    <Modal :show="show" maxWidth="md" @close="$emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100/80 text-amber-600 shrink-0">
                    <AlertCircle class="w-6 h-6" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-slate-900 leading-6">Action Restricted</h3>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Your account is currently assigned to <strong class="text-slate-800 font-semibold">General Area</strong>. Users in the General Area are unable to add or upload items in the system.
                    </p>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Please contact the administrator to change your designated area.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-semibold shadow-md shadow-slate-200 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
                >
                    Close
                </button>
            </div>
        </div>
    </Modal>
</template>
