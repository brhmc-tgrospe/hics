<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, AlertCircle, Info, X } from 'lucide-vue-next';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success'); // success, error, info
let timeout = null;

const checkFlash = () => {
    const flash = page.props.flash;
    if (flash?.success) {
        message.value = flash.success;
        type.value = 'success';
        showToast();
        flash.success = null;
    } else if (flash?.error) {
        message.value = flash.error;
        type.value = 'error';
        showToast();
        flash.error = null;
    } else if (flash?.message) {
        message.value = flash.message;
        type.value = 'info';
        showToast();
        flash.message = null;
    }
};

watch(() => page.props.flash, () => {
    checkFlash();
}, { deep: true });

onMounted(() => {
    checkFlash();
});

const showToast = () => {
    show.value = true;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        show.value = false;
    }, 3000);
};

const dismiss = () => {
    show.value = false;
    if (timeout) clearTimeout(timeout);
};
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" @click="dismiss" class="fixed bottom-4 right-4 z-[9999] px-6 py-3 rounded-xl shadow-lg font-medium flex items-center gap-3 cursor-pointer select-none max-w-sm hover:opacity-90 transition-opacity"
             :class="{
                 'bg-green-500 text-white': type === 'success',
                 'bg-red-500 text-white': type === 'error',
                 'bg-blue-500 text-white': type === 'info'
             }">
            <CheckCircle v-if="type === 'success'" class="w-5 h-5 shrink-0" />
            <AlertCircle v-if="type === 'error'" class="w-5 h-5 shrink-0" />
            <Info v-if="type === 'info'" class="w-5 h-5 shrink-0" />
            <span class="break-words text-sm">{{ message }}</span>
            <button @click.stop="dismiss" class="p-1 -mr-2 ml-auto rounded-full hover:bg-white/20 transition-colors">
                <X class="w-4 h-4 opacity-70 hover:opacity-100 transition-opacity shrink-0" />
            </button>
        </div>
    </Transition>
</template>
