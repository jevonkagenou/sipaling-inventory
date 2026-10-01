<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppSidebar from '@/Components/Layout/AppSidebar.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { useTheme } from '@/Composables/useTheme'
import {
  Menu,
  Sun,
  Moon,
  ChevronDown,
  User as UserIcon,
  LogOut,
  PanelLeftClose,
  PanelLeftOpen,
} from 'lucide-vue-next'

const page = usePage()
const { isDark, toggleTheme, initTheme } = useTheme()

const isSidebarOpen = ref(false)
const isSidebarCollapsed = ref(false)
const sidebarRef = ref(null)

function toggleDesktopSidebar() {
  if (sidebarRef.value) {
    sidebarRef.value.toggleCollapse()
    isSidebarCollapsed.value = sidebarRef.value.isCollapsed
  }
}

const user = computed(() => page.props.auth?.user || {})
const roles = computed(() => page.props.auth?.roles || [])

const primaryRole = computed(() => {
  if (roles.value.includes('komisaris')) return 'komisaris'
  if (roles.value.includes('manajer-operasional')) return 'manajer-operasional'
  if (roles.value.includes('staf-gudang')) return 'staf-gudang'
  if (roles.value.includes('auditor-internal')) return 'auditor-internal'
  return roles.value[0] || 'User'
})

const roleBadge = computed(() => {
  switch (primaryRole.value) {
    case 'komisaris':
      return {
        label: 'Komisaris',
        cls: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
      }
    case 'manajer-operasional':
      return {
        label: 'Manajer Ops',
        cls: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
      }
    case 'staf-gudang':
      return {
        label: 'Staf Gudang',
        cls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
      }
    case 'auditor-internal':
      return {
        label: 'Auditor Internal',
        cls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
      }
    default:
      return {
        label: primaryRole.value,
        cls: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
      }
  }
})

onMounted(() => {
  initTheme()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-[#0F172A] text-slate-900 dark:text-slate-100 flex transition-colors duration-200 font-sans">
    <!-- Modern Sticky Sidebar Navigation -->
    <AppSidebar
      ref="sidebarRef"
      :is-open="isSidebarOpen"
      :model-value-collapsed="isSidebarCollapsed"
      @update:model-value-collapsed="isSidebarCollapsed = $event"
      @update:is-open="isSidebarOpen = $event"
      @close="isSidebarOpen = false"
    />

    <!-- Main Content Shell -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Clean Minimal Top Header Navbar (No Redundant Badges) -->
      <header class="sticky top-0 z-30 h-14 border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-[#0F172A]/90 backdrop-blur-md px-4 sm:px-6 lg:px-8 flex items-center justify-between transition-colors">
        <!-- Left: Mobile Trigger & Desktop Sidebar Trigger & Header Title -->
        <div class="flex items-center gap-2.5 min-w-0">
          <!-- Mobile Menu Toggle Button -->
          <button
            type="button"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:hidden focus:outline-none cursor-pointer"
            @click="isSidebarOpen = true"
          >
            <span class="sr-only">Buka menu</span>
            <Menu class="h-5 w-5" />
          </button>

          <!-- Desktop Sidebar Trigger (Dynamic Arrow Toggle: Inward to close, Outward to open) -->
          <button
            type="button"
            @click="toggleDesktopSidebar"
            class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors cursor-pointer"
            :title="isSidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
          >
            <PanelLeftOpen v-if="isSidebarCollapsed" class="h-4 w-4" />
            <PanelLeftClose v-else class="h-4 w-4" />
          </button>

          <!-- Custom Page Title / Breadcrumb Slot -->
          <div class="min-w-0">
            <slot name="header" />
          </div>
        </div>

        <!-- Right: Actions & User Avatar -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          <!-- Quick Theme Toggle -->
          <button
            type="button"
            @click="toggleTheme"
            title="Ganti Mode Tampilan"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 shadow-2xs hover:border-[#2563EB]/40 transition-colors cursor-pointer"
          >
            <Sun v-if="isDark" class="h-4 w-4 text-amber-400" />
            <Moon v-else class="h-4 w-4 text-slate-400" />
          </button>

          <!-- Compact User Avatar Dropdown (Eliminates Header Redundancy) -->
          <Dropdown align="right" width="48">
            <template #trigger>
              <button
                type="button"
                class="flex items-center gap-2 p-1 pl-1.5 pr-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800/80 focus:outline-none transition-all cursor-pointer"
              >
                <div class="h-7 w-7 rounded-lg bg-blue-100 dark:bg-blue-950/80 text-[#2563EB] dark:text-blue-400 font-bold text-xs flex items-center justify-center uppercase">
                  {{ (user.name || 'U').slice(0, 2) }}
                </div>
                <ChevronDown class="h-3.5 w-3.5 text-slate-400" />
              </button>
            </template>

            <template #content>
              <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                <p class="text-xs font-semibold text-slate-900 dark:text-slate-100 truncate">{{ user.name }}</p>
                <p class="text-[11px] text-slate-400 truncate">{{ user.email }}</p>
                <div class="mt-1">
                  <span :class="['inline-flex text-[10px] font-semibold px-1.5 py-0.2 rounded border uppercase tracking-wider', roleBadge.cls]">
                    {{ roleBadge.label }}
                  </span>
                </div>
              </div>
              <DropdownLink :href="route('profile.edit')" class="text-xs flex items-center gap-2">
                <UserIcon class="h-3.5 w-3.5 text-slate-400" />
                Akun
              </DropdownLink>
              <DropdownLink
                :href="route('logout')"
                method="post"
                as="button"
                class="text-xs text-rose-600 dark:text-rose-400 font-medium hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2"
              >
                <LogOut class="h-3.5 w-3.5 text-rose-500" />
                Keluar
              </DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <!-- Main Page Content Body -->
      <main class="flex-1 p-4 sm:p-5 lg:p-6 min-w-0">
        <slot />
      </main>

      <!-- Minimal Enterprise Footer -->
      <footer class="border-t border-slate-200/80 dark:border-slate-800/80 py-4 px-6 text-center text-xs text-slate-400 dark:text-slate-500 bg-white/40 dark:bg-slate-900/40">
        <p>
          <span class="font-semibold text-slate-600 dark:text-slate-400">SIPALING</span> &bull;
          Sistem Inventaris Prediktif &amp; Audit Log Terintegrasi &bull; Kelompok 1 PBL SIB 3C Polinema
        </p>
      </footer>
    </div>
  </div>
</template>
