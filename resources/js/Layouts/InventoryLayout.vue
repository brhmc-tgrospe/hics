<script setup>
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { User, Settings, LogOut, ChevronDown } from 'lucide-vue-next';
</script>

<template>
    <div class="min-h-screen flex flex-col font-sans relative text-slate-800" style="background: radial-gradient(at top left, #e0f2fe 0%, #f1f5f9 50%, #dcfce7 100%)">
        <div v-if="$page.props.auth.is_impersonating" class="bg-red-600 text-white px-4 py-2 text-center text-sm font-bold shadow-md relative z-50 flex justify-center items-center gap-4">
            <span>You are currently impersonating {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}.</span>
            <a :href="route('impersonate.leave')" class="bg-white text-red-600 px-3 py-1 rounded-md text-xs hover:bg-red-50 transition-colors shadow-sm uppercase tracking-wide">
                Leave Impersonation
            </a>
        </div>
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
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center gap-2 bg-white/50 backdrop-blur border border-white/80 px-3 py-1.5 rounded-xl hover:bg-white/70 transition-colors">
                                    <User class="w-4 h-4 text-slate-500" />
                                    <span class="text-sm font-bold text-slate-700 outline-none">{{ $page.props.auth?.user?.first_name || 'Viewer' }}</span>
                                    <ChevronDown class="w-4 h-4 text-slate-400" />
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2 text-slate-700">
                                    <Settings class="w-4 h-4" />
                                    Settings
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50">
                                    <LogOut class="w-4 h-4" />
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
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
                        v-if="$page.props.auth.user?.permissions?.includes('generate_reports')"
                        :href="route('reports.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('reports.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('reports.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Reports
                    </Link>

                    <Link 
                        v-if="$page.props.auth.user?.permissions?.includes('view_users')"
                        :href="route('users.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('users.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('users.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Users
                    </Link>

                    <Link 
                        v-if="$page.props.auth.user?.permissions?.includes('view_divisions')"
                        :href="route('divisions.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('divisions.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('divisions.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Divisions
                    </Link>

                    <Link 
                        v-if="$page.props.auth.user?.permissions?.includes('view_areas')"
                        :href="route('areas.index')" 
                        :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('areas.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                    >
                        <svg :class="['w-5 h-5', route().current('areas.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Areas
                    </Link>
                </nav>
            </aside>

            <main class="flex-1 min-w-0">
                <slot />
            </main>
        </div>
    </div>
</template>
