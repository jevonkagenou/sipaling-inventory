<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import {
  PackagePlus,
  PackageMinus,
  ArrowUpDown,
  Search,
  Calendar,
  Building2,
  FileText,
  User,
  Eye,
  Filter,
  ArrowRight,
  Boxes,
  Clock,
  CheckCircle2,
  X,
  Printer,
} from 'lucide-vue-next'

export interface TransactionProduct {
  id: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
}

export interface TransactionDetail {
  id: string
  product_id: string
  quantity: number
  unit_price: number | string
  notes?: string
  product?: TransactionProduct
}

export interface TransactionItem {
  id: string
  reference_no: string
  type: 'inbound' | 'outbound'
  transaction_date: string
  party_name?: string
  notes?: string
  created_by?: string
  creator?: { id: string; name: string }
  details_count?: number
  details?: TransactionDetail[]
}

const props = defineProps<{
  transactions: {
    data: TransactionItem[]
    current_page: number
    last_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
  }
  filters: {
    type?: string
    search?: string
    start_date?: string
    end_date?: string
  }
  stats: {
    total_inbound: number
    total_outbound: number
    today_inbound_qty: number
    today_outbound_qty: number
    is_date_filtered?: boolean
    is_filtered?: boolean
  }
  can_create?: boolean
}>()

const page = usePage()

const search = ref(props.filters.search || '')
const currentType = ref(props.filters.type || '')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')

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

function setTypeFilter(type: string) {
  currentType.value = type
  applyFilter()
}

const startDateInput = ref<HTMLInputElement | null>(null)
const endDateInput = ref<HTMLInputElement | null>(null)

function formatDisplayDate(val: string): string {
  if (!val) return ''
  const [y, m, d] = val.split('-')
  return (y && m && d) ? `${d}/${m}/${y}` : val
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

function clearDateFilter() {
  startDate.value = ''
  endDate.value = ''
}

function applyDateShortcut(preset: 'today' | '7days' | '30days' | 'this_month' | 'all') {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  const toDateStr = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

  if (preset === 'all') {
    startDate.value = ''
    endDate.value = ''
  } else if (preset === 'today') {
    const todayStr = toDateStr(now)
    startDate.value = todayStr
    endDate.value = todayStr
  } else if (preset === '7days') {
    const past = new Date(now)
    past.setDate(past.getDate() - 6)
    startDate.value = toDateStr(past)
    endDate.value = toDateStr(now)
  } else if (preset === '30days') {
    const past = new Date(now)
    past.setDate(past.getDate() - 29)
    startDate.value = toDateStr(past)
    endDate.value = toDateStr(now)
  } else if (preset === 'this_month') {
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
    startDate.value = toDateStr(firstDay)
    endDate.value = toDateStr(now)
  }
}

function resetAllFilters() {
  search.value = ''
  currentType.value = ''
  startDate.value = ''
  endDate.value = ''
  applyFilter()
}

const hasActiveFilters = computed(() => {
  return Boolean(search.value || currentType.value || startDate.value || endDate.value)
})

function applyFilter() {
  selectedIds.value = []
  router.get(
    route('transactions.index'),
    {
      search: search.value || undefined,
      type: currentType.value || undefined,
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

// Fitur Cetak Masal / Batch Print PDF
const selectedIds = ref<string[]>([])
const isBatchPrintModalOpen = ref(false)

const isAllSelected = computed(() => {
  if (!props.transactions.data || props.transactions.data.length === 0) return false
  return props.transactions.data.every((tx) => selectedIds.value.includes(tx.id))
})

function toggleSelectAll() {
  if (isAllSelected.value) {
    const currentIds = props.transactions.data.map((tx) => tx.id)
    selectedIds.value = selectedIds.value.filter((id) => !currentIds.includes(id))
  } else {
    const currentIds = props.transactions.data.map((tx) => tx.id)
    const set = new Set([...selectedIds.value, ...currentIds])
    selectedIds.value = Array.from(set)
  }
}

function toggleSelectOne(id: string) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((item) => item !== id)
  } else {
    selectedIds.value.push(id)
  }
}

function printBatchReceipts() {
  if (selectedIds.value.length === 0) return
  const params = new URLSearchParams()
  selectedIds.value.forEach((id) => params.append('ids[]', id))
  window.open(`${route('transactions.batch-receipt')}?${params.toString()}`, '_blank')
}

function handleBatchPrintClick() {
  if (selectedIds.value.length > 0) {
    printBatchReceipts()
  } else {
    isBatchPrintModalOpen.value = true
  }
}

function printAllCurrentPage() {
  if (!props.transactions.data || props.transactions.data.length === 0) return
  const currentIds = props.transactions.data.map((tx) => tx.id)
  selectedIds.value = currentIds
  isBatchPrintModalOpen.value = false
  printBatchReceipts()
}

function selectManualFromTable() {
  isBatchPrintModalOpen.value = false
}

// Modal Detail View
const selectedTransaction = ref<TransactionItem | null>(null)
const isDetailModalOpen = ref(false)

const originalTitle = document.title

function openDetail(tx: TransactionItem) {
  selectedTransaction.value = tx
  isDetailModalOpen.value = true
  document.title = `Operasional Mutasi Stok_${tx.reference_no}`
}

function closeDetail() {
  selectedTransaction.value = null
  isDetailModalOpen.value = false
  document.title = originalTitle
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(d)
}

function formatRupiah(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}

const prevPageUrl = computed(() => {
  if (!props.transactions.links || props.transactions.links.length === 0) return null
  const first = props.transactions.links[0]
  return first?.url || null
})

const nextPageUrl = computed(() => {
  if (!props.transactions.links || props.transactions.links.length === 0) return null
  const last = props.transactions.links[props.transactions.links.length - 1]
  return last?.url || null
})

const visiblePages = computed(() => {
  const current = props.transactions.current_page
  const last = props.transactions.last_page
  if (last <= 1) return []

  const pages: Array<{ page: number; url: string; active: boolean } | '...'> = []
  const getUrl = (p: number) => {
    const match = props.transactions.links.find((l) => l.label == String(p))
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
  <Head title="Operasional Mutasi Stok" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Operasional Gudang</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Mutasi Stok Barang
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Flash Alert Banner -->
      <div v-if="page.props.flash?.success" class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-xs sm:text-sm font-medium text-emerald-800 dark:text-emerald-300 shadow-2xs backdrop-blur-md">
        <div class="flex items-center gap-2.5">
          <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-500" />
          <span>{{ page.props.flash.success }}</span>
        </div>
      </div>

      <!-- Header Banner & Action Buttons -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Mutasi Stok Barang
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Pencatatan dan pemantauan riwayat barang masuk (Inbound) dan pengeluaran barang (Outbound).
          </p>
        </div>

        <!-- Action Buttons (Batch Print & Mutasi Baru) -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <button
            type="button"
            @click="handleBatchPrintClick"
            :class="[
              'h-9 px-3.5 rounded-xl text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer',
              selectedIds.length > 0
                ? 'bg-[#2563EB] text-white hover:bg-blue-700'
                : 'border border-blue-200 dark:border-blue-900/60 bg-blue-50/70 dark:bg-blue-950/40 text-[#2563EB] dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60'
            ]"
            title="Cetak Berita Acara Rekapitulasi Kolektif (Hanya 1x tanda tangan di akhir berkas)"
          >
            <Printer class="h-4 w-4" />
            <span>{{ selectedIds.length > 0 ? `Cetak Berita Acara (${selectedIds.length} Dipilih)` : 'Cetak Berita Acara Kolektif' }}</span>
          </button>

          <!-- Quick Action Buttons (Khusus Staf Gudang & Manajer) -->
          <template v-if="props.can_create !== false">
            <Link
              :href="route('transactions.inbound.create')"
              class="h-9 px-3.5 rounded-xl bg-[#10B981] hover:bg-emerald-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <PackagePlus class="h-4 w-4" />
              Catat Masuk (Inbound)
            </Link>

            <Link
              :href="route('transactions.outbound.create')"
              class="h-9 px-3.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <PackageMinus class="h-4 w-4" />
              Catat Keluar (Outbound)
            </Link>
          </template>
        </div>
      </div>

      <!-- Stat Cards (Unified Interactive KPI Cards via Reusable StatCard) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Inbound Total -->
        <StatCard
          :title="stats.is_filtered ? 'Inbound (Terfilter)' : 'Transaksi Inbound'"
          :value="stats.total_inbound"
          unit="faktur"
          :description="stats.is_filtered ? 'Faktur sesuai filter pencarian' : 'Total Dokumen Inbound'"
          color="emerald"
          :icon="PackagePlus"
        />

        <!-- Outbound Total -->
        <StatCard
          :title="stats.is_filtered ? 'Outbound (Terfilter)' : 'Transaksi Outbound'"
          :value="stats.total_outbound"
          unit="faktur"
          :description="stats.is_filtered ? 'Faktur sesuai filter pencarian' : 'Total Dokumen Outbound'"
          color="amber"
          :icon="PackageMinus"
        />

        <!-- Inbound Hari Ini / Periode Terfilter -->
        <StatCard
          :title="stats.is_date_filtered ? 'Masuk (Periode Dipilih)' : 'Masuk Hari Ini'"
          :description="stats.is_date_filtered ? 'Total kuantitas rentang terpilih' : 'Kuantitas Fisik Diterima Hari Ini'"
          color="blue"
          :icon="Calendar"
        >
          <template #value>
            <span class="text-[#10B981] dark:text-emerald-400">+ {{ stats.today_inbound_qty }}</span>
          </template>
          <template #unit>unit</template>
        </StatCard>

        <!-- Outbound Hari Ini / Periode Terfilter -->
        <StatCard
          :title="stats.is_date_filtered ? 'Keluar (Periode Dipilih)' : 'Keluar Hari Ini'"
          :description="stats.is_date_filtered ? 'Total kuantitas rentang terpilih' : 'Kuantitas Fisik Dikeluarkan Hari Ini'"
          color="indigo"
          :icon="Clock"
        >
          <template #value>
            <span class="text-amber-600 dark:text-amber-400">- {{ stats.today_outbound_qty }}</span>
          </template>
          <template #unit>unit</template>
        </StatCard>
      </div>

      <!-- Main Data Table Card -->
      <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md overflow-hidden">
        <!-- Toolbar & Filter Bar (2 Baris Rapi, Lega & Konsisten Seperti Katalog Inventaris) -->
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800/80 space-y-3.5">
          <!-- Baris 1: Pencarian & Tombol Aksi Cetak / Reset -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative w-full sm:w-80 lg:w-96">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
              <input
                v-model="search"
                type="text"
                placeholder="Cari No. Referensi, Pemasok/Penerima, SKU..."
                class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-8 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-900 dark:text-white shadow-2xs"
              />
              <button
                v-if="search"
                type="button"
                @click="search = ''; applyFilter()"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                title="Hapus pencarian"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <!-- Sisi Kanan: Cetak Berita Acara & Reset Filter -->
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
              <button
                v-if="hasActiveFilters"
                type="button"
                @click="resetAllFilters"
                class="h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 hover:border-rose-200 dark:hover:border-rose-900/50 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer"
                title="Reset seluruh filter"
              >
                <X class="h-3.5 w-3.5" />
                <span>Reset Filter</span>
              </button>

              <button
                type="button"
                @click="handleBatchPrintClick"
                :class="[
                  'h-9 px-3.5 rounded-xl text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer',
                  selectedIds.length > 0
                    ? 'bg-[#2563EB] hover:bg-blue-700 text-white'
                    : 'border border-blue-200 dark:border-blue-900/60 bg-blue-50/70 dark:bg-blue-950/40 text-[#2563EB] dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60'
                ]"
                title="Cetak Berita Acara Rekapitulasi (Cukup 1x tanda tangan kolektif di akhir berkas)"
              >
                <Printer class="h-3.5 w-3.5" />
                <span>{{ selectedIds.length > 0 ? `Cetak Berita Acara (${selectedIds.length})` : 'Cetak Berita Acara' }}</span>
              </button>
            </div>
          </div>

          <!-- Baris 2: Segmented Control Tipe Mutasi & Filter Kalender -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/80">
            <!-- Sisi Kiri: Segmented Tabs Tipe Mutasi (Matching InventoryToolbar Style!) -->
            <div class="inline-flex items-center p-1 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-xs w-fit">
              <button
                type="button"
                @click="setTypeFilter('')"
                :class="!currentType
                  ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold shadow-2xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <span>Semua Mutasi</span>
                <span
                  :class="!currentType
                    ? 'bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 font-semibold'
                    : 'bg-slate-200/80 dark:bg-slate-700/80 text-slate-600 dark:text-slate-400'"
                  class="px-1.5 py-0.2 rounded-md text-[10px]"
                >{{ stats.total_inbound + stats.total_outbound }}</span>
              </button>

              <button
                type="button"
                @click="setTypeFilter('inbound')"
                :class="currentType === 'inbound'
                  ? 'bg-[#10B981] text-white font-bold shadow-2xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 font-medium'"
                class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="currentType === 'inbound' ? 'bg-white' : 'bg-[#10B981]'"
                ></span>
                <span>Barang Masuk</span>
                <span
                  :class="currentType === 'inbound'
                    ? 'bg-white/20 text-white font-semibold'
                    : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'"
                  class="px-1.5 py-0.2 rounded-md text-[10px]"
                >{{ stats.total_inbound }}</span>
              </button>

              <button
                type="button"
                @click="setTypeFilter('outbound')"
                :class="currentType === 'outbound'
                  ? 'bg-amber-600 text-white font-bold shadow-2xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 font-medium'"
                class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="currentType === 'outbound' ? 'bg-white' : 'bg-amber-500'"
                ></span>
                <span>Barang Keluar</span>
                <span
                  :class="currentType === 'outbound'
                    ? 'bg-white/20 text-white font-semibold'
                    : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'"
                  class="px-1.5 py-0.2 rounded-md text-[10px]"
                >{{ stats.total_outbound }}</span>
              </button>
            </div>

            <!-- Sisi Kanan: Shortcut Waktu & Custom Date Range -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Shortcuts -->
              <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-slate-800 text-[11px]">
                <button
                  type="button"
                  @click="applyDateShortcut('all')"
                  :class="[!startDate && !endDate ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold shadow-2xs' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white', 'px-2 py-1 rounded-lg transition-all cursor-pointer']"
                >Semua</button>
                <button
                  type="button"
                  @click="applyDateShortcut('today')"
                  class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all cursor-pointer"
                >Hari Ini</button>
                <button
                  type="button"
                  @click="applyDateShortcut('7days')"
                  class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all cursor-pointer"
                >7 Hari</button>
                <button
                  type="button"
                  @click="applyDateShortcut('30days')"
                  class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all cursor-pointer"
                >30 Hari</button>
                <button
                  type="button"
                  @click="applyDateShortcut('this_month')"
                  class="px-2 py-1 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all cursor-pointer"
                >Bulan Ini</button>
              </div>

              <!-- Date Picker -->
              <div class="h-9 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs shadow-2xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:border-slate-300 dark:hover:border-slate-700 focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/15 select-none shrink-0">
                <div class="relative flex items-center gap-1.5 cursor-pointer group py-1" @click="openStartDatePicker">
                  <Calendar class="h-3.5 w-3.5 text-slate-400 group-hover:text-[#2563EB] dark:group-hover:text-blue-400 transition-colors shrink-0" />
                  <span class="text-[11px] sm:text-xs font-medium tabular-nums" :class="startDate ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                    {{ startDate ? formatDisplayDate(startDate) : 'dd/mm/yyyy' }}
                  </span>
                  <input ref="startDateInput" v-model="startDate" type="date" :max="endDate || undefined" class="absolute inset-0 opacity-0 pointer-events-none w-full h-full" tabindex="-1" />
                </div>
                <span class="text-[11px] text-slate-400 font-semibold select-none px-0.5">s.d.</span>
                <div class="relative flex items-center gap-1.5 cursor-pointer group py-1" @click="openEndDatePicker">
                  <Calendar class="h-3.5 w-3.5 text-slate-400 group-hover:text-[#2563EB] dark:group-hover:text-blue-400 transition-colors shrink-0" />
                  <span class="text-[11px] sm:text-xs font-medium tabular-nums" :class="endDate ? 'text-slate-800 dark:text-slate-200 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                    {{ endDate ? formatDisplayDate(endDate) : 'dd/mm/yyyy' }}
                  </span>
                  <input ref="endDateInput" v-model="endDate" type="date" :min="startDate || undefined" class="absolute inset-0 opacity-0 pointer-events-none w-full h-full" tabindex="-1" />
                </div>
                <button v-if="startDate || endDate" type="button" @click.stop="clearDateFilter" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer ml-0.5" title="Hapus filter tanggal">
                  <X class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Banner Panduan Jelas: Fitur Berita Acara Rekapitulasi Kolektif -->
        <div class="px-4 sm:px-5 py-2.5 bg-blue-50/50 dark:bg-blue-950/25 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-slate-600 dark:text-slate-400">
          <div class="flex items-center gap-2 min-w-0">
            <Printer class="h-3.5 w-3.5 text-[#2563EB] shrink-0" />
            <span class="truncate">
              <strong>Pengesahan Kolektif:</strong> Centang beberapa baris mutasi untuk disatukan dalam 1 berkas Berita Acara (Cukup 1 tanda tangan bersama di akhir dokumen).
            </span>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <button
              v-if="!isAllSelected && transactions.data.length > 0"
              type="button"
              @click="toggleSelectAll"
              class="text-[#2563EB] dark:text-blue-400 hover:underline font-semibold cursor-pointer"
            >
              Pilih Semua ({{ transactions.data.length }})
            </button>
            <button
              v-if="selectedIds.length > 0"
              type="button"
              @click="selectedIds = []"
              class="text-rose-600 dark:text-rose-400 hover:underline font-semibold cursor-pointer"
            >
              Batal Pilihan ({{ selectedIds.length }})
            </button>
          </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
              <tr class="h-10">
                <th class="w-16 px-3 py-2.5 text-center">
                  <div class="flex items-center justify-center gap-1.5" title="Centang untuk memilih transaksi yang disatukan dalam Berita Acara">
                    <input
                      type="checkbox"
                      :checked="isAllSelected"
                      @change="toggleSelectAll"
                      class="rounded border-slate-300 dark:border-slate-700 text-[#2563EB] focus:ring-[#2563EB]/20 h-4 w-4 cursor-pointer"
                      title="Pilih Semua di Halaman Ini"
                    />
                    <span class="text-[11px] font-semibold text-slate-500 hidden sm:inline">Pilih</span>
                  </div>
                </th>
                <th class="px-3 py-2.5 whitespace-nowrap">No. Referensi</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Tipe Mutasi</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Tanggal</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Pemasok / Tujuan</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Rincian Barang</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Petugas</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr v-if="transactions.data.length === 0">
                <td colspan="8" class="px-3 py-12 text-center text-xs text-slate-400">
                  <Boxes class="h-8 w-8 mx-auto text-slate-300 dark:text-slate-600 mb-2 stroke-[1.5]" />
                  <p v-if="hasActiveFilters">Tidak ada transaksi yang cocok dengan filter yang dipilih.</p>
                  <p v-else>Belum ada rekaman transaksi mutasi stok.</p>
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
              <tr
                v-for="tx in transactions.data"
                :key="tx.id"
                :class="[
                  'group transition-colors',
                  selectedIds.includes(tx.id)
                    ? 'bg-blue-50/60 dark:bg-blue-950/30'
                    : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40'
                ]"
              >
                <!-- Checkbox Selection -->
                <td class="w-10 px-3 py-2.5 text-center">
                  <input
                    type="checkbox"
                    :checked="selectedIds.includes(tx.id)"
                    @change="toggleSelectOne(tx.id)"
                    class="rounded border-slate-300 dark:border-slate-700 text-[#2563EB] focus:ring-[#2563EB]/20 h-4 w-4 cursor-pointer"
                  />
                </td>

                <!-- Reference No -->
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono tracking-wider text-[11px] font-semibold">
                    {{ tx.reference_no }}
                  </span>
                </td>

                <!-- Type Badge -->
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border',
                      tx.type === 'inbound'
                        ? 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/60 dark:border-emerald-800/60'
                        : 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/60 dark:border-amber-800/60 dark:text-amber-300',
                    ]"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full"
                      :class="tx.type === 'inbound' ? 'bg-[#10B981]' : 'bg-amber-500'"
                    />
                    {{ tx.type === 'inbound' ? 'Barang Masuk' : 'Barang Keluar' }}
                  </span>
                </td>

                <!-- Date -->
                <td class="px-3 py-2.5 text-slate-600 dark:text-slate-400 text-xs whitespace-nowrap tabular-nums">
                  {{ formatDate(tx.transaction_date) }}
                </td>

                <!-- Party Name -->
                <td class="px-3 py-2.5 text-slate-800 dark:text-slate-200 font-medium text-xs truncate max-w-[180px]" :title="tx.party_name || '-'">
                  {{ tx.party_name || '-' }}
                </td>

                <!-- Details Preview -->
                <td class="px-3 py-2.5">
                  <div class="flex items-center gap-1.5">
                    <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[11px] font-semibold text-slate-700 dark:text-slate-300 tabular-nums">
                      {{ tx.details_count || tx.details?.length || 0 }} SKU
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[150px]">
                      {{ tx.details?.[0]?.product?.name || '' }}
                    </span>
                  </div>
                </td>

                <!-- Creator -->
                <td class="px-3 py-2.5 text-slate-500 dark:text-slate-400 text-xs whitespace-nowrap">
                  {{ tx.creator?.name || 'Sistem' }}
                </td>

                <!-- Action -->
                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      type="button"
                      @click="openDetail(tx)"
                      class="h-7 px-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs inline-flex items-center gap-1 cursor-pointer transition-colors"
                      title="Lihat Rincian Bukti Transaksi"
                    >
                      <Eye class="h-3.5 w-3.5 text-slate-400" />
                      Detail
                    </button>
            <a
              :href="route('transactions.receipt', tx.id)"
              target="_blank"
              class="h-7 px-2.5 rounded-lg border border-blue-200 dark:border-blue-900/60 bg-blue-50/60 dark:bg-blue-950/40 text-xs font-medium text-[#2563EB] dark:text-blue-400 hover:bg-blue-100/80 dark:hover:bg-blue-900/60 shadow-2xs inline-flex items-center gap-1 transition-colors"
              title="Unduh / Cetak PDF"
            >
              <Printer class="h-3.5 w-3.5" />
              Cetak PDF
            </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination & Total Counter (Konsisten Standar SIPALING) -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 text-xs">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ transactions.data.length }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ transactions.total }}</span> transaksi terdaftar
          </div>

          <div class="flex items-center gap-2">
            <span v-if="transactions.last_page > 1" class="text-xs text-slate-500 dark:text-slate-400 mr-1 hidden sm:inline">
              Halaman <span class="font-semibold text-slate-700 dark:text-slate-300">{{ transactions.current_page }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ transactions.last_page }}</span>
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

      <!-- Modal Detail Bukti Transaksi -->
      <Teleport to="body">
        <div
          v-if="isDetailModalOpen && selectedTransaction"
          class="receipt-modal-root fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <!-- Fixed Backdrop with Pure Opacity Fade (No Scale Glitch) -->
          <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            appear
          >
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="closeDetail" />
          </Transition>

          <!-- Modal Card with Smooth Scale & Opacity Popup -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
            appear
          >
            <div class="relative z-10 w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800">
           <div id="receipt-print-area" class="p-6 space-y-5">
            <div class="hidden print:block text-center mb-4 pb-3 border-b-2 border-slate-900">
              <h2 class="text-lg font-bold">SIPALING — Bukti Serah Terima Barang</h2>
              <p class="text-xs text-slate-500">Sistem Inventaris Prediktif & Audit Log Terintegrasi</p>
            </div>
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <div class="flex items-center gap-2">
                    <span
                      :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider',
                        selectedTransaction.type === 'inbound'
                          ? 'bg-emerald-50 text-[#10B981] border-emerald-200'
                          : 'bg-amber-50 text-amber-700 border-amber-200',
                      ]"
                    >
                      {{ selectedTransaction.type === 'inbound' ? 'Barang Masuk (Inbound)' : 'Barang Keluar (Outbound)' }}
                    </span>
                  </div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1">
                    Bukti Transaksi: {{ selectedTransaction.reference_no }}
                  </h3>
                </div>
                <button
                  type="button"
                  @click="closeDetail"
                  class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>

              <!-- Metadata Grid -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl text-xs">
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Waktu Transaksi</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatDate(selectedTransaction.transaction_date) }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ selectedTransaction.type === 'inbound' ? 'Pemasok' : 'Penerima' }}</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ selectedTransaction.party_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Petugas Pencatat</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ selectedTransaction.creator?.name || 'Sistem' }}</span>
                </div>
              </div>

              <!-- Item List Table -->
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Rincian Barang Mutasi</h4>
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 font-semibold uppercase text-[10px]">
                      <tr>
                        <th class="px-3 py-2">SKU & Nama Produk</th>
                        <th class="px-3 py-2 text-center">Kuantitas</th>
                        <th class="px-3 py-2 text-right">Harga Unit</th>
                        <th class="px-3 py-2">Keterangan</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                      <tr v-for="dt in selectedTransaction.details" :key="dt.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-3 py-2.5">
                          <p class="font-semibold text-xs text-slate-900 dark:text-white">{{ dt.product?.name }}</p>
                          <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono text-[10px]">{{ dt.product?.sku }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center font-bold tabular-nums whitespace-nowrap" :class="selectedTransaction.type === 'inbound' ? 'text-[#10B981]' : 'text-amber-600'">
                          {{ selectedTransaction.type === 'inbound' ? '+' : '-' }}{{ dt.quantity }} {{ dt.product?.unit }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-medium tabular-nums text-slate-700 dark:text-slate-300 whitespace-nowrap">
                          {{ formatRupiah(dt.unit_price) }}
                        </td>
                        <td class="px-3 py-2.5 text-slate-500 text-[11px]">
                          {{ dt.notes || '-' }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            <!-- Notes -->
            <div v-if="selectedTransaction.notes" class="text-xs bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl">
              <span class="font-semibold text-slate-700 dark:text-slate-300 block mb-0.5">Catatan:</span>
              <p class="text-slate-500 dark:text-slate-400">{{ selectedTransaction.notes }}</p>
            </div>

            <!-- Kolom tanda tangan khusus cetak -->
            <div class="signature-grid hidden print:grid grid-cols-2 gap-8 pt-8 mt-4 text-xs">
              <div class="text-center">
                <p class="mb-12">Diserahkan oleh,</p>
                <p class="border-t border-slate-900 pt-1">( _______________ )</p>
              </div>
              <div class="text-center">
                <p class="mb-12">Diterima oleh,</p>
                <p class="border-t border-slate-900 pt-1">( _______________ )</p>
              </div>
            </div>
              <div class="flex items-center justify-end gap-2 pt-3 no-print border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  @click="closeDetail"
                  class="rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                >
                  Tutup
                </button>
                </div>
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>

    <!-- Floating Action Pill untuk Cetak Berita Acara Rekapitulasi Kolektif -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform translate-y-6 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-6 opacity-0"
    >
      <div
        v-if="selectedIds.length > 0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 text-white dark:bg-slate-800/95 dark:text-white px-5 py-3 rounded-2xl shadow-2xl backdrop-blur-md border border-slate-700/80 flex items-center gap-4 max-w-[90vw]"
      >
        <div class="flex items-center gap-2 text-xs font-semibold">
          <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-[10px] text-white font-bold">
            {{ selectedIds.length }}
          </span>
          <span class="truncate">Faktur dipilih untuk Berita Acara Kolektif (1x TTD)</span>
        </div>
        <div class="h-4 w-px bg-slate-700 hidden sm:block"></div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            @click="selectedIds = []"
            class="h-8 px-3 rounded-xl hover:bg-slate-800 text-xs text-slate-300 hover:text-white transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="printBatchReceipts"
            class="h-8 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-600 text-white text-xs font-bold shadow-md inline-flex items-center gap-1.5 transition-colors cursor-pointer"
          >
            <Printer class="h-3.5 w-3.5" />
            <span>Cetak Berita Acara ({{ selectedIds.length }})</span>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Modal Dialog: Panduan & Opsi Cetak Berita Acara Kolektif -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isBatchPrintModalOpen"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
          @click.self="isBatchPrintModalOpen = false"
        >
          <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] flex items-center justify-center shrink-0">
                  <Printer class="h-5 w-5" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cetak Berita Acara Rekapitulasi</h3>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengesahan kolektif dokumen mutasi stok</p>
                </div>
              </div>
              <button
                type="button"
                @click="isBatchPrintModalOpen = false"
                class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 cursor-pointer"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-blue-50/50 dark:bg-blue-950/20 p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/50 space-y-1">
              <p class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <CheckCircle2 class="h-4 w-4 text-[#10B981]" />
                Solusi Praktis: Cukup 1x Tanda Tangan
              </p>
              <p>
                Untuk meringankan beban administrasi, sistem menggabungkan seluruh mutasi terpilih ke dalam 1 berkas Berita Acara Rekapitulasi resmi dengan <strong>pengesahan 1 kali bersama</strong> di akhir lembar dokumen (Petugas Pelaksana, Manajer, &amp; Komisaris).
              </p>
            </div>

            <div class="space-y-2 pt-1">
              <button
                type="button"
                @click="printAllCurrentPage"
                class="w-full h-10 px-4 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs transition-all cursor-pointer flex items-center justify-center gap-2 shadow-2xs"
              >
                <Printer class="h-4 w-4" />
                <span>Cetak Semua di Halaman Ini ({{ transactions.data.length }} Dokumen)</span>
              </button>
              <button
                type="button"
                @click="selectManualFromTable"
                class="w-full h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>Pilih Tertentu Lewat Centang Tabel</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    </div>
  </AuthenticatedLayout>
</template>
