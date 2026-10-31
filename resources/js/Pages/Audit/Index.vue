<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AuditDiffDialog from '@/Components/Audit/AuditDiffDialog.vue'
import {
  Search,
  Filter,
  FileText,
  User,
  Clock,
  Eye,
  Activity,
  Boxes,
  Users,
  CheckCircle2,
  Calendar,
  X,
  Cpu,
} from 'lucide-vue-next'

export interface LogItem {
  id: number
  log_name: string
  description: string
  subject_type?: string
  subject_id?: string
  causer_type?: string
  causer_id?: string
  causer?: { id: string; name: string; email: string }
  event: string
  attribute_changes?: any
  properties?: any
  created_at: string
}

const props = defineProps<{
  logs: {
    data: LogItem[]
    current_page: number
    last_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
  }
  stats: {
    total_logs: number
    inventory_logs: number
    user_logs: number
    forecast_logs: number
  }
  filters?: {
    search?: string
    event?: string
    log_name?: string
    start_date?: string
    end_date?: string
  }
}>()

// State Filter
const search = ref(props.filters?.search || '')
const currentEvent = ref(props.filters?.event || '')
const currentLogName = ref(props.filters?.log_name || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')
const startDateInput = ref<HTMLInputElement | null>(null)
const endDateInput = ref<HTMLInputElement | null>(null)

// Modal Detail View
const selectedLog = ref<LogItem | null>(null)
const isDetailModalOpen = ref(false)

function openDetail(log: LogItem) {
  selectedLog.value = log
  isDetailModalOpen.value = true
}

// Format Tanggal
function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'medium',
  }).format(new Date(dateStr))
}

function formatDisplayDate(dateStr: string) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return `${d}/${m}/${y}`
}

function openStartDatePicker() {
  if (startDateInput.value) {
    if (typeof startDateInput.value.showPicker === 'function') {
      startDateInput.value.showPicker()
    } else {
      startDateInput.value.focus()
    }
  }
}

function openEndDatePicker() {
  if (endDateInput.value) {
    if (typeof endDateInput.value.showPicker === 'function') {
      endDateInput.value.showPicker()
    } else {
      endDateInput.value.focus()
    }
  }
}

// Logika Pengiriman Filter
function applyFilter() {
  router.get(
    route('audit.index'),
    {
      search: search.value || undefined,
      event: currentEvent.value || undefined,
      log_name: currentLogName.value || undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

// Debounce Search
let searchTimeout: any = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilter()
  }, 350)
})

watch([startDate, endDate], () => {
  applyFilter()
})

function setEventFilter(ev: string) {
  currentEvent.value = currentEvent.value === ev ? '' : ev
  applyFilter()
}

function setLogNameFilter(name: string) {
  currentLogName.value = currentLogName.value === name ? '' : name
  applyFilter()
}

function resetAllFilters() {
  search.value = ''
  currentEvent.value = ''
  currentLogName.value = ''
  startDate.value = ''
  endDate.value = ''
  applyFilter()
}

const hasActiveFilters = computed(() => {
  return Boolean(search.value || currentEvent.value || currentLogName.value || startDate.value || endDate.value)
})

function getEventBadgeClass(event: string) {
  switch (event) {
    case 'created':
    case 'inbound':
      return 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/60 dark:border-emerald-800/60'
    case 'updated':
    case 'status_changed':
      return 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/60 dark:border-blue-800/60'
    case 'deleted':
    case 'outbound':
      return 'bg-rose-50 text-rose-600 border-rose-200/80 dark:bg-rose-950/60 dark:border-rose-800/60'
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300'
  }
}

// Navigasi Paginasi
const prevPageUrl = computed(() => {
  if (!props.logs.links || props.logs.links.length === 0) return null
  const first = props.logs.links[0]
  return first?.url || null
})

const nextPageUrl = computed(() => {
  if (!props.logs.links || props.logs.links.length === 0) return null
  const last = props.logs.links[props.logs.links.length - 1]
  return last?.url || null
})

const visiblePages = computed(() => {
  const current = props.logs.current_page
  const last = props.logs.last_page
  if (last <= 1) return []

  const pages: Array<{ page: number; url: string; active: boolean } | '...'> = []
  const getUrl = (p: number) => {
    const match = props.logs.links.find((l) => l.label == String(p))
    return match?.url || `?page=${p}`
  }

  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push({ page: i, url: getUrl(i), active: i === current })
    }
    return pages
  }

  pages.push({ page: 1, url: getUrl(1), active: current === 1 })

  if (current > 3) {
    pages.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)

  for (let i = start; i <= end; i++) {
    pages.push({ page: i, url: getUrl(i), active: i === current })
  }

  if (current < last - 2) {
    pages.push('...')
  }

  pages.push({ page: last, url: getUrl(last), active: current === last })

  return pages
})
</script>

<template>
  <Head title="Jejak Rekam Audit (Audit Trail)" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Tata Kelola</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Jejak Rekam Audit
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Jejak Rekam Audit (Audit Trail)
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Pencatatan terpusat dan bukti forensik digital atas setiap perubahan data inventaris, mutasi stok, dan manajemen akun.
          </p>
        </div>
      </div>

      <!-- KPI Summary Cards (Unified Interactive Cards) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Rekam Jejak -->
        <div
          @click="setLogNameFilter('')"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="!currentLogName
            ? 'border-slate-400 ring-2 ring-slate-400/20 dark:border-slate-500'
            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
          title="Tampilkan seluruh aktivitas sistem"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Rekam Jejak</span>
            <div class="h-8 w-8 rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Activity class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">entri</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Seluruh rekam jejak sistem
          </p>
        </div>

        <!-- 2. Mutasi & Inventaris -->
        <div
          @click="setLogNameFilter('inventory')"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="currentLogName === 'inventory'
            ? 'border-emerald-500 ring-2 ring-emerald-500/20 dark:border-emerald-500'
            : 'border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'"
          title="Klik untuk filter log mutasi & inventaris"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Mutasi &amp; Inventaris</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Boxes class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.inventory_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Aktivitas produk &amp; stok gudang
          </p>
        </div>

        <!-- 3. Aktivitas Pengguna -->
        <div
          @click="setLogNameFilter('users')"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="currentLogName === 'users'
            ? 'border-[#2563EB] ring-2 ring-blue-500/20 dark:border-blue-500'
            : 'border-slate-200 dark:border-slate-800 hover:border-[#2563EB]/50'"
          title="Klik untuk filter aktivitas akun pengguna"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Aktivitas Pengguna</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Users class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.user_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Autentikasi &amp; peran pengguna
          </p>
        </div>

        <!-- 4. Log Peramalan DES -->
        <div
          @click="setLogNameFilter('forecasting')"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="currentLogName === 'forecasting'
            ? 'border-[#4F46E5] ring-2 ring-indigo-500/20 dark:border-indigo-500'
            : 'border-slate-200 dark:border-slate-800 hover:border-[#4F46E5]/50'"
          title="Klik untuk filter log peramalan DES"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Log Peramalan DES</span>
            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Cpu class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.forecast_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Riwayat komputasi &amp; simulasi
          </p>
        </div>
      </div>

      <!-- Logs Table Card -->
      <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md overflow-hidden">
        <!-- Toolbar & Filters (Style Transaksi) -->
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-col gap-3.5 xl:flex-row xl:items-center xl:justify-between">
          <!-- Event Filter Tabs -->
          <div class="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800 w-full sm:w-fit overflow-x-auto">
            <button
              type="button"
              @click="setEventFilter('')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer shrink-0',
                !currentEvent
                  ? 'bg-white text-slate-900 shadow-2xs dark:bg-slate-900 dark:text-white'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              Semua Event
            </button>
            <button
              type="button"
              @click="setEventFilter('created')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                currentEvent === 'created'
                  ? 'bg-[#10B981] text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              Created
            </button>
            <button
              type="button"
              @click="setEventFilter('updated')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                currentEvent === 'updated'
                  ? 'bg-[#2563EB] text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <Activity class="h-3.5 w-3.5" />
              Updated
            </button>
            <button
              type="button"
              @click="setEventFilter('deleted')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                currentEvent === 'deleted'
                  ? 'bg-rose-600 text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <X class="h-3.5 w-3.5" />
              Deleted
            </button>
          </div>

          <!-- Filters: Date Range & Search Input -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-wrap xl:flex-nowrap">
            <!-- Date Range Filter -->
            <div class="h-9 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs shadow-2xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:border-slate-300 dark:hover:border-slate-700 focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/15 select-none">
              <!-- Tanggal Mulai -->
              <div
                class="relative flex items-center gap-1.5 cursor-pointer group py-1"
                @click="openStartDatePicker"
                title="Dari Tanggal (Klik untuk memilih kalender)"
              >
                <Calendar class="h-3.5 w-3.5 text-slate-400 group-hover:text-[#2563EB] dark:group-hover:text-blue-400 transition-colors shrink-0" />
                <span
                  class="text-[11px] sm:text-xs font-medium tabular-nums transition-colors"
                  :class="startDate ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400 dark:text-slate-500'"
                >
                  {{ startDate ? formatDisplayDate(startDate) : 'dd/mm/yyyy' }}
                </span>
                <input
                  ref="startDateInput"
                  v-model="startDate"
                  type="date"
                  :max="endDate || undefined"
                  aria-label="Filter Tanggal Mulai"
                  class="absolute inset-0 opacity-0 pointer-events-none w-full h-full"
                  tabindex="-1"
                />
              </div>

              <span class="text-[11px] text-slate-400 font-semibold select-none px-0.5">s.d.</span>

              <!-- Tanggal Akhir -->
              <div
                class="relative flex items-center gap-1.5 cursor-pointer group py-1"
                @click="openEndDatePicker"
                title="Sampai Tanggal (Klik untuk memilih kalender)"
              >
                <Calendar class="h-3.5 w-3.5 text-slate-400 group-hover:text-[#2563EB] dark:group-hover:text-blue-400 transition-colors shrink-0" />
                <span
                  class="text-[11px] sm:text-xs font-medium tabular-nums transition-colors"
                  :class="endDate ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400 dark:text-slate-500'"
                >
                  {{ endDate ? formatDisplayDate(endDate) : 'dd/mm/yyyy' }}
                </span>
                <input
                  ref="endDateInput"
                  v-model="endDate"
                  type="date"
                  :min="startDate || undefined"
                  aria-label="Filter Tanggal Akhir"
                  class="absolute inset-0 opacity-0 pointer-events-none w-full h-full"
                  tabindex="-1"
                />
              </div>

              <button
                v-if="startDate || endDate"
                type="button"
                @click.stop="startDate = ''; endDate = ''"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-0.5"
                title="Hapus filter rentang tanggal"
              >
                <X class="h-3 w-3" />
              </button>
            </div>

            <!-- Search Box -->
            <div class="relative flex-1 sm:w-64 xl:w-72">
              <input
                v-model="search"
                type="text"
                placeholder="Cari deskripsi, aktor, event..."
                class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-8 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-900 dark:text-white shadow-2xs"
              />
              <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
              <button
                v-if="search"
                type="button"
                @click="search = ''"
                class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                title="Hapus pencarian"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <!-- Reset Filter -->
            <button
              v-if="hasActiveFilters"
              type="button"
              @click="resetAllFilters"
              class="h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-rose-600 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer shrink-0"
              title="Reset seluruh filter"
            >
              <X class="h-3.5 w-3.5" />
              <span>Reset</span>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/80 text-xs font-semibold text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr class="h-10">
                <th class="px-4 py-2.5 whitespace-nowrap">Waktu &amp; Tanggal</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Kategori &amp; Event</th>
                <th class="px-4 py-2.5">Deskripsi Aktivitas</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Pengguna (Aktor)</th>
                <th class="px-4 py-2.5 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap tabular-nums">
                  <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <Clock class="h-3.5 w-3.5 text-slate-400" />
                    {{ formatDate(log.created_at) }}
                  </div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium border uppercase tracking-wider',
                      getEventBadgeClass(log.event)
                    ]"
                  >
                    {{ log.event || log.log_name }}
                  </span>
                </td>
                <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200 text-xs">
                  <p class="font-medium line-clamp-1">{{ log.description }}</p>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 text-[10px] font-bold">
                      {{ log.causer?.name?.charAt(0) || 'S' }}
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                      {{ log.causer?.name || 'Sistem / Anonim' }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                  <button
                    @click="openDetail(log)"
                    class="h-7 px-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs inline-flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <Eye class="h-3.5 w-3.5 text-slate-400" />
                    Detail
                  </button>
                </td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="5" class="px-4 py-12 text-center text-xs text-slate-400">
                  <Activity class="h-8 w-8 mx-auto text-slate-300 dark:text-slate-600 mb-2 stroke-[1.5]" />
                  <p v-if="hasActiveFilters" class="font-medium text-slate-600 dark:text-slate-400">
                    Tidak ada rekam jejak audit yang sesuai dengan filter yang dipilih.
                  </p>
                  <p v-else class="font-medium text-slate-600 dark:text-slate-400">
                    Belum ada rekaman rekam jejak audit sistem.
                  </p>
                  <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="resetAllFilters"
                    class="mt-2 text-xs font-semibold text-[#2563EB] hover:underline cursor-pointer inline-flex items-center gap-1"
                  >
                    <span>Reset Filter</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination & Total Counter (Konsisten Standar SIPALING) -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 text-xs">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ logs.data.length }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ logs.total }}</span> rekaman jejak audit
          </div>

          <div class="flex items-center gap-2">
            <span v-if="logs.last_page > 1" class="text-xs text-slate-500 dark:text-slate-400 mr-1 hidden sm:inline">
              Halaman <span class="font-semibold text-slate-700 dark:text-slate-300">{{ logs.current_page }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ logs.last_page }}</span>
            </span>

            <!-- Tombol Sebelumnya -->
            <Link
              v-if="prevPageUrl"
              :href="prevPageUrl"
              preserve-state
              preserve-scroll
              class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs h-8 px-3 rounded-lg inline-flex items-center gap-1 cursor-pointer transition-colors"
            >
              Sebelumnya
            </Link>
            <button
              v-else
              disabled
              class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 opacity-40 cursor-not-allowed shadow-2xs h-8 px-3 rounded-lg inline-flex items-center gap-1"
            >
              Sebelumnya
            </button>

            <!-- Navigasi Nomor Halaman Pintar (Layar md+) -->
            <div v-if="visiblePages.length > 0" class="hidden md:flex items-center gap-1">
              <template v-for="(pItem, idx) in visiblePages" :key="idx">
                <span v-if="pItem === '...'" class="px-1 text-slate-400 select-none">...</span>
                <Link
                  v-else
                  :href="pItem.url"
                  preserve-state
                  preserve-scroll
                  :class="[
                    'h-8 min-w-[32px] px-2 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors',
                    pItem.active
                      ? 'bg-[#2563EB] text-white shadow-2xs'
                      : 'border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                  ]"
                >
                  {{ pItem.page }}
                </Link>
              </template>
            </div>

            <!-- Tombol Berikutnya -->
            <Link
              v-if="nextPageUrl"
              :href="nextPageUrl"
              preserve-state
              preserve-scroll
              class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs h-8 px-3 rounded-lg inline-flex items-center gap-1 cursor-pointer transition-colors"
            >
              Berikutnya
            </Link>
            <button
              v-else
              disabled
              class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 opacity-40 cursor-not-allowed shadow-2xs h-8 px-3 rounded-lg inline-flex items-center gap-1"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog Perbandingan Perubahan Atribut -->
    <AuditDiffDialog
      v-model:open="isDetailModalOpen"
      :log="selectedLog"
    />
  </AuthenticatedLayout>
</template>
