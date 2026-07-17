<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 relative overflow-hidden"
        style="background: radial-gradient(at top left, #e0f2fe 0%, #f1f5f9 50%, #dcfce7 100%)"
    >
        <!-- Decorative background elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl"></div>
        </div>

        <div class="z-10 relative mb-8 flex flex-col items-center">
            <Link href="/" class="relative block">
                <!-- Shadow/glow on the upper left -->
                <div class="absolute -top-4 -left-4 w-20 h-20 bg-blue-900/20 rounded-full blur-xl"></div>
                <!-- General glow behind the logo to make it prominent -->
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-100 to-white rounded-full blur-md opacity-60"></div>
                <ApplicationLogo class="relative h-28 w-28 drop-shadow-2xl" />
            </Link>
            <h1 class="mt-4 text-2xl font-bold text-slate-800 tracking-tight">BRHMC - HICS</h1>
            <p class="text-sm text-slate-500 font-medium">Hospital Inventory Consolidation System</p>
        </div>

        <div
            class="z-10 w-full overflow-hidden bg-white/70 backdrop-blur-xl px-8 py-8 shadow-2xl sm:max-w-md sm:rounded-3xl border border-white/80"
        >
            <slot />
        </div>
    </div>
</template>
