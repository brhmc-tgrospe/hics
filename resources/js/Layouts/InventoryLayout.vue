<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalToast from '@/Components/GlobalToast.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { User, Settings, LogOut, ChevronDown, Menu } from 'lucide-vue-next';

const isSidebarOpen = ref(false);
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
                    <button @click="isSidebarOpen = true" class="md:hidden p-2 -ml-2 text-slate-600 hover:text-slate-900 bg-white/50 hover:bg-white/80 rounded-lg transition-colors">
                        <Menu class="w-6 h-6" />
                    </button>
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
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <main class="flex-1 min-w-0">
                <slot />
            </main>
        </div>
        
        <GlobalToast />
    </div>
</template>
