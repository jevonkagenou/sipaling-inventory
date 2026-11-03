<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import RestockRequestDialog from '@/Components/Restock/RestockRequestDialog.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  TrendingUp,
  Cpu,
  BarChart3,
  CheckCircle2,
  AlertTriangle,
  AlertCircle,
  RotateCcw,
  Sparkles,
  Layers,
  ArrowRight,
  Boxes,
  Sliders,
  ShieldCheck,
  Send,
  Download,
  FileSpreadsheet,
  FileText,
  ChevronDown,
  Coins,
  Search,
  X,
  Filter,
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import DemandForecastChart from '@/Components/Inventory/DemandForecastChart.vue'
import AlgorithmPerformanceCard from '@/Components/Analytics/AlgorithmPerformanceCard.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  selectedProductId: {
    type: String,
    default: '',
  },
  forecastData: {
    type: Object,
    default: null,
  },
  restockAlerts: {
    type: Array,
    default: () => [],
  },
  executiveSummary: {
    type: Object,
    default: () => ({
      total_skus: 0,
      urgent_skus: 0,
      warning_skus: 0,
      safe_skus: 0,
      total_budget: 0,
      average_accuracy: 0,
      average_mape: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
})

// Current selected product object
const currentProduct = computed(() => {
  return (
    props.products.find((p) => p.id === props.selectedProductId) ||
    props.products[0] ||
    null
  )
})

// Alpha & Beta local state
const alphaInput = ref(
  props.filters?.alpha !== null && props.filters?.alpha !== undefined
    ? Number(props.filters.alpha)
    : props.forecastData?.optimal_alpha ?? 0.2
)
const betaInput = ref(
  props.filters?.beta !== null && props.filters?.beta !== undefined
    ? Number(props.filters.beta)
    : props.forecastData?.optimal_beta ?? 0.1
)
// Synchronize local slider state when Inertia props change (e.g. after reset to optimal)
watch(
  () => [props.filters, props.forecastData],
  () => {
    if (props.filters?.alpha !== null && props.filters?.alpha !== undefined && props.filters?.alpha !== '') {
      alphaInput.value = Number(props.filters.alpha)
    } else if (props.forecastData?.optimal_alpha !== undefined) {
      alphaInput.value = Number(props.forecastData.optimal_alpha)
    }

    if (props.filters?.beta !== null && props.filters?.beta !== undefined && props.filters?.beta !== '') {
      betaInput.value = Number(props.filters.beta)
    } else if (props.forecastData?.optimal_beta !== undefined) {
      betaInput.value = Number(props.forecastData.optimal_beta)
    }
  },
  { deep: true }
)
function onProductChange(productId) {
  router.get(
    route('analytics.index'),
    {
      product_id: productId,
      alpha: alphaInput.value,
      beta: betaInput.value,
    },
    { preserveState: true, preserveScroll: true }
  )
}

function applyCustomParams() {
  router.get(
    route('analytics.index'),
    {
      product_id: currentProduct.value?.id,
      alpha: Number(alphaInput.value),
      beta: Number(betaInput.value),
    },
    { preserveState: true, preserveScroll: true }
  )
}

function resetToOptimal() {
  router.get(
    route('analytics.index'),
    {
      product_id: currentProduct.value?.id,
    },
    { preserveState: true, preserveScroll: true }
  )
}

function applyPreset(type) {
  if (type === 'stabil') {
    alphaInput.value = 0.15
    betaInput.value = 0.05
  } else if (type === 'optimal') {
    alphaInput.value = props.forecastData?.optimal_alpha ?? 0.2
    betaInput.value = props.forecastData?.optimal_beta ?? 0.1
  } else if (type === 'responsif') {
    alphaInput.value = 0.45
    betaInput.value = 0.25
  }
}

// Chart SVG Calculation
const chartWidth = 760
const chartHeight = 240
const padding = { top: 20, right: 30, bottom: 40, left: 55 }

const chartData = computed(() => {
  if (!props.forecastData || !props.forecastData.has_sufficient_data) return null

  const histPeriods = props.forecastData.historical_periods || []
  const actuals = props.forecastData.actual_series || []
  const fitted = props.forecastData.fitted_series || []
  const futurePeriods = props.forecastData.future_periods || []
  const futureForecasts = props.forecastData.future_forecasts || []

  const allPeriods = [...histPeriods, ...futurePeriods]
  const allValues = [
    ...actuals,
    ...fitted.filter((v) => v !== null),
    ...futureForecasts,
  ]

  const maxVal = Math.max(...allValues, 10) * 1.15
  const minVal = 0

  const getX = (idx) => {
    const usableW = chartWidth - padding.left - padding.right
    const step = usableW / Math.max(allPeriods.length - 1, 1)
    return padding.left + idx * step
  }

  const getY = (val) => {
    if (val === null || val === undefined) return 0
    const usableH = chartHeight - padding.top - padding.bottom
    const ratio = (val - minVal) / (maxVal - minVal || 1)
    return chartHeight - padding.bottom - ratio * usableH
  }

  // Actual Line points
  const actualPoints = actuals.map((val, i) => ({
    x: getX(i),
    y: getY(val),
    val,
    period: histPeriods[i],
    type: 'actual',
  }))

  // Fitted Line points
  const fittedPoints = fitted.map((val, i) => ({
    x: getX(i),
    y: val !== null ? getY(val) : null,
    val,
    period: histPeriods[i],
    type: 'fitted',
  }))

  // Projection Points (connect from last actual point)
  const lastActual = actualPoints[actualPoints.length - 1]
  const futurePoints = futureForecasts.map((val, i) => ({
    x: getX(histPeriods.length + i),
    y: getY(val),
    val,
    period: futurePeriods[i],
    type: 'future',
  }))

  const projLinePoints = lastActual
    ? [lastActual, ...futurePoints]
    : futurePoints

  const toPath = (pts) => {
    const valid = pts.filter((p) => p.y !== null)
    if (!valid.length) return ''
    return valid.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
  }

  // Smooth curve pakai Catmull-Rom spline diubah jadi Bezier
  const toSmoothPath = (pts) => {
    const valid = pts.filter((p) => p.y !== null)
    if (valid.length < 2) return toPath(pts)
    let d = `M ${valid[0].x} ${valid[0].y}`
    for (let i = 0; i < valid.length - 1; i++) {
      const p0 = valid[i - 1] || valid[i]
      const p1 = valid[i]
      const p2 = valid[i + 1]
      const p3 = valid[i + 2] || p2
      const cp1x = p1.x + (p2.x - p0.x) / 6
      const cp1y = p1.y + (p2.y - p0.y) / 6
      const cp2x = p2.x - (p3.x - p1.x) / 6
      const cp2y = p2.y - (p3.y - p1.y) / 6
      d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`
    }
    return d
  }

  // Area fill di bawah garis Aktual
  const baselineY = chartHeight - padding.bottom
  const actualAreaPath = (() => {
    const valid = actualPoints.filter((p) => p.y !== null)
    if (valid.length < 2) return ''
    const linePath = toSmoothPath(valid)
    const last = valid[valid.length - 1]
    const first = valid[0]
    return `${linePath} L ${last.x} ${baselineY} L ${first.x} ${baselineY} Z`
  })()

  // Garis pembatas antara histori dan proyeksi
  const dividerX = futurePoints.length && lastActual ? (lastActual.x + futurePoints[0].x) / 2 : null

  return {
    periods: allPeriods,
    maxVal: Math.round(maxVal),
    actualPoints,
    fittedPoints,
    futurePoints,
    actualPath: toSmoothPath(actualPoints),
    actualAreaPath,
    fittedPath: toSmoothPath(fittedPoints),
    projPath: toSmoothPath(projLinePoints),
    dividerX,
    baselineY,
    getX,
    getY,
  }
})
// Parameter yang benar-benar dipakai pada hasil saat ini
// (nilai query bila disimulasikan manual, selain itu hasil grid search)
const appliedAlpha = computed(() => {
  const f = props.filters?.alpha
  return f !== null && f !== undefined && f !== '' ? Number(f) : props.forecastData?.optimal_alpha ?? null
})
const appliedBeta = computed(() => {
  const f = props.filters?.beta
  return f !== null && f !== undefined && f !== '' ? Number(f) : props.forecastData?.optimal_beta ?? null
})
const isOptimalParams = computed(() => {
  const hasAlphaFilter = props.filters?.alpha !== null && props.filters?.alpha !== undefined && props.filters?.alpha !== ''
  const hasBetaFilter = props.filters?.beta !== null && props.filters?.beta !== undefined && props.filters?.beta !== ''
  
  // Jika ada query filter alpha/beta di URL, artinya sedang dalam mode simulasi manual
  return !hasAlphaFilter && !hasBetaFilter
})
const hoveredPoint = ref(null)
// Multi-restock: pilihan produk dan modal pengajuan
const page = usePage()
const roles = computed(() => {
  const auth = page.props.auth || {}
  const raw = auth.roles ?? auth.user?.roles ?? []
  return raw.map((r) => (typeof r === 'string' ? r : r?.name))
})
const canRequestRestock = computed(() => roles.value.includes('manajer-operasional'))

function formatRupiah(val) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}

// Filter Tabel Rekomendasi Restock
const tableCategoryFilter = ref('all')
const tableStatusFilter = ref('all')
const tableSearch = ref('')

const filteredRestockAlerts = computed(() => {
  return props.restockAlerts.filter((item) => {
    // Filter Kategori
    if (tableCategoryFilter.value !== 'all' && item.category_id !== tableCategoryFilter.value) {
      return false
    }
    // Filter Status
    if (tableStatusFilter.value === 'urgent' && item.status !== 'RESTOCK_URGENT') {
      return false
    }
    if (tableStatusFilter.value === 'safe' && item.suggested_quantity > 0) {
      return false
    }
    // Filter Search
    if (tableSearch.value) {
      const q = tableSearch.value.toLowerCase().trim()
      const matchSku = item.sku?.toLowerCase().includes(q)
      const matchName = item.name?.toLowerCase().includes(q)
      if (!matchSku && !matchName) return false
    }
    return true
  })
})

const filteredEstimatedBudget = computed(() => {
  return filteredRestockAlerts.value.reduce((acc, item) => {
    return acc + (item.suggested_quantity > 0 ? (item.suggested_quantity * item.unit_price) : 0)
  }, 0)
})

const selectedIds = ref([])
const isRestockDialogOpen = ref(false)

const allSelected = computed(() => {
  if (filteredRestockAlerts.value.length === 0) return false
  return filteredRestockAlerts.value.every((i) => selectedIds.value.includes(i.id))
})
const someSelected = computed(() => {
  const count = filteredRestockAlerts.value.filter((i) => selectedIds.value.includes(i.id)).length
  return count > 0 && count < filteredRestockAlerts.value.length
})
const selectedItems = computed(() => props.restockAlerts.filter((i) => selectedIds.value.includes(i.id)))

function toggleAll() {
  if (allSelected.value) {
    const curIds = new Set(filteredRestockAlerts.value.map((i) => i.id))
    selectedIds.value = selectedIds.value.filter((id) => !curIds.has(id))
  } else {
    const newSet = new Set([...selectedIds.value, ...filteredRestockAlerts.value.map((i) => i.id)])
    selectedIds.value = Array.from(newSet)
  }
}

function selectAllUrgentItems() {
  const urgentIds = props.restockAlerts
    .filter((i) => i.status === 'RESTOCK_URGENT' || i.suggested_quantity > 0)
    .map((i) => i.id)
  selectedIds.value = urgentIds
  if (canRequestRestock.value) {
    isRestockDialogOpen.value = true
  } else {
    scrollToRecommendations()
  }
}

function onRestockSubmitted() {
  selectedIds.value = []
}

// Auto-scroll ke bagian Ringkasan Rekomendasi jika diarahkan dari 'Ajukan via Analitik DES'
const isSectionHighlighted = ref(false)

function scrollToRecommendations() {
  nextTick(() => {
    setTimeout(() => {
      const el = document.getElementById('ringkasan-rekomendasi')
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
        isSectionHighlighted.value = true
        setTimeout(() => {
          isSectionHighlighted.value = false
        }, 2500)
      }
    }, 120)
  })
}

function checkAndScroll() {
  if (typeof window === 'undefined') return
  const url = page.url || ''
  const hash = window.location.hash
  const urlParams = new URLSearchParams(window.location.search)

  const isTargeted =
    hash === '#ringkasan-rekomendasi' ||
    urlParams.get('section') === 'rekomendasi' ||
    urlParams.get('scroll') === 'rekomendasi' ||
    url.includes('section=rekomendasi') ||
    url.includes('#ringkasan-rekomendasi')

  if (isTargeted) {
    scrollToRecommendations()
  }
}

onMounted(() => {
  checkAndScroll()
})

watch(
  () => page.url,
  () => {
    checkAndScroll()
  }
)
</script>

<template>
  <Head title="Prediksi Kebutuhan Stok - SIPALING" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Intelijen Bisnis</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Prediksi Kebutuhan Stok
        </h1>
      </div>
    </template>

    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Header Banner & Keterangan Modul -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Proyeksi Kebutuhan &amp; Perencanaan Pengadaan
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Estimasi volume restock cerdas berbasis tren penjualan bulanan (Didukung Algoritma Double Exponential Smoothing).
          </p>
        </div>
      </div>

      <!-- Executive Helicopter View Header & Macro KPIs -->
      <div class="space-y-4">
        <!-- 4 Global Macro KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Card 1: Total SKU Terdaftar & Dipantau -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
              <span>Status Katalog Inventaris</span>
              <div class="h-8 w-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                <Boxes class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2.5 flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                {{ executiveSummary.total_skus }}
              </span>
              <span class="text-xs font-normal text-slate-500 dark:text-slate-400">SKU dipantau</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate">
              {{ executiveSummary.safe_skus }} SKU aman di atas batas minimum
            </p>
          </div>

          <!-- Card 2: SKU Kritis Butuh Restock Segera -->
          <div
            @click="tableStatusFilter = 'urgent'; scrollToRecommendations()"
            class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
            :class="executiveSummary.urgent_skus > 0
              ? 'border-rose-300/80 dark:border-rose-900/60 ring-2 ring-rose-500/10'
              : 'border-slate-200 dark:border-slate-800'"
            title="Klik untuk menyaring barang kritis pada tabel"
          >
            <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
              <span :class="executiveSummary.urgent_skus > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : ''">
                Kritis (Perlu Restock Segera)
              </span>
              <div
                class="h-8 w-8 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
                :class="executiveSummary.urgent_skus > 0
                  ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
              >
                <AlertCircle class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2.5 flex items-baseline gap-1.5">
              <span
                class="text-2xl font-bold tracking-tight tabular-nums"
                :class="executiveSummary.urgent_skus > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'"
              >
                {{ executiveSummary.urgent_skus }}
              </span>
              <span class="text-xs font-normal text-slate-500 dark:text-slate-400">SKU mendesak</span>
            </div>
            <p class="text-[11px] text-rose-600/90 dark:text-rose-400/90 mt-1 truncate">
              {{ executiveSummary.urgent_skus > 0 ? 'Defisit stok & risiko kehabisan barang' : 'Seluruh barang dalam batas aman' }}
            </p>
          </div>

          <!-- Card 3: Total Estimasi Anggaran Restock -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
              <span>Estimasi Anggaran Restock</span>
              <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center">
                <Coins class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2.5 flex items-baseline gap-1.5">
              <span class="text-xl sm:text-2xl font-bold tracking-tight text-[#2563EB] dark:text-blue-400 tabular-nums">
                {{ formatRupiah(executiveSummary.total_budget) }}
              </span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate">
              Total biaya pengadaan barang kritis
            </p>
          </div>

          <!-- Card 4: Rata-rata Akurasi Prediksi Sistem -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
              <span>Akurasi Model Global</span>
              <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center">
                <ShieldCheck class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2.5 flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
                {{ executiveSummary.average_accuracy }}%
              </span>
              <span class="text-xs font-normal text-slate-500 dark:text-slate-400">confidence</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate">
              Tingkat keyakinan agregat Holt DES
            </p>
          </div>
        </div>

        <!-- Executive Strategic Action Callout Banner -->
        <div class="rounded-2xl border border-[#2563EB]/25 bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-white dark:from-blue-950/40 dark:via-slate-900 dark:to-slate-900 p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-start gap-3.5 max-w-3xl">
            <div class="p-2.5 rounded-xl bg-[#2563EB]/10 text-[#2563EB] dark:bg-blue-950 dark:text-blue-400 shrink-0 mt-0.5">
              <Sparkles class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                Rekomendasi Tindakan Eksekutif (Executive Guidance)
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                <template v-if="executiveSummary.urgent_skus > 0">
                  Sistem mendeteksi <strong>{{ executiveSummary.urgent_skus }} SKU produk</strong> memerlukan tindakan pemesanan stok segera dengan estimasi nilai belanja sebesar <strong>{{ formatRupiah(executiveSummary.total_budget) }}</strong>. Disarankan untuk segera menyusun draf usulan pengadaan restock agar tidak mengganggu kelancaran operasional penjualan.
                </template>
                <template v-else>
                  Kondisi persediaan barang saat ini dalam keadaan aman dan berada di atas batas safety stock minimum. Rata-rata tingkat keyakinan proyeksi stabil di angka <strong>{{ executiveSummary.average_accuracy }}%</strong>.
                </template>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2.5 shrink-0 self-start md:self-auto">
            <button
              v-if="canRequestRestock && executiveSummary.urgent_skus > 0"
              type="button"
              @click="selectAllUrgentItems"
              class="h-9 px-4 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 active:scale-[0.98]"
            >
              <Send class="w-3.5 h-3.5" />
              <span>Ajukan Restock Kritis</span>
            </button>
            <button
              type="button"
              @click="scrollToRecommendations"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <ArrowRight class="w-3.5 h-3.5 text-slate-400" />
              <span>Tinjau Matriks SKU</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Section: Pemilihan Produk & Panel Kontrol Parameter -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Pilihan Produk & Indikator Stok Aktual -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
              Pilih Produk Evaluasi
            </span>
            <span class="text-xs text-slate-400">
              Total {{ products.length }} produk
            </span>
          </div>

          <select
            :value="selectedProductId"
            @change="onProductChange($event.target.value)"
            class="h-9 w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 text-xs font-medium text-slate-900 dark:text-slate-100 shadow-2xs transition-all duration-200 outline-none focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 cursor-pointer"
          >
            <option
              v-for="p in products"
              :key="p.id"
              :value="p.id"
            >
              [{{ p.sku }}] {{ p.name }}
            </option>
          </select>

          <!-- Kartu Detail Ringkas Produk -->
          <div v-if="currentProduct" class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800/60 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400">Kategori:</span>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                {{ currentProduct.category?.name || 'Retail' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400">Stok Fisik Saat Ini:</span>
              <span class="text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ currentProduct.current_stock }} {{ currentProduct.unit || 'pcs' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400">Batas Safety Stock:</span>
              <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 tabular-nums">
                {{ currentProduct.minimum_stock }} {{ currentProduct.unit || 'pcs' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Kolom Tengah & Kanan: Pengatur Parameter Humanis & Preset Kategori -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <Sliders class="w-4 h-4 text-[#2563EB]" />
                Sensitivitas &amp; Simulasi Prediksi
              </h2>
              <p class="text-[11px] text-slate-400 mt-0.5">
                Pilih kategori respon kesiapan sistem atau atur bobot tren secara manual.
              </p>
            </div>
            <button
              type="button"
              @click="resetToOptimal"
              class="h-9 px-3.5 rounded-xl border border-blue-200/80 dark:border-blue-800/60 bg-blue-50/70 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-[#2563EB] dark:text-blue-400 font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 self-start sm:self-auto"
            >
              <Sparkles class="w-3.5 h-3.5" />
              Rekomendasi Cerdas Sistem
            </button>
          </div>

          <!-- Pilihan Preset Kategori Cepat (Humanis & Non-Teknis) -->
          <div class="flex items-center gap-2 flex-wrap pt-0.5">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Level Siap Pakai:</span>
            <button
              type="button"
              @click="applyPreset('stabil')"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border border-blue-200/80 bg-blue-50/50 text-[#2563EB] hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300 transition-colors cursor-pointer"
            >
              🟢 Stabil (Konservatif)
            </button>
            <button
              type="button"
              @click="applyPreset('optimal')"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border border-emerald-200/80 bg-emerald-50/50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300 transition-colors cursor-pointer"
            >
              🔵 Seimbang (Optimal)
            </button>
            <button
              type="button"
              @click="applyPreset('responsif')"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border border-amber-200/80 bg-amber-50/50 text-amber-700 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300 transition-colors cursor-pointer"
            >
              🟡 Responsif (Cepat Tanggap)
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
            <!-- Alpha Slider -->
            <div class="space-y-2">
              <div class="flex justify-between items-center text-xs">
                <label class="font-semibold text-slate-700 dark:text-slate-300">
                  Bobot Tren Terkini (&alpha;): <span class="text-[#2563EB] tabular-nums font-bold">{{ alphaInput }}</span>
                </label>
                <span class="text-[10px] text-slate-400">Respons penjualan terakhir</span>
              </div>
              <input
                v-model.number="alphaInput"
                type="range"
                min="0.05"
                max="0.95"
                step="0.05"
                class="w-full accent-[#2563EB] cursor-pointer"
              />
            </div>

            <!-- Beta Slider -->
            <div class="space-y-2">
              <div class="flex justify-between items-center text-xs">
                <label class="font-semibold text-slate-700 dark:text-slate-300">
                  Bobot Arah Tren (&beta;): <span class="text-indigo-600 dark:text-indigo-400 tabular-nums font-bold">{{ betaInput }}</span>
                </label>
                <span class="text-[10px] text-slate-400">Adaptasi laju naik/turun</span>
              </div>
              <input
                v-model.number="betaInput"
                type="range"
                min="0.05"
                max="0.95"
                step="0.05"
                class="w-full accent-[#4F46E5] cursor-pointer"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="button"
              @click="applyCustomParams"
              class="h-9 px-4 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              Terapkan Simulasi Kustom
            </button>
          </div>
        </div>
      </div>

      <!-- Section: Kartu Hasil Akurasi & Rekomendasi Restock (Unified Interactive KPI Cards) -->
      <div v-if="forecastData" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Tingkat Keyakinan Model -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Tingkat Keyakinan</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <CheckCircle2 class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
              {{ forecastData.accuracy_rate ? forecastData.accuracy_rate + '%' : 'N/A' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Keandalan Prediksi (100% &minus; MAPE)
          </p>
        </div>

        <!-- 2. Toleransi Deviasi (MAPE & RMSE) -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#4F46E5]/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Toleransi Deviasi</span>
            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <BarChart3 class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ forecastData.mape ? forecastData.mape + '%' : 'N/A' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Simpangan: {{ forecastData.rmse ? Math.round(forecastData.rmse) : '-' }} unit (RMSE)
          </p>
        </div>

        <!-- 3. Estimasi Permintaan Bulan Depan -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Estimasi Kebutuhan</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <TrendingUp class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#2563EB] dark:text-blue-400 tabular-nums">
              {{ Math.round(forecastData.future_forecasts?.[0] || 0) }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">unit</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Periode: {{ forecastData.future_periods?.[0] || '-' }}
          </p>
        </div>

        <!-- 4. Saran Restock Pengadaan -->
        <div
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'hover:border-amber-500/50' : 'hover:border-emerald-500/50'"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Saran Pengadaan</span>
            <div
              class="h-8 w-8 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
              :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT'
                ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400'
                : 'bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400'"
            >
              <AlertTriangle class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span
              :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'text-amber-600 dark:text-amber-400' : 'text-[#10B981] dark:text-emerald-400'"
              class="text-2xl font-bold tracking-tight tabular-nums"
            >
              {{ forecastData.restock_recommendation?.suggested_quantity || 0 }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">unit</span>
          </div>
          <p
            class="text-[11px] mt-1 font-medium truncate"
            :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'"
          >
            {{ forecastData.restock_recommendation?.status_label }}
          </p>
        </div>
      </div>
          <!-- Section: Ringkasan Performa Algoritma (M4-FE-03) -->
      <AlgorithmPerformanceCard
        v-if="forecastData"
        :alpha="appliedAlpha"
        :beta="appliedBeta"
        :mape="forecastData.mape"
        :rmse="forecastData.rmse"
        :optimal-alpha="forecastData.optimal_alpha"
        :optimal-beta="forecastData.optimal_beta"
        :is-optimal="isOptimalParams"
      />
      <!-- Section: Visualisasi Kurva Tren Prediksi -->
      <div v-if="forecastData && forecastData.has_sufficient_data" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Layers class="w-4 h-4 text-[#2563EB]" />
              Kurva Permintaan Riil vs Proyeksi Kebutuhan
            </h2>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Data transaksi historis bulanan tertutup dan estimasi 3 periode ke depan.
            </p>
          </div>
        </div>

        <DemandForecastChart
          :historical-periods="forecastData.historical_periods"
          :actual-series="forecastData.actual_series"
          :fitted-series="forecastData.fitted_series"
          :future-periods="forecastData.future_periods"
          :future-forecasts="forecastData.future_forecasts"
          :unit="currentProduct?.unit || 'unit'"
          :height="260"
        />
      </div><div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 shadow-2xs flex flex-col items-center justify-center text-center min-h-[350px]">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-full mb-4">
          <AlertCircle class="w-10 h-10 text-[#2563EB] dark:text-blue-400" />
        </div>
        
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
          Data Peramalan Belum Cukup
        </h3>
        
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mb-6 leading-relaxed">
          Grafik peramalan (Double Exponential Smoothing) tidak dapat ditampilkan. Sistem membutuhkan <strong>minimal 2-3 bulan data tutup buku</strong> untuk memproyeksikan tren dengan akurat.
        </p>
        
        <div class="flex items-start bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 p-4 rounded-xl text-left max-w-md">
          <ShieldCheck class="w-5 h-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" />
          <div>
            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-400">Rekomendasi Saat Ini</h4>
            <p class="text-xs text-amber-700 dark:text-amber-500/80 mt-1">
              Sementara riwayat transaksi sedang dikumpulkan, rekomendasi persediaan untuk barang ini akan mengacu pada batas <strong>Safety Stock</strong>.
            </p>
          </div>
        </div>
      </div>

      <!-- Section: Matriks Peringatan Restock Seluruh Master Produk -->
      <div
        id="ringkasan-rekomendasi"
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden scroll-mt-20 transition-all duration-700"
        :class="{ 'ring-2 ring-[#2563EB] shadow-lg border-blue-400 dark:border-blue-500': isSectionHighlighted }"
      >
        <!-- Header Toolbar & Title -->
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex flex-col gap-3.5 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Boxes class="w-4 h-4 text-[#2563EB]" />
              Ringkasan Rekomendasi Pengadaan Seluruh Katalog Master
            </h2>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Diurutkan berdasarkan prioritas urgensi pengadaan (safety stock deficit).
            </p>
          </div>

          <div class="flex items-center gap-2.5 shrink-0 self-start xl:self-auto flex-wrap sm:flex-nowrap">
            <!-- Tombol Ajukan Restock -->
            <button
              v-if="canRequestRestock"
              type="button"
              :disabled="selectedIds.length === 0"
              @click="isRestockDialogOpen = true"
              class="h-8 sm:h-9 px-3.5 rounded-xl inline-flex items-center gap-1.5 text-xs font-semibold shadow-2xs transition-all"
              :class="selectedIds.length > 0
                ? 'bg-[#2563EB] hover:bg-blue-700 text-white cursor-pointer active:scale-[0.98]'
                : 'bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 cursor-not-allowed opacity-80'"
              :title="selectedIds.length > 0
                ? `Ajukan restock untuk ${selectedIds.length} produk terpilih`
                : 'Centang minimal satu barang pada tabel di bawah untuk mengajukan restock'"
            >
              <Send class="w-3.5 h-3.5" :class="selectedIds.length > 0 ? 'text-white' : 'text-slate-400 dark:text-slate-500'" />
              <span>Ajukan Restock</span>
              <span
                v-if="selectedIds.length > 0"
                class="ml-0.5 px-1.5 py-0.5 rounded-md bg-blue-800/60 text-white text-[10px] font-bold tabular-nums"
              >
                {{ selectedIds.length }}
              </span>
            </button>

            <!-- Dropdown Pilihan Format Unduh -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <button
                  type="button"
                  class="h-8 sm:h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 font-semibold text-xs shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer outline-none"
                  title="Pilih format unduhan lembar kerja rekomendasi restock"
                >
                  <Download class="w-3.5 h-3.5 text-[#2563EB]" />
                  <span class="hidden sm:inline">Unduh (.xlsx / .csv)</span>
                  <span class="sm:hidden">Unduh</span>
                  <ChevronDown class="w-3 h-3 text-slate-400 ml-0.5" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-56 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md p-1.5 rounded-xl">
                <DropdownMenuLabel class="text-[11px] text-slate-400 font-normal px-2 py-1">Pilih Format Unduhan</DropdownMenuLabel>
                <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800 my-1" />
                <DropdownMenuItem as-child>
                  <a
                    :href="route('analytics.export-restock', { format: 'xlsx' })"
                    class="flex items-center gap-2.5 px-2.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer transition-colors"
                  >
                    <FileSpreadsheet class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <div class="flex flex-col">
                      <span class="font-semibold text-slate-900 dark:text-white">Microsoft Excel (.xlsx)</span>
                      <span class="text-[10px] text-slate-400">Gaya palet, format rupiah & border</span>
                    </div>
                  </a>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <a
                    :href="route('analytics.export-restock', { format: 'csv' })"
                    class="flex items-center gap-2.5 px-2.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg cursor-pointer transition-colors"
                  >
                    <FileText class="w-4 h-4 text-[#2563EB] shrink-0" />
                    <div class="flex flex-col">
                      <span class="font-semibold text-slate-900 dark:text-white">Teks CSV (.csv)</span>
                      <span class="text-[10px] text-slate-400">Ringan, kompatibel UTF-8 BOM</span>
                    </div>
                  </a>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <!-- Filter Sub-Toolbar (Kategori, Status Tab, & Pencarian Cepat) -->
        <div class="p-4 sm:p-5 bg-slate-50/50 dark:bg-slate-950/30 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between gap-3 flex-wrap">
          <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Filter Status Tabs -->
            <div class="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
              <button
                type="button"
                @click="tableStatusFilter = 'all'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                  tableStatusFilter === 'all'
                    ? 'bg-white text-slate-900 shadow-2xs dark:bg-slate-900 dark:text-white'
                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                ]"
              >
                Semua ({{ restockAlerts.length }})
              </button>
              <button
                type="button"
                @click="tableStatusFilter = 'urgent'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                  tableStatusFilter === 'urgent'
                    ? 'bg-rose-600 text-white shadow-2xs'
                    : 'text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400',
                ]"
              >
                <AlertCircle class="h-3.5 w-3.5" />
                Perlu Restock ({{ executiveSummary.urgent_skus }})
              </button>
              <button
                type="button"
                @click="tableStatusFilter = 'safe'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                  tableStatusFilter === 'safe'
                    ? 'bg-[#10B981] text-white shadow-2xs'
                    : 'text-slate-500 hover:text-[#10B981] dark:text-slate-400 dark:hover:text-emerald-400',
                ]"
              >
                <CheckCircle2 class="h-3.5 w-3.5" />
                Aman ({{ executiveSummary.safe_skus }})
              </button>
            </div>

            <!-- Dropdown Filter Kategori -->
            <select
              v-if="props.categories && props.categories.length > 0"
              v-model="tableCategoryFilter"
              aria-label="Filter Kategori Produk"
              class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 shadow-2xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 transition-all hover:border-slate-300 dark:hover:border-slate-700 focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 cursor-pointer outline-none shrink-0"
            >
              <option value="all">Semua Kategori</option>
              <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <!-- Pencarian SKU / Nama Produk -->
          <div class="relative w-full md:w-64">
            <input
              v-model="tableSearch"
              type="text"
              placeholder="Cari SKU atau nama..."
              class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-8 py-1.5 text-xs font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-900 dark:text-white shadow-2xs"
            />
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
            <button
              v-if="tableSearch"
              type="button"
              @click="tableSearch = ''"
              class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
              title="Hapus pencarian"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
              <tr class="h-10">
                <th v-if="canRequestRestock" class="px-3 py-2.5 w-10">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    :indeterminate="someSelected"
                    @change="toggleAll"
                    class="h-3.5 w-3.5 rounded border-slate-300 text-[#2563EB] cursor-pointer"
                    title="Pilih semua"
                  />
                </th>
                <th class="px-3 py-2.5 text-left whitespace-nowrap">SKU</th>
                <th class="px-3 py-2.5 text-left whitespace-nowrap">Nama Barang</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok Aktual</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Safety Stock</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Estimasi Kebutuhan</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Saran Pengadaan</th>
                <th class="px-3 py-2.5 text-center whitespace-nowrap">Status</th>
                <th class="px-3 py-2.5 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr
                v-for="item in filteredRestockAlerts"
                :key="item.id"
                class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                :class="item.id === selectedProductId ? 'bg-blue-50/40 dark:bg-blue-950/20' : ''"
              >
                <td v-if="canRequestRestock" class="px-3 py-2.5">
                  <input
                    v-model="selectedIds"
                    :value="item.id"
                    type="checkbox"
                    class="h-3.5 w-3.5 rounded border-slate-300 text-[#2563EB] cursor-pointer"
                  />
                </td>
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono tracking-wider text-[11px] font-semibold">
                    {{ item.sku }}
                  </span>
                </td>
                <td class="px-3 py-2.5 font-semibold text-slate-900 dark:text-white text-xs truncate max-w-[200px]" :title="item.name">
                  {{ item.name }}
                </td>
                <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 dark:text-slate-300 text-xs font-medium whitespace-nowrap">
                  {{ item.current_stock }} {{ item.unit }}
                </td>
                <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 text-xs whitespace-nowrap">
                  {{ item.minimum_stock }} {{ item.unit }}
                </td>
                <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-[#2563EB] dark:text-blue-400 text-xs whitespace-nowrap">
                  {{ item.next_forecast }} {{ item.unit }}
                </td>
                <td class="px-3 py-2.5 text-right tabular-nums font-bold text-xs whitespace-nowrap" :class="item.suggested_quantity > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-[#10B981]'">
                  {{ item.suggested_quantity }} {{ item.unit }}
                </td>
                <td class="px-3 py-2.5 text-center whitespace-nowrap">
                  <span
                    :class="item.status === 'RESTOCK_URGENT'
                      ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/60'
                      : 'bg-emerald-50 dark:bg-emerald-950/50 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full"
                      :class="item.status === 'RESTOCK_URGENT' ? 'bg-amber-500' : 'bg-[#10B981]'"
                    />
                    {{ item.status_label }}
                  </span>
                </td>
                <td class="px-3 py-2.5 text-center whitespace-nowrap">
                  <button
                    type="button"
                    @click="onProductChange(item.id)"
                    class="h-7 px-2.5 rounded-lg border border-blue-200/80 dark:border-blue-800/60 bg-blue-50/60 dark:bg-blue-950/40 text-[11px] font-semibold text-[#2563EB] dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 cursor-pointer transition-colors"
                  >
                    Simulasi
                  </button>
                </td>
              </tr>
              <tr v-if="filteredRestockAlerts.length === 0">
                <td :colspan="canRequestRestock ? 9 : 8" class="px-4 py-8 text-center text-xs text-slate-400 dark:text-slate-500">
                  Tidak ada produk yang memenuhi kriteria filter aktif.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Total Counter (Konsisten Standar SIPALING) -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 text-xs">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ filteredRestockAlerts.length }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ restockAlerts.length }}</span> produk
            <template v-if="filteredEstimatedBudget > 0">
              &bull; Subtotal Anggaran: <span class="font-bold text-[#2563EB] dark:text-blue-400 tabular-nums">{{ formatRupiah(filteredEstimatedBudget) }}</span>
            </template>
          </div>
          <div class="text-[11px] text-slate-400">
            Didukung Algoritma: Double Exponential Smoothing (Holt)
          </div>
        </div>
      </div>
    </div>

    <RestockRequestDialog
      v-model:open="isRestockDialogOpen"
      :items="selectedItems"
      @submitted="onRestockSubmitted"
    />
  </AuthenticatedLayout>
</template>