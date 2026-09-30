<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage();
const roles = computed(() => page.props.auth?.roles || []);
const user = computed(() => page.props.auth?.user || {});

const primaryRole = computed(() => {
    if (roles.value.includes('komisaris')) return 'komisaris';
    if (roles.value.includes('manajer-operasional')) return 'manajer-operasional';
    if (roles.value.includes('staf-gudang')) return 'staf-gudang';
    if (roles.value.includes('auditor-internal')) return 'auditor-internal';
    return roles.value[0] || 'User';
});

const roleBadge = computed(() => {
    switch (primaryRole.value) {
        case 'komisaris':
            return {
                label: 'Komisaris',
                cls: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
            };
        case 'manajer-operasional':
            return {
                label: 'Manajer Operasional',
                cls: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
            };
        case 'staf-gudang':
            return {
                label: 'Staf Gudang',
                cls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
            };
        case 'auditor-internal':
            return {
                label: 'Auditor Internal',
                cls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            };
        default:
            return {
                label: primaryRole.value,
                cls: 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
            };
    }
});

// Penentuan akses navigasi sesuai pembagian peran Spatie RBAC
const canAccessTransactions = computed(() => roles.value.some(r => ['staf-gudang', 'manajer-operasional'].includes(r)));
const canAccessAnalytics = computed(() => roles.value.some(r => ['manajer-operasional', 'komisaris'].includes(r)));
const canAccessRestock = computed(() => roles.value.some(r => ['manajer-operasional', 'komisaris'].includes(r)));
const canAccessAudit = computed(() => roles.value.some(r => ['auditor-internal', 'komisaris'].includes(r)));
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50/60 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 transition-colors">
            <nav class="sticky top-0 z-40 border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center gap-8">
                            <!-- Logo Brand -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2.5 group">
                                    <ApplicationLogo class="block h-9 w-auto" />
                                    <span class="font-bold text-base tracking-tight text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        SIPALING
                                    </span>
                                </Link>
                            </div>

                            <!-- Desktop Dynamic Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    :href="route('inventory.index')"
                                    :active="route().current('inventory.*')"
                                >
                                    Katalog Inventaris
                                </NavLink>
                                <NavLink
                                    v-if="canAccessTransactions"
                                    :href="route('transactions.index')"
                                    :active="route().current('transactions.*')"
                                >
                                    Mutasi Stok
                                </NavLink>
                                <NavLink
                                    v-if="canAccessAnalytics"
                                    :href="route('analytics.index')"
                                    :active="route().current('analytics.*')"
                                >
                                    Analitik DES
                                </NavLink>
                                <NavLink
                                    v-if="canAccessRestock"
                                    :href="route('restock.index')"
                                    :active="route().current('restock.*')"
                                >
                                    Approval Restock
                                </NavLink>
                                <NavLink
                                    v-if="canAccessAudit"
                                    :href="route('audit.index')"
                                    :active="route().current('audit.*')"
                                >
                                    Jejak Audit
                                </NavLink>
                            </div>
                        </div>

                        <!-- User Profile & Action Dropdown -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="56">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-xs hover:bg-zinc-50 dark:hover:bg-zinc-800/80 focus:outline-none transition-all"
                                        >
                                            <span class="font-medium text-xs sm:text-sm text-zinc-800 dark:text-zinc-100 max-w-[140px] truncate">
                                                {{ user.name }}
                                            </span>

                                            <span
                                                :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full border tracking-wide uppercase', roleBadge.cls]"
                                            >
                                                {{ roleBadge.label }}
                                            </span>

                                            <svg
                                                class="h-4 w-4 text-zinc-400"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-800">
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500 font-medium">Masuk Sebagai</p>
                                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate">{{ user.email }}</p>
                                        </div>
                                        <DropdownLink :href="route('profile.edit')">
                                            Pengaturan Akun
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="text-rose-600 dark:text-rose-400 font-medium hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                        >
                                            Keluar Sistem
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger Mobile Button -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 focus:outline-none transition-colors"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Mobile Navigation Drawer -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden border-b border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('inventory.index')" :active="route().current('inventory.*')">
                            Katalog Inventaris
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canAccessTransactions" :href="route('transactions.index')" :active="route().current('transactions.*')">
                            Mutasi Stok
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canAccessAnalytics" :href="route('analytics.index')" :active="route().current('analytics.*')">
                            Analitik DES
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canAccessRestock" :href="route('restock.index')" :active="route().current('restock.*')">
                            Approval Restock
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="canAccessAudit" :href="route('audit.index')" :active="route().current('audit.*')">
                            Jejak Audit
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-zinc-200 dark:border-zinc-800 pb-3 pt-4">
                        <div class="px-4">
                            <div class="flex items-center justify-between">
                                <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ user.name }}
                                </div>
                                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase', roleBadge.cls]">
                                    {{ roleBadge.label }}
                                </span>
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Pengaturan Akun
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="text-rose-600 dark:text-rose-400 font-medium">
                                Keluar Sistem
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-zinc-900 border-b border-zinc-200/80 dark:border-zinc-800/80 shadow-2xs" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
