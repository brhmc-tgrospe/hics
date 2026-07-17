<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col font-sans relative text-slate-800" style="background: radial-gradient(at top left, #e0f2fe 0%, #f1f5f9 50%, #dcfce7 100%)">
        <header class="h-20 bg-white/30 backdrop-blur-lg border-b border-white/60 sticky top-0 z-10">
            <div class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative w-10 h-10 flex items-center justify-center">
                        <div class="absolute -top-2 -left-2 w-8 h-8 bg-blue-900/20 rounded-full blur-md"></div>
                        <div class="absolute -inset-1 bg-gradient-to-r from-blue-100 to-white rounded-full blur-sm opacity-60"></div>
                        <img src="/hics_logo.png" alt="BRHMC - HICS Logo" class="relative w-full h-full object-contain drop-shadow-xl" />
                    </div>
                    <h1 class="text-sm font-bold leading-tight text-slate-800 uppercase tracking-wider hidden sm:block">
                        BRHMC - HICS
                    </h1>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:hidden">
                        BRHMC - HICS
                    </h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-white/50 backdrop-blur border border-white/80 px-3 py-1.5 rounded-xl">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span class="text-sm font-bold text-slate-700 outline-none">{{ $page.props.auth?.user?.name || 'Viewer' }}</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 max-w-[96rem] w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row gap-8">
            <aside class="w-full md:w-64 shrink-0">
                <nav class="space-y-1">
                    <Link 
                        :href="route('dashboard')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('dashboard') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('dashboard') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard
                    </Link>
                    
                    <Link 
                        :href="route('equipment.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('equipment.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('equipment.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        Equipment
                    </Link>

                    <Link 
                        :href="route('supplies.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('supplies.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('supplies.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Supplies
                    </Link>

                    <Link 
                        v-if="$page.props.auth.user?.permissions?.includes('view_users')"
                        :href="route('users.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('users.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('users.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Users
                    </Link>
                </nav>
            </aside>

            <main class="flex-1 min-w-0">
                <slot />
            </main>
        </div>
    </div>
</template>
