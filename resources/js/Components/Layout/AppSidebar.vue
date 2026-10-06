<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'
import {
  LayoutDashboard,
  Boxes,
  ArrowLeftRight,
  ClipboardCheck,
  LineChart,
  ShieldCheck,
  Users,
  Settings,
  LogOut,
  Sun,
  Moon,
  X,
  Sparkles,
} from 'lucide-vue-next'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  modelValueCollapsed: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:isOpen', 'update:modelValueCollapsed', 'close'])

const page = usePage()
const { isDark, toggleTheme } = useTheme()

// Collapsed state for desktop icon-rail mode
const isCollapsed = ref(props.modelValueCollapsed)

watch(
  () => props.modelValueCollapsed,
  (val) => {
    isCollapsed.value = val
  }
)

function toggleCollapse() {
  isCollapsed.value = !isCollapsed.value
  emit('update:modelValueCollapsed', isCollapsed.value)
}

defineExpose({
  isCollapsed,
  toggleCollapse,
})

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
        cls: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60',
        dot: 'bg-indigo-500',
      }
    case 'manajer-operasional':
      return {
        label: 'Manajer Ops',
        cls: 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800/60',
        dot: 'bg-[#2563EB]',
      }
    case 'staf-gudang':
      return {
        label: 'Staf Gudang',
        cls: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800/60',
        dot: 'bg-amber-500',
      }
    case 'auditor-internal':
      return {
        label: 'Auditor Internal',
        cls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
        dot: 'bg-[#10B981]',
      }
    default:
      return {
        label: primaryRole.value,
        cls: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        dot: 'bg-slate-400',
      }
  }
})

// RBAC Permissions Check
const canAccessTransactions = computed(() =>
  roles.value.some((r) => ['staf-gudang', 'manajer-operasional', 'komisaris', 'auditor-internal'].includes(r))
)
const canAccessRestock = computed(() =>
  roles.value.some((r) => ['manajer-operasional', 'komisaris'].includes(r))
)
// Analitik DES terbuka untuk seluruh tim (mode pengujian & referensi)
const canAccessAnalytics = computed(() => true)
const canAccessAudit = computed(() =>
  roles.value.some((r) => ['auditor-internal', 'komisaris'].includes(r))
)
const canAccessUsers = computed(() =>
  roles.value.some((r) => ['manajer-operasional', 'komisaris'].includes(r))
)

const navGroups = computed(() => [
  {
    title: 'Utama',
    items: [
      {
        name: 'Dashboard',
        route: 'dashboard',
        icon: LayoutDashboard,
        active: route().current('dashboard'),
        show: true,
      },
      {
        name: 'Katalog Inventaris',
        route: 'inventory.index',
        icon: Boxes,
        active: route().current('inventory.*'),
        badge: '10 SKU',
        show: true,
      },
    ],
  },
  {
    title: 'Operasional Gudang',
    items: [
      {
        name: 'Mutasi Stok',
        route: 'transactions.index',
        icon: ArrowLeftRight,
        active: route().current('transactions.*'),
        show: canAccessTransactions.value,
      },
      {
        name: 'Approval Restock',
        route: 'restock.index',
        icon: ClipboardCheck,
        active: route().current('restock.*'),
        show: canAccessRestock.value,
      },
    ],
  },
  {
    title: 'Intelijen & Tata Kelola',
    items: [
      {
        name: 'Analitik DES',
        route: 'analytics.index',
        icon: LineChart,
        active: route().current('analytics.*'),
        badge: 'Holt DES',
        show: canAccessAnalytics.value,
      },
      {
        name: 'Jejak Audit Log',
        route: 'audit.index',
        icon: ShieldCheck,
        active: route().current('audit.*'),
        show: canAccessAudit.value,
      },
      {
        name: 'Manajemen Pengguna',
        route: 'users.index',
        icon: Users,
        active: route().current('users.*'),
        show: canAccessUsers.value,
      },
    ],
  },
])

function closeSidebar() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <!-- Mobile Backdrop Overlay -->
  <Transition
    enter-active-class="transition-opacity duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden"
      @click="closeSidebar"
    />
  </Transition>

  <!-- Stripe Enterprise Sidebar Container -->
  <aside
    :class="[
      'fixed top-0 bottom-0 left-0 z-50 flex flex-col border-r border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-[#0B1120]/95 backdrop-blur-md transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:shrink-0 select-none',
      isCollapsed ? 'lg:w-[74px]' : 'lg:w-64',
      isOpen ? 'w-64 translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <!-- Brand Header -->
    <div
      class="flex h-16 shrink-0 items-center border-b border-slate-200/80 dark:border-slate-800/80 transition-all"
      :class="isCollapsed ? 'justify-center px-2' : 'justify-between px-4'"
    >
      <!-- Mode Collapsed: Logo Bersih di Tengah -->
      <template v-if="isCollapsed">
        <Link
          :href="route('dashboard')"
          class="flex items-center justify-center p-1 rounded-xl hover:scale-105 transition-transform"
          title="SIPALING - Beranda"
        >
          <img
            src="/logo-sipaling-squircle.png"
            alt="SIPALING Logo"
            class="h-9 w-9 object-contain drop-shadow-xs"
          />
        </Link>
      </template>

      <!-- Mode Expanded: Logo + Brand Name + Toggle Button -->
      <template v-else>
        <Link :href="route('dashboard')" class="flex items-center gap-3 group min-w-0">
          <img
            src="/logo-sipaling-squircle.png"
            alt="SIPALING Logo"
            class="h-9 w-9 shrink-0 object-contain drop-shadow-xs group-hover:scale-105 transition-transform"
          />
          <div class="min-w-0">
            <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white truncate block">
              SIPALING
            </span>
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider truncate -mt-0.5">
              Inventaris &amp; DES
            </p>
          </div>
        </Link>

        <!-- Mobile Close Button -->
        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:hidden"
          @click="closeSidebar"
        >
          <span class="sr-only">Tutup menu</span>
          <X class="h-5 w-5" />
        </button>
      </template>
    </div>

    <!-- User Profile Card (Stripe Enterprise Glass Elevation) -->
    <div
      class="transition-all mt-3"
      :class="isCollapsed ? 'flex justify-center px-2' : 'px-3'"
    >
      <div
        :class="[
          'rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900/80 shadow-2xs backdrop-blur-sm flex items-center transition-all',
          isCollapsed
            ? 'w-11 h-11 justify-center p-1'
            : 'p-2.5 gap-2.5 hover:border-slate-300 dark:hover:border-slate-700',
        ]"
      >
        <div class="h-8 w-8 shrink-0 rounded-xl bg-gradient-to-tr from-[#2563EB] to-[#4F46E5] text-white flex items-center justify-center font-bold text-xs shadow-xs uppercase">
          {{ (user.name || 'SP').slice(0, 2) }}
        </div>
        <div v-show="!isCollapsed" class="flex-1 min-w-0">
          <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
            {{ user.name }}
          </p>
          <div class="mt-0.5 flex items-center gap-1.5">
            <span
              :class="[
                'inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-semibold border uppercase tracking-wider',
                roleBadge.cls,
              ]"
            >
              <span :class="['w-1 h-1 rounded-full animate-pulse', roleBadge.dot]" />
              {{ roleBadge.label }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Menu List -->
    <div class="flex-1 overflow-y-auto px-2.5 py-3 space-y-5 custom-scrollbar">
      <div
        v-for="group in navGroups"
        :key="group.title"
        class="space-y-1"
        v-show="group.items.some((item) => item.show)"
      >
        <h3
          v-show="!isCollapsed"
          class="px-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] transition-opacity"
        >
          {{ group.title }}
        </h3>
        <div class="space-y-1 pt-0.5">
          <template v-for="item in group.items" :key="item.name">
            <Link
              v-if="item.show"
              :href="route(item.route)"
              :title="isCollapsed ? item.name : undefined"
              :class="[
                'group flex items-center gap-3 py-2 rounded-xl text-xs transition-all duration-200',
                isCollapsed ? 'justify-center px-0' : 'px-3',
                item.active
                  ? 'bg-gradient-to-r from-[#2563EB] to-[#3B82F6] text-white font-semibold shadow-md shadow-blue-500/20 border border-blue-400/25'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-800/60 font-medium',
              ]"
              @click="closeSidebar"
            >
              <component
                :is="item.icon"
                :class="[
                  'h-4 w-4 shrink-0 transition-transform duration-200 group-hover:scale-110',
                  item.active
                    ? 'text-white'
                    : 'text-slate-400 dark:text-slate-500 group-hover:text-[#2563EB] dark:group-hover:text-blue-400',
                ]"
              />
              <span v-show="!isCollapsed" class="truncate flex-1 tracking-tight">{{ item.name }}</span>
              <span
                v-if="!isCollapsed && item.badge"
                :class="[
                  'text-[10px] font-semibold px-2 py-0.5 rounded-full transition-colors',
                  item.active
                    ? 'bg-white/20 text-white border border-white/20 backdrop-blur-xs'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60',
                ]"
              >
                {{ item.badge }}
              </span>
            </Link>
          </template>
        </div>
      </div>
    </div>

    <!-- Footer Controls -->
    <div
      class="shrink-0 p-2.5 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/60 dark:bg-[#0B1120]/60 space-y-1"
    >
      <!-- Dark / Light Theme Toggle -->
      <button
        type="button"
        @click="toggleTheme"
        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
        :class="[
          'w-full flex items-center py-2 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-slate-200 transition-colors cursor-pointer',
          isCollapsed ? 'justify-center px-0' : 'justify-between px-3',
        ]"
      >
        <div class="flex items-center gap-3">
          <Sun v-if="isDark" class="h-4 w-4 text-amber-400 shrink-0" />
          <Moon v-else class="h-4 w-4 text-slate-400 shrink-0" />
          <span v-show="!isCollapsed">Mode {{ isDark ? 'Terang' : 'Gelap' }}</span>
        </div>
        <span
          v-show="!isCollapsed"
          class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/60 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold uppercase tracking-wider"
        >
          {{ isDark ? 'Dark' : 'Light' }}
        </span>
      </button>

      <!-- Account Settings -->
      <Link
        :href="route('profile.edit')"
        :title="isCollapsed ? 'Akun' : undefined"
        :class="[
          'flex items-center gap-3 py-2 rounded-xl text-xs font-medium transition-colors',
          isCollapsed ? 'justify-center px-0' : 'px-3',
          route().current('profile.edit')
            ? 'bg-slate-200/70 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold'
            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-slate-200',
        ]"
        @click="closeSidebar"
      >
        <Settings class="h-4 w-4 text-slate-400 dark:text-slate-500 shrink-0" />
        <span v-show="!isCollapsed" class="truncate">Akun</span>
      </Link>

      <!-- Logout Button -->
      <Link
        :href="route('logout')"
        method="post"
        as="button"
        :title="isCollapsed ? 'Keluar' : undefined"
        :class="[
          'w-full flex items-center gap-3 py-2 rounded-xl text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer',
          isCollapsed ? 'justify-center px-0' : 'px-3',
        ]"
      >
        <LogOut class="h-4 w-4 text-rose-500 shrink-0" />
        <span v-show="!isCollapsed">Keluar</span>
      </Link>
    </div>
  </aside>
</template>
