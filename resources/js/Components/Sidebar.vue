<script setup>
import { Link } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';

defineProps({
    isOpen: {
        type: Boolean,
        required: true
    }
});

defineEmits(['close']);
</script>

<template>
    <!-- Overlay for mobile -->
    <div 
        v-show="isOpen" 
        @click="$emit('close')" 
        class="fixed inset-0 bg-slate-900/40 z-40 md:hidden backdrop-blur-sm transition-opacity"
    ></div>
    
    <!-- Sidebar -->
    <aside 
        :class="[
            'fixed inset-y-0 left-0 z-50 w-64 bg-white/90 backdrop-blur-xl md:bg-transparent md:backdrop-blur-none border-r border-slate-200/50 md:border-none md:sticky md:top-28 md:h-[calc(100vh-8rem)] transition-transform duration-300 ease-in-out shrink-0',
            isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        ]"
    >
        <div class="h-full overflow-y-auto p-4 md:p-0 flex flex-col">
            <!-- Close button for mobile -->
            <div class="flex justify-end md:hidden mb-4">
                <button @click="$emit('close')" class="p-2 text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <nav class="space-y-1">
                <Link 
                    :href="route('dashboard')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('dashboard') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('dashboard') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </Link>

                <Link 
                    :href="route('categories.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('categories.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('categories.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Categories
                </Link>
                
                <Link 
                    :href="route('equipment.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('equipment.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('equipment.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    Equipment
                </Link>

                <Link 
                    :href="route('supplies.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('supplies.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('supplies.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Supplies
                </Link>

                <Link 
                    :href="route('reports.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('reports.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('reports.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Reports
                </Link>

                <Link 
                    v-if="$page.props.auth.user?.permissions?.includes('view_users')"
                    :href="route('users.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('users.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('users.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Users
                </Link>

                <Link 
                    v-if="$page.props.auth.user?.permissions?.includes('view_divisions')"
                    :href="route('divisions.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('divisions.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('divisions.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Divisions
                </Link>

                <Link 
                    v-if="$page.props.auth.user?.permissions?.includes('view_areas')"
                    :href="route('areas.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('areas.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('areas.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Areas
                </Link>

                <Link 
                    v-if="$page.props.auth.user?.permissions?.includes('view_activity_logs')"
                    :href="route('activity-logs.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('activity-logs.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('activity-logs.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Activity Logs
                </Link>

                <Link 
                    v-if="$page.props.auth.user?.roles?.includes('Developer') || $page.props.auth.user?.roles?.includes('Superadmin')"
                    :href="route('recycle-bin.index')" 
                    @click="$emit('close')"
                    :class="['w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors', route().current('recycle-bin.index') ? 'text-blue-700 bg-blue-100/50 rounded-lg' : 'text-slate-600 hover:bg-white/40 rounded-lg']"
                >
                    <svg :class="['w-5 h-5', route().current('recycle-bin.index') ? 'text-blue-700' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Recycle Bin
                </Link>
            </nav>
        </div>
    </aside>
</template>
