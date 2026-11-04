<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import RestockRequestDialog from '@/Components/Restock/RestockRequestDialog.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DemandForecastChart from '@/Components/Inventory/DemandForecastChart.vue'
import {
  TrendingUp,
  BarChart3,
  CheckCircle2,
  AlertCircle,
  RotateCcw,
  Sparkles,
  ArrowLeft,
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
  AlertTriangle,
  PackageMinus,
  SlidersHorizontal,
  ChevronUp,
  Info,
  Clock,
  Calculator,
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

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

// Active View Tab: 'recommendations' (Matrix) vs 'simulation' (Trend Analysis)
const activeTab = ref('recommendations')
const showAdvancedSliders = ref(false)

// Currency Formatter
function formatRupiah(val) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}

// Alpha & Beta parameter state
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
      tab: 'simulation',
    },
    { preserveState: true, preserveScroll: true }
  )
}

function openProductSimulation(productId) {
  activeTab.value = 'simulation'
  if (productId !== props.selectedProductId) {
    onProductChange(productId)
  }
}

function applyCustomParams() {
  router.get(
    route('analytics.index'),
    {
      product_id: currentProduct.value?.id,
      tab: 'simulation',
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
      tab: 'simulation',
    },
    { preserveState: true, preserveScroll: true }
  )
}

const isOptimalParams = computed(() => {
  const hasAlpha = props.filters?.alpha !== null && props.filters?.alpha !== undefined && props.filters?.alpha !== ''
  const hasBeta = props.filters?.beta !== null && props.filters?.beta !== undefined && props.filters?.beta !== ''
  return !hasAlpha && !hasBeta
})

const activePreset = computed(() => {
  if (isOptimalParams.value) return 'optimal'
  const a = Number(alphaInput.value)
  const b = Number(betaInput.value)
  if (Math.abs(a - 0.15) < 0.01 && Math.abs(b - 0.05) < 0.01) return 'stabil'
  if (Math.abs(a - 0.45) < 0.01 && Math.abs(b - 0.25) < 0.01) return 'responsif'
  return 'custom'
})

function applyPreset(type) {
  if (type === 'optimal') {
    resetToOptimal()
    return
  }
  if (type === 'stabil') {
    alphaInput.value = 0.15
    betaInput.value = 0.05
  } else if (type === 'responsif') {
    alphaInput.value = 0.45
    betaInput.value = 0.25
  }
  applyCustomParams()
}

// Multi-Restock Request
const page = usePage()
const roles = computed(() => {
  const auth = page.props.auth || {}
  const raw = auth.roles ?? auth.user?.roles ?? []
  return raw.map((r) => (typeof r === 'string' ? r : r?.name))
})
const canRequestRestock = computed(() => roles.value.includes('manajer-operasional'))

const selectedIds = ref([])
const isRestockDialogOpen = ref(false)

// Filters for Restock Recommendation Table
const tableCategoryFilter = ref('all')
const tableStatusFilter = ref('all')
const tableSearch = ref('')

const filteredRestockAlerts = computed(() => {
  return props.restockAlerts.filter((item) => {
    if (tableCategoryFilter.value !== 'all' && item.category_id !== tableCategoryFilter.value) {
      return false
    }
    if (tableStatusFilter.value === 'urgent' && item.status !== 'RESTOCK_URGENT') {
      return false
    }
    if (tableStatusFilter.value === 'warning' && (item.status === 'RESTOCK_URGENT' || item.suggested_quantity <= 0)) {
      return false
    }
    if (tableStatusFilter.value === 'safe' && item.suggested_quantity > 0) {
      return false
    }
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
    return acc + (item.suggested_quantity > 0 ? item.suggested_quantity * item.unit_price : 0)
  }, 0)
})

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

const totalNeedingRestock = computed(() => {
  return props.restockAlerts.filter((i) => i.suggested_quantity > 0).length
})

function submitRestock() {
  if (selectedIds.value.length === 0) {
    selectedIds.value = props.restockAlerts
      .filter((i) => i.suggested_quantity > 0)
      .map((i) => i.id)
  }
  if (canRequestRestock.value) {
    isRestockDialogOpen.value = true
  } else {
    activeTab.value = 'recommendations'
  }
}

function selectAllUrgentItems() {
  submitRestock()
}

function openRestockForCurrentProduct() {
  if (!currentProduct.value) return
  selectedIds.value = [currentProduct.value.id]
  if (canRequestRestock.value) {
    isRestockDialogOpen.value = true
  } else {
    activeTab.value = 'recommendations'
  }
}

function onRestockSubmitted() {
  selectedIds.value = []
}

// Insight ramah pengguna tanpa bahasa matematika kaku
const trendInsight = computed(() => {
  if (!props.forecastData || !props.forecastData.future_forecasts?.length) return null
  const future = props.forecastData.future_forecasts
  const actuals = props.forecastData.actual_series || []
  const lastActual = actuals[actuals.length - 1] ?? 0
  const nextForecast = Math.round(future[0] ?? 0)

  const currentStock = currentProduct.value?.current_stock ?? 0
  const minStock = currentProduct.value?.minimum_stock ?? 0
  const unit = currentProduct.value?.unit || 'pcs'
  const suggestedQty = props.forecastData.restock_recommendation?.suggested_quantity ?? 0
  const isUrgent = props.forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' || currentStock <= minStock

  // 1. Kondisi Stok Kritis (di bawah atau sama dengan batas aman minimum)
  if (isUrgent) {
    if (nextForecast > 0) {
      return {
        type: 'urgent',
        title: 'Perhatian: Stok Kritis di Bawah Batas Aman',
        desc: `Sisa stok di gudang (${currentStock} ${unit}) sudah menipis di bawah batas aman (${minStock} ${unit}), sedangkan kebutuhan bulan depan diproyeksikan mencapai ${nextForecast.toLocaleString('id-ID')} ${unit}. Segera ajukan pengadaan restock${suggestedQty > 0 ? ` sebanyak ${suggestedQty.toLocaleString('id-ID')} ${unit}` : ''} agar operasional tidak terhenti akibat kehabisan stok.`,
      }
    } else {
      return {
        type: 'urgent',
        title: 'Perhatian: Stok Berada di Bawah Batas Cadangan',
        desc: `Sisa stok di gudang (${currentStock} ${unit}) berada di bawah batas minimum (${minStock} ${unit}), namun tren proyeksi permintaan bulan depan tercatat nihil (0 ${unit}). Anda dapat menunda pemesanan baru atau hanya memesan jumlah minimal jika diperlukan untuk cadangan darurat.`,
      }
    }
  }

  // 2. Kondisi Permintaan Nihil / Belum Ada Kebutuhan (nextForecast <= 0)
  if (nextForecast <= 0) {
    if (currentStock > 0) {
      return {
        type: 'caution',
        title: 'Belum Ada Proyeksi Kebutuhan (Nihil Permintaan)',
        desc: `Berdasarkan pola transaksi historis, sistem memproyeksikan belum ada kebutuhan penjualan untuk bulan depan (0 ${unit}). Persediaan fisik saat ini (${currentStock} ${unit}) masih sangat memadai, sehingga Anda tidak perlu melakukan restock agar kas operasional tidak tertahan dalam bentuk stok mati (dead stock).`,
      }
    } else {
      return {
        type: 'caution',
        title: 'Stok Kosong & Tren Permintaan Nihil',
        desc: `Stok saat ini kosong (0 ${unit}) dan tren permintaan bulan depan tercatat nihil. Pantau pergerakan pasar atau permintaan riil pelanggan terlebih dahulu sebelum memutuskan pengadaan baru.`,
      }
    }
  }

  // 3. Kondisi Tren Penjualan Meningkat Signifikan (> 8% dari transaksi terakhir)
  if (lastActual > 0 && nextForecast > lastActual * 1.08) {
    return {
      type: 'up',
      title: 'Peluang: Tren Penjualan Diperkirakan Meningkat',
      desc: `Permintaan bulan depan diperkirakan naik menjadi ${nextForecast.toLocaleString('id-ID')} ${unit} (meningkat dari ${lastActual.toLocaleString('id-ID')} ${unit} pada periode lalu). ${suggestedQty > 0 ? `Disarankan menyiapkan pemesanan restock sebanyak ${suggestedQty.toLocaleString('id-ID')} ${unit} lebih awal agar siap memenuhi lonjakan pesanan pelanggan.` : `Persediaan stok saat ini (${currentStock} ${unit}) sudah mencukupi untuk memenuhi kenaikan tersebut.`}`,
    }
  }

  // 4. Kondisi Tren Penjualan Menurun Signifikan (< 92% dari transaksi terakhir)
  if (lastActual > 0 && nextForecast < lastActual * 0.92) {
    return {
      type: 'down',
      title: 'Tren Penjualan Mengalami Penurunan',
      desc: `Permintaan bulan depan diproyeksikan melambat menjadi ${nextForecast.toLocaleString('id-ID')} ${unit} (turun dari ${lastActual.toLocaleString('id-ID')} ${unit} pada periode lalu). ${suggestedQty > 0 ? `Batasi pembelian restock baru secukupnya (disarankan ${suggestedQty.toLocaleString('id-ID')} ${unit})` : 'Tidak perlu menambah stok baru'} agar barang tidak menumpuk di gudang dan perputaran modal tetap sehat.`,
    }
  }

  // 5. Kondisi Penjualan Stabil
  return {
    type: 'stable',
    title: 'Pola Penjualan Cenderung Stabil',
    desc: `Aktivitas pembelian pelanggan relatif teratur di kisaran ${nextForecast.toLocaleString('id-ID')} ${unit} per bulan. ${suggestedQty > 0 ? `Lakukan pengadaan berkala sebanyak ${suggestedQty.toLocaleString('id-ID')} ${unit} sesuai rekomendasi sistem guna menjaga kelancaran stok.` : `Persediaan di gudang (${currentStock} ${unit}) dalam kondisi aman untuk memenuhi kebutuhan operasional.`}`,
  }
})

// Estimasi Ketahanan Stok (Stock Runway dalam hitungan hari)
const stockRunway = computed(() => {
  const current = currentProduct.value?.current_stock ?? 0
  const unit = currentProduct.value?.unit || 'pcs'
  const forecast = Math.round(props.forecastData?.future_forecasts?.[0] || 0)

  if (forecast <= 0) {
    return {
      status: 'safe',
      label: 'Stok Sangat Aman (> 90 hari)',
      desc: 'Tidak ada penurunan stok cepat karena permintaan bulan depan diproyeksikan nihil.',
      badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400',
    }
  }

  const dailyRate = forecast / 30
  const days = Math.round(current / dailyRate)

  if (days <= 3) {
    return {
      status: 'critical',
      label: days <= 1 ? 'Kritis: Habis dalam < 1 hari!' : `Kritis: Habis dalam ~${days} hari!`,
      desc: `Laju laku sekitar ${dailyRate.toFixed(1)} ${unit}/hari. Persediaan fisik (${current} ${unit}) akan segera habis tanpa restock segera.`,
      badgeClass: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-400',
    }
  } else if (days <= 14) {
    return {
      status: 'warning',
      label: `Waspada: Cukup untuk ~${days} hari`,
      desc: `Laju laku sekitar ${dailyRate.toFixed(1)} ${unit}/hari. Stok berisiko menipis sebelum pertengahan bulan.`,
      badgeClass: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-400',
    }
  } else {
    return {
      status: 'safe',
      label: `Aman: Cukup untuk ~${days} hari`,
      desc: `Laju laku sekitar ${dailyRate.toFixed(1)} ${unit}/hari. Stok saat ini mencukupi kebutuhan penjualan mendatang.`,
      badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400',
    }
  }
})

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('tab') === 'simulation' || urlParams.get('product_id')) {
    activeTab.value = 'simulation'
  }
})
</script>

<template>
  <Head title="Prediksi & Perencanaan Restock - SIPALING" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Analitik &amp; Peramalan</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Prediksi Permintaan &amp; Restock
        </h1>
      </div>
    </template>

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Header Banner & Action Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Perencanaan Belanja &amp; Prediksi Stok
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Membantu Anda mengetahui barang mana yang mau habis, perkiraan laku bulan depan, dan berapa banyak yang harus dipesan.
          </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
          <!-- Dropdown Unduh Lembar Kerja -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                type="button"
                class="h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 hover:text-[#2563EB] font-semibold text-xs shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer outline-none"
                title="Pilih format unduhan rekomendasi restock"
              >
                <Download class="w-3.5 h-3.5 text-[#2563EB]" />
                <span class="hidden sm:inline">Unduh Rekap</span>
                <span class="sm:hidden">Unduh</span>
                <ChevronDown class="w-3 h-3 text-slate-400 ml-0.5" />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md p-1.5 rounded-xl z-50">
              <DropdownMenuLabel class="text-[11px] text-slate-400 font-normal px-2 py-1">Pilih Format Unduhan</DropdownMenuLabel>
              <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800 my-1" />
              <DropdownMenuItem as-child>
                <a
                  :href="route('analytics.export-restock', { format: 'xlsx' })"
                  class="flex items-center gap-2.5 px-2.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer transition-colors"
                >
                  <FileSpreadsheet class="w-4 h-4 text-emerald-600 shrink-0" />
                  <div class="flex flex-col">
                    <span class="font-semibold text-slate-900 dark:text-white">Excel Spreadsheet (.xlsx)</span>
                    <span class="text-[10px] text-slate-400">Siap cetak, format rupiah</span>
                  </div>
                </a>
              </DropdownMenuItem>
              <DropdownMenuItem as-child>
                <a
                  :href="route('analytics.export-restock', { format: 'csv' })"
                  class="flex items-center gap-2.5 px-2.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#2563EB] hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg cursor-pointer transition-colors"
                >
                  <FileText class="w-4 h-4 text-[#2563EB] shrink-0" />
                  <div class="flex flex-col">
                    <span class="font-semibold text-slate-900 dark:text-white">Berkas CSV (.csv)</span>
                    <span class="text-[10px] text-slate-400">Format ringkas UTF-8 BOM</span>
                  </div>
                </a>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <!-- Tombol Ajukan Restock Cepat -->
          <button
            v-if="canRequestRestock"
            type="button"
            @click="submitRestock"
            class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 active:scale-[0.98]"
            :title="selectedIds.length > 0 ? 'Ajukan restock untuk ' + selectedIds.length + ' barang terpilih' : 'Ajukan usulan draf restock untuk seluruh produk yang butuh belanja'"
          >
            <Send class="w-3.5 h-3.5" />
            <span>{{ selectedIds.length > 0 ? 'Ajukan Pilihan' : 'Ajukan Restock' }}</span>
            <span
              v-if="selectedIds.length > 0 || totalNeedingRestock > 0"
              class="ml-0.5 px-1.5 py-0.5 rounded-md bg-blue-800 text-white text-[10px] font-bold tabular-nums"
            >
              {{ selectedIds.length > 0 ? selectedIds.length : totalNeedingRestock }}
            </span>
          </button>
        </div>
      </div>

      <!-- 4 Ringkasan Angka Utama (Sangat Mudah Dipahami Siapapun) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Produk Dipantau -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Produk Dipantau</span>
            <div class="h-8 w-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
              <Boxes class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ executiveSummary.total_skus }}
            </span>
            <span class="text-xs font-normal text-slate-500">jenis barang</span>
          </div>
          <p class="text-[11px] text-slate-400 mt-1 truncate">
            {{ executiveSummary.safe_skus > 0 ? executiveSummary.safe_skus + ' barang kondisi aman' : (executiveSummary.urgent_skus + ' kritis, ' + executiveSummary.warning_skus + ' perlu restock') }}
          </p>
        </div>

        <!-- 2. Barang Mau Habis (Kritis) -->
        <div
          @click="activeTab = 'recommendations'; tableStatusFilter = 'urgent'"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs transition-all cursor-pointer group"
          :class="executiveSummary.urgent_skus > 0
            ? 'border-rose-200 dark:border-rose-900/60 hover:border-rose-400'
            : 'border-slate-200 dark:border-slate-800'"
          title="Klik untuk menyaring barang yang harus segera dibeli"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span :class="executiveSummary.urgent_skus > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : ''">
              Perlu Dibeli Segera
            </span>
            <div
              class="h-8 w-8 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
              :class="executiveSummary.urgent_skus > 0
                ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
            >
              <AlertTriangle class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span
              class="text-2xl font-bold tracking-tight tabular-nums"
              :class="executiveSummary.urgent_skus > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'"
            >
              {{ executiveSummary.urgent_skus }}
            </span>
            <span class="text-xs font-normal text-slate-500">barang mau habis</span>
          </div>
          <p class="text-[11px] text-rose-600/90 dark:text-rose-400/90 mt-1 truncate font-medium">
            {{ executiveSummary.urgent_skus > 0 ? 'Stok di bawah batas minimal gudang' : 'Semua persediaan masih cukup' }}
          </p>
        </div>

        <!-- 3. Perkiraan Total Biaya Belanja -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 transition-all">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Perkiraan Total Belanja</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center">
              <Coins class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-xl sm:text-2xl font-bold tracking-tight text-[#2563EB] dark:text-blue-400 tabular-nums">
              {{ formatRupiah(executiveSummary.total_budget) }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400 mt-1 truncate">
            Biaya untuk memesan seluruh barang kritis
          </p>
        </div>

        <!-- 4. Rata-rata Akurasi Prediksi -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 transition-all">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Tingkat Kepastian Prediksi</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center">
              <ShieldCheck class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
              {{ executiveSummary.average_accuracy }}%
            </span>
            <span class="text-xs font-normal text-slate-500">akurasi</span>
          </div>
          <p class="text-[11px] text-slate-400 mt-1 truncate">
            Tingkat keyakinan perhitungan sistem
          </p>
        </div>
      </div>

      <!-- Navigation Tabs: Daftar Belanja vs Grafik Detail Produk -->
      <div class="border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2 -mb-px">
          <button
            type="button"
            @click="activeTab = 'recommendations'"
            class="px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'recommendations'
              ? 'border-[#2563EB] text-[#2563EB] dark:border-blue-500 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'"
          >
            <Boxes class="w-4 h-4" />
            <span>Daftar Restock</span>
            <span
              v-if="executiveSummary.urgent_skus > 0"
              class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 tabular-nums"
            >
              {{ executiveSummary.urgent_skus }} Kritis
            </span>
          </button>

          <button
            type="button"
            @click="activeTab = 'simulation'"
            class="px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'simulation'
              ? 'border-[#2563EB] text-[#2563EB] dark:border-blue-500 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'"
          >
            <TrendingUp class="w-4 h-4" />
            <span>Grafik &amp; Prediksi</span>
            <span
              v-if="currentProduct"
              class="hidden sm:inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-600 dark:text-slate-300 font-mono"
            >
              {{ currentProduct.sku }}
            </span>
          </button>
        </div>

        <div v-if="activeTab === 'simulation' && currentProduct" class="hidden md:flex items-center gap-2 text-xs text-slate-500">
          <span>Barang dipilih:</span>
          <span class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">{{ currentProduct.name }}</span>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- TAB 1: DAFTAR KEBUTUHAN BELANJA RESTOCK                                   -->
      <!-- ========================================================================= -->
      <div v-show="activeTab === 'recommendations'" class="space-y-4">
        <!-- Table Card -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
          <!-- Toolbar Filter -->
          <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-950/20">
            <div class="flex items-center gap-2.5 flex-wrap">
              <!-- Filter Status Tabs -->
              <div class="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800 flex-wrap gap-1">
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
                  Kritis ({{ executiveSummary.urgent_skus }})
                </button>
                <button
                  type="button"
                  @click="tableStatusFilter = 'warning'"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                    tableStatusFilter === 'warning'
                      ? 'bg-amber-600 text-white shadow-2xs'
                      : 'text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400',
                  ]"
                >
                  <AlertTriangle class="h-3.5 w-3.5" />
                  Restock ({{ executiveSummary.warning_skus }})
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

              <!-- Tombol Cepat Ajukan Pilihan -->
              <button
                v-if="canRequestRestock && selectedIds.length > 0"
                type="button"
                @click="isRestockDialogOpen = true"
                class="h-9 px-3 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-semibold shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 active:scale-95 shrink-0"
              >
                <Send class="w-3.5 h-3.5" />
                <span>Ajukan ({{ selectedIds.length }} Dipilih)</span>
              </button>

              <!-- Dropdown Kategori -->
              <select
                v-if="props.categories && props.categories.length > 0"
                v-model="tableCategoryFilter"
                aria-label="Filter Kategori Produk"
                class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 shadow-2xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 cursor-pointer outline-none"
              >
                <option value="all">Semua Kategori</option>
                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <!-- Pencarian SKU / Nama -->
            <div class="relative w-full md:w-64">
              <input
                v-model="tableSearch"
                type="text"
                placeholder="Cari nama atau kode barang..."
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

          <!-- Tabel Kebutuhan Belanja (Ramping Tanpa Scroll Horizontal) -->
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
                <tr class="h-10">
                  <th v-if="canRequestRestock" class="px-3 py-2.5 w-10 text-center">
                    <input
                      type="checkbox"
                      :checked="allSelected"
                      :indeterminate="someSelected"
                      @change="toggleAll"
                      class="h-3.5 w-3.5 rounded border-slate-300 text-[#2563EB] cursor-pointer"
                      title="Pilih semua baris"
                    />
                  </th>
                  <th class="px-3 py-2.5 text-left">Barang</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok Gudang</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Perkiraan Laku</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Saran Restock</th>
                  <th class="px-3 py-2.5 text-center whitespace-nowrap">Status</th>
                  <th class="px-3 py-2.5 text-center whitespace-nowrap w-16">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <tr
                  v-for="item in filteredRestockAlerts"
                  :key="item.id"
                  class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                  :class="item.id === selectedProductId ? 'bg-blue-50/30 dark:bg-blue-950/20' : ''"
                >
                  <td v-if="canRequestRestock" class="px-3 py-2.5 text-center w-10">
                    <input
                      v-model="selectedIds"
                      :value="item.id"
                      type="checkbox"
                      class="h-3.5 w-3.5 rounded border-slate-300 text-[#2563EB] cursor-pointer"
                    />
                  </td>
                  <td class="px-3 py-2.5">
                    <div class="font-semibold text-slate-900 dark:text-white text-xs truncate max-w-[240px]" :title="item.name">
                      {{ item.name }}
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5">
                      <span class="font-mono font-medium text-slate-600 dark:text-slate-300">{{ item.sku }}</span>
                      <span>&bull;</span>
                      <span>{{ item.category_name }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-2.5 text-right whitespace-nowrap tabular-nums">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                      {{ item.current_stock }} {{ item.unit }}
                    </div>
                    <div class="text-[10px] text-slate-400">
                      Min: {{ item.minimum_stock }} {{ item.unit }}
                    </div>
                  </td>
                  <td class="px-3 py-2.5 text-right whitespace-nowrap tabular-nums font-semibold text-[#2563EB] dark:text-blue-400 text-xs">
                    {{ item.next_forecast }} {{ item.unit }}
                  </td>
                  <td class="px-3 py-2.5 text-right whitespace-nowrap tabular-nums">
                    <div class="text-xs font-bold" :class="item.suggested_quantity > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-[#10B981]'">
                      {{ item.suggested_quantity > 0 ? `+${item.suggested_quantity} ${item.unit}` : 'Cukup' }}
                    </div>
                    <div v-if="item.suggested_quantity > 0" class="text-[10px] text-slate-400">
                      {{ formatRupiah(item.suggested_quantity * item.unit_price) }}
                    </div>
                  </td>
                  <td class="px-3 py-2.5 text-center whitespace-nowrap">
                    <span
                      v-if="item.status === 'RESTOCK_URGENT'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/60"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-rose-500" />
                      Kritis
                    </span>
                    <span
                      v-else-if="item.suggested_quantity > 0"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/60"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                      Restock
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-50 dark:bg-emerald-950/50 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]" />
                      Aman
                    </span>
                  </td>
                  <td class="px-3 py-2.5 text-center whitespace-nowrap w-16">
                    <button
                      type="button"
                      @click="openProductSimulation(item.id)"
                      class="h-7 px-2 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/70 dark:bg-blue-950/40 text-[11px] font-semibold text-[#2563EB] dark:text-blue-400 hover:bg-blue-100 cursor-pointer transition-colors inline-flex items-center gap-1"
                      title="Lihat grafik kurva tren dan peramalan untuk produk ini"
                    >
                      <TrendingUp class="w-3 h-3" />
                      <span>Grafik</span>
                    </button>
                  </td>
                </tr>

                <tr v-if="filteredRestockAlerts.length === 0">
                  <td :colspan="canRequestRestock ? 7 : 6" class="px-4 py-10 text-center text-xs text-slate-400">
                    Tidak ada barang yang memenuhi kriteria filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer Total -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 text-xs text-slate-500">
            <div>
              Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ filteredRestockAlerts.length }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ restockAlerts.length }}</span> barang
              <template v-if="filteredEstimatedBudget > 0">
                &bull; Total Perkiraan Belanja: <span class="font-bold text-[#2563EB] dark:text-blue-400 tabular-nums">{{ formatRupiah(filteredEstimatedBudget) }}</span>
              </template>
            </div>
            <div class="text-[11px] text-slate-400">
              SIPLALING &bull; Sistem Inventaris Prediktif
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- TAB 2: GRAFIK PENJUALAN & PERKIRAAN KEBUTUHAN PER BARANG (EQUAL HEIGHT)   -->
      <!-- ========================================================================= -->
      <div v-show="activeTab === 'simulation'" class="space-y-4">
        <!-- SKU Selector Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs">
          <div class="flex items-center gap-2.5 flex-1 max-w-xl">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 shrink-0">
              Pilih Barang:
            </label>
            <select
              :value="selectedProductId"
              @change="onProductChange($event.target.value)"
              aria-label="Pilih Barang untuk Dilihat Grafiknya"
              class="h-9 w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 px-3 text-xs font-medium text-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 cursor-pointer outline-none"
            >
              <option v-for="p in products" :key="p.id" :value="p.id">
                [{{ p.sku }}] {{ p.name }}
              </option>
            </select>
          </div>

          <button
            type="button"
            @click="activeTab = 'recommendations'"
            class="inline-flex items-center gap-1.5 text-xs text-[#2563EB] hover:underline font-semibold self-start sm:self-auto cursor-pointer"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Kembali ke Daftar Kebutuhan Belanja</span>
          </button>
        </div>

        <!-- Symmetrical Split View: Kiri (Grafik) & Kanan (Panel Kebutuhan & Opsi) - SAMA PANJANG / HEIGHT EQUALIZED -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
          <!-- Kolom Kiri: Card Grafik Penjualan (Tinggi Sejajar) -->
          <div class="lg:col-span-7 flex flex-col">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs h-full flex flex-col justify-between space-y-4">
              <!-- Header Grafik -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <TrendingUp class="w-4 h-4 text-[#2563EB]" />
                    Tren Penjualan &amp; Ramalan Kebutuhan
                  </h3>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Garis biru adalah penjualan lalu, garis hijau adalah perkiraan kebutuhan ke depan.
                  </p>
                </div>

                <!-- Badge Kepastian Prediksi Sederhana -->
                <div class="flex items-center gap-1.5 shrink-0">
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    Akurasi: {{ forecastData?.accuracy_rate ? forecastData.accuracy_rate + '%' : '-' }}
                  </span>
                </div>
              </div>

              <!-- Komponen Grafik (Tinggi Presisi) -->
              <div v-if="forecastData && forecastData.has_sufficient_data" class="flex-1 flex flex-col justify-center my-auto min-h-[320px]">
                <DemandForecastChart
                  :historical-periods="forecastData.historical_periods"
                  :actual-series="forecastData.actual_series"
                  :future-periods="forecastData.future_periods"
                  :future-forecasts="forecastData.future_forecasts"
                  :show-fitted="false"
                  :unit="currentProduct?.unit || 'pcs'"
                  :height="300"
                />
              </div>

              <!-- Empty State jika data belum cukup -->
              <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-8 min-h-[320px]">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-full mb-2">
                  <AlertCircle class="w-7 h-7 text-[#2563EB]" />
                </div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                  Data Penjualan Lalu Belum Cukup
                </h4>
                <p class="text-xs text-slate-400 max-w-xs mt-1">
                  Sistem membutuhkan minimal 2&ndash;3 bulan riwayat transaksi untuk membaca pola laku barang ini.
                </p>
              </div>

              <!-- Panduan Cepat Membaca Grafik (Helicopter View) -->
              <div v-if="forecastData && forecastData.has_sufficient_data" class="p-3 rounded-xl bg-slate-50/90 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <BarChart3 class="w-3.5 h-3.5 text-[#2563EB]" />
                    Sekilas Membaca Grafik
                  </span>
                  <span class="text-[10px] text-slate-400">
                    Alur 3 Zona Waktu
                  </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-0.5 text-xs">
                  <!-- Zona 1: Penjualan Riil -->
                  <div class="flex items-start gap-2 p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#2563EB] shrink-0 mt-0.5" />
                    <div>
                      <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">1. Data Riil (Lalu)</span>
                      <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug block mt-0.5">
                        Garis biru: jumlah fisik barang yang benar-benar laku terjual di bulan lalu.
                      </span>
                    </div>
                  </div>

                  <!-- Zona 2: Batas Waktu -->
                  <div class="flex items-start gap-2 p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0 mt-0.5" />
                    <div>
                      <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">2. Batas Saat Ini</span>
                      <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug block mt-0.5">
                        Garis vertikal tengah: titik pemisah antara data masa lalu dan masa depan.
                      </span>
                    </div>
                  </div>

                  <!-- Zona 3: Ramalan Kebutuhan -->
                  <div class="flex items-start gap-2 p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#10B981] shrink-0 mt-0.5" />
                    <div>
                      <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">3. Proyeksi Kebutuhan</span>
                      <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug block mt-0.5">
                        Garis hijau: perkiraan laku 3 bulan ke depan untuk acuan pesan restock.
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Insight Praktis Bahasa Manusia di Dasar Card Kiri -->
              <div
                v-if="trendInsight"
                class="p-3.5 rounded-xl border text-xs"
                :class="trendInsight.type === 'urgent'
                  ? 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300'
                  : trendInsight.type === 'caution'
                    ? 'bg-amber-50/60 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900 text-amber-900 dark:text-amber-200'
                    : 'bg-blue-50/60 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900 text-slate-700 dark:text-slate-300'"
              >
                <div class="flex items-start gap-2">
                  <component
                    :is="trendInsight.type === 'urgent' ? AlertTriangle : trendInsight.type === 'caution' ? AlertCircle : Info"
                    class="w-4 h-4 shrink-0 mt-0.5"
                    :class="trendInsight.type === 'urgent'
                      ? 'text-rose-600'
                      : trendInsight.type === 'caution'
                        ? 'text-amber-600'
                        : 'text-[#2563EB]'"
                  />
                  <div>
                    <strong class="font-bold block">{{ trendInsight.title }}</strong>
                    <span class="text-[11px] leading-relaxed opacity-90">{{ trendInsight.desc }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Kolom Kanan: Card Tunggal Keputusan Belanja & Opsi (Tinggi Sejajar Persis dengan Kiri) -->
          <div class="lg:col-span-5 flex flex-col">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs h-full flex flex-col justify-between gap-4">
              <!-- Bagian 1: Identitas & Status Barang -->
              <div class="space-y-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                  <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">
                      {{ currentProduct?.sku }}
                    </span>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[220px]" :title="currentProduct?.name">
                      {{ currentProduct?.name }}
                    </h4>
                  </div>
                  <span
                    v-if="forecastData?.restock_recommendation?.status === 'RESTOCK_URGENT'"
                    class="px-2.5 py-1 rounded-full text-xs font-bold border bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-400"
                  >
                    Stok Kritis
                  </span>
                  <span
                    v-else-if="(forecastData?.restock_recommendation?.suggested_quantity || 0) > 0"
                    class="px-2.5 py-1 rounded-full text-xs font-bold border bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-400"
                  >
                    Disarankan Restock
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400"
                  >
                    Stok Aman
                  </span>
                </div>

                <!-- 2 Kotak Kondisi Fisik Besar & Jelas -->
                <div class="grid grid-cols-2 gap-3">
                  <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/70 dark:border-slate-800">
                    <span class="text-[11px] font-medium text-slate-500 block">Stok Fisik di Gudang:</span>
                    <div class="flex items-baseline gap-1 mt-1">
                      <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums tracking-tight">
                        {{ currentProduct?.current_stock || 0 }}
                      </span>
                      <span class="text-xs font-medium text-slate-400">{{ currentProduct?.unit }}</span>
                    </div>
                  </div>

                  <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/70 dark:border-slate-800">
                    <span class="text-[11px] font-medium text-slate-500 block">Batas Cadangan Aman:</span>
                    <div class="flex items-baseline gap-1 mt-1">
                      <span class="text-2xl font-black text-slate-600 dark:text-slate-300 tabular-nums tracking-tight">
                        {{ currentProduct?.minimum_stock || 0 }}
                      </span>
                      <span class="text-xs font-medium text-slate-400">{{ currentProduct?.unit }}</span>
                    </div>
                  </div>
                </div>

                <!-- Widget Baru 1: Estimasi Daya Tahan Stok (Stock Runway) -->
                <div
                  v-if="stockRunway"
                  class="p-3 rounded-xl border text-xs transition-all"
                  :class="stockRunway.status === 'critical'
                    ? 'bg-rose-50/70 border-rose-200 dark:bg-rose-950/30 dark:border-rose-900 text-rose-900 dark:text-rose-200'
                    : stockRunway.status === 'warning'
                      ? 'bg-amber-50/70 border-amber-200 dark:bg-amber-950/30 dark:border-amber-900 text-amber-900 dark:text-amber-200'
                      : 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-900 text-emerald-900 dark:text-emerald-200'"
                >
                  <div class="flex items-center justify-between pb-1">
                    <span class="font-bold flex items-center gap-1.5 text-[11px]">
                      <Clock class="w-3.5 h-3.5" :class="stockRunway.status === 'critical' ? 'text-rose-600' : stockRunway.status === 'warning' ? 'text-amber-600' : 'text-[#10B981]'" />
                      Ketahanan Stok Fisik
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="stockRunway.badgeClass">
                      {{ stockRunway.label }}
                    </span>
                  </div>
                  <p class="text-[11px] leading-relaxed opacity-90 mt-0.5">
                    {{ stockRunway.desc }}
                  </p>
                </div>
              </div>

              <!-- Bagian 2: Hasil Proyeksi Cerdas & Rekomendasi Restock -->
              <div class="space-y-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="grid grid-cols-2 gap-3">
                  <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60">
                    <span class="text-[11px] text-[#2563EB] dark:text-blue-300 block font-semibold">Perkiraan Laku:</span>
                    <div class="flex items-baseline gap-1 mt-1">
                      <span class="text-2xl font-black text-[#2563EB] dark:text-blue-400 tabular-nums tracking-tight">
                        {{ Math.round(forecastData?.future_forecasts?.[0] || 0) }}
                      </span>
                      <span class="text-xs font-semibold text-[#2563EB]">{{ currentProduct?.unit }}</span>
                    </div>
                  </div>

                  <div class="p-3.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/60">
                    <span class="text-[11px] text-amber-800 dark:text-amber-300 block font-semibold">Harus Dipesan (Restock):</span>
                    <div class="flex items-baseline gap-1 mt-1">
                      <span class="text-2xl font-black text-amber-700 dark:text-amber-400 tabular-nums tracking-tight">
                        {{ forecastData?.restock_recommendation?.suggested_quantity || 0 }}
                      </span>
                      <span class="text-xs font-semibold text-amber-700 ml-0.5">{{ currentProduct?.unit }}</span>
                    </div>
                  </div>
                </div>

                <!-- Widget Baru 2: Transparansi Rumus Rekomendasi Restock -->
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 space-y-1.5 text-xs">
                  <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                    <span class="flex items-center gap-1.5">
                      <Calculator class="w-3.5 h-3.5 text-[#2563EB]" />
                      Transparansi Rumus Rekomendasi
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">
                      (Ramalan + Cadangan) &minus; Sisa
                    </span>
                  </div>
                  <div class="grid grid-cols-4 gap-1.5 text-center pt-0.5 text-[11px]">
                    <div class="p-1.5 rounded-lg bg-blue-50/80 dark:bg-blue-950/50 border border-blue-100 dark:border-blue-900/60">
                      <span class="text-[9px] text-slate-400 block font-sans">Ramalan</span>
                      <span class="font-bold text-[#2563EB] font-mono">+{{ Math.round(forecastData?.future_forecasts?.[0] || 0) }}</span>
                    </div>
                    <div class="p-1.5 rounded-lg bg-slate-100/90 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700">
                      <span class="text-[9px] text-slate-400 block font-sans">Cadangan</span>
                      <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">+{{ currentProduct?.minimum_stock || 0 }}</span>
                    </div>
                    <div class="p-1.5 rounded-lg bg-rose-50/80 dark:bg-rose-950/50 border border-rose-100 dark:border-rose-900/60">
                      <span class="text-[9px] text-slate-400 block font-sans">Sisa Stok</span>
                      <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">&minus;{{ currentProduct?.current_stock || 0 }}</span>
                    </div>
                    <div class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60">
                      <span class="text-[9px] text-amber-700 dark:text-amber-400 block font-sans font-bold">Pesanan</span>
                      <span class="font-bold text-amber-700 dark:text-amber-400 font-mono">= {{ forecastData?.restock_recommendation?.suggested_quantity || 0 }}</span>
                    </div>
                  </div>
                </div>

                <!-- Perkiraan Biaya & Tombol Pengajuan -->
                <div class="flex items-center justify-between text-xs pt-0.5">
                  <span class="text-slate-500 font-medium">Perkiraan Total Biaya Pengadaan:</span>
                  <span class="font-bold text-slate-900 dark:text-white tabular-nums text-sm">
                    {{ formatRupiah((forecastData?.restock_recommendation?.suggested_quantity || 0) * (currentProduct?.unit_price || 0)) }}
                  </span>
                </div>

                <button
                  type="button"
                  @click="openRestockForCurrentProduct"
                  class="w-full h-10 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.98]"
                >
                  <Send class="w-3.5 h-3.5" />
                  <span v-if="(forecastData?.restock_recommendation?.suggested_quantity || 0) > 0">
                    Buat Draf Pemesanan ({{ forecastData?.restock_recommendation?.suggested_quantity }} {{ currentProduct?.unit }})
                  </span>
                  <span v-else>
                    Ajukan Restock Manual (Stok Aman)
                  </span>
                </button>
              </div>

              <!-- Bagian 3: Mode Kepekaan Prediksi (Bahasa Manusia Bersahabat) -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <h5 class="text-xs font-bold text-slate-900 dark:text-white">
                      Pilihan Karakter Prediksi
                    </h5>
                    <p class="text-[10px] text-slate-400">
                      Pilih bagaimana sistem memprediksi penjualan barang ini.
                    </p>
                  </div>

                  <button
                    type="button"
                    @click="resetToOptimal"
                    class="h-7 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
                    title="Kembalikan semua parameter ke rekomendasi cerdas sistem"
                  >
                    <RotateCcw class="w-3 h-3 text-slate-500 dark:text-slate-400" />
                    <span>Reset Optimal</span>
                  </button>
                </div>

                <!-- 3 Tombol Skenario Bisnis Mudah -->
                <div class="grid grid-cols-3 gap-1.5">
                  <button
                    type="button"
                    @click="applyPreset('stabil')"
                    class="p-2 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center"
                    :class="activePreset === 'stabil'
                      ? 'border-[#2563EB] bg-blue-50/70 text-[#2563EB] font-bold shadow-2xs dark:bg-blue-950/40 dark:border-blue-500 dark:text-blue-300'
                      : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-blue-300'"
                  >
                    <span class="text-xs flex items-center justify-center gap-1.5 font-semibold">
                      <span class="w-2 h-2 rounded-full bg-[#2563EB] inline-block shrink-0"></span>
                      <span>Normal</span>
                    </span>
                    <span class="text-[9px] text-slate-400 block mt-0.5">Laku stabil</span>
                  </button>

                  <button
                    type="button"
                    @click="applyPreset('optimal')"
                    class="p-2 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center"
                    :class="activePreset === 'optimal'
                      ? 'border-[#10B981] bg-emerald-50/70 text-[#10B981] font-bold shadow-2xs dark:bg-emerald-950/40 dark:border-emerald-500 dark:text-emerald-300'
                      : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-emerald-300'"
                  >
                    <span class="text-xs flex items-center justify-center gap-1.5 font-semibold">
                      <span class="w-2 h-2 rounded-full bg-[#10B981] inline-block shrink-0"></span>
                      <span>Cerdas</span>
                    </span>
                    <span class="text-[9px] text-slate-400 block mt-0.5">Otomatis terbaik</span>
                  </button>

                  <button
                    type="button"
                    @click="applyPreset('responsif')"
                    class="p-2 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center"
                    :class="activePreset === 'responsif'
                      ? 'border-amber-500 bg-amber-50/70 text-amber-700 font-bold shadow-2xs dark:bg-amber-950/40 dark:border-amber-500 dark:text-amber-300'
                      : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-amber-300'"
                  >
                    <span class="text-xs flex items-center justify-center gap-1.5 font-semibold">
                      <span class="w-2 h-2 rounded-full bg-amber-500 inline-block shrink-0"></span>
                      <span>Musiman</span>
                    </span>
                    <span class="text-[9px] text-slate-400 block mt-0.5">Promo / Hari raya</span>
                  </button>
                </div>

                <!-- Toggle Slider Manual Lanjutan (Disembunyikan agar tidak membingungkan orang awam) -->
                <div>
                  <button
                    type="button"
                    @click="showAdvancedSliders = !showAdvancedSliders"
                    class="text-[11px] font-medium text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 flex items-center justify-between w-full py-1 cursor-pointer"
                  >
                    <span class="flex items-center gap-1.5">
                      <SlidersHorizontal class="w-3.5 h-3.5" />
                      <span>Pengaturan Manual Lanjutan (Opsional)</span>
                    </span>
                    <ChevronDown v-if="!showAdvancedSliders" class="w-3.5 h-3.5" />
                    <ChevronUp v-else class="w-3.5 h-3.5" />
                  </button>

                  <!-- Panel Slider yang Rapi (Hanya muncul jika user ingin atur sendiri) -->
                  <div v-show="showAdvancedSliders" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 space-y-3 mt-2">
                    <div class="space-y-1">
                      <div class="flex justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-300 font-medium">Kepekaan Penjualan Terakhir:</span>
                        <span class="font-bold text-[#2563EB] tabular-nums">{{ alphaInput }}</span>
                      </div>
                      <input
                        v-model.number="alphaInput"
                        type="range"
                        min="0.05"
                        max="0.95"
                        step="0.05"
                        class="w-full accent-[#2563EB] cursor-pointer"
                      />
                      <div class="flex justify-between text-[9px] text-slate-400">
                        <span>Hati-hati (0.05)</span>
                        <span>Sangat Cepat (0.95)</span>
                      </div>
                    </div>

                    <div class="space-y-1">
                      <div class="flex justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-300 font-medium">Kepekaan Kenaikan Tren:</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 tabular-nums">{{ betaInput }}</span>
                      </div>
                      <input
                        v-model.number="betaInput"
                        type="range"
                        min="0.05"
                        max="0.95"
                        step="0.05"
                        class="w-full accent-[#4F46E5] cursor-pointer"
                      />
                      <div class="flex justify-between text-[9px] text-slate-400">
                        <span>Tren lambat (0.05)</span>
                        <span>Tren cepat (0.95)</span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                      <button
                        type="button"
                        @click="resetToOptimal"
                        class="flex-1 h-7 rounded-lg border border-slate-200 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all cursor-pointer inline-flex items-center justify-center gap-1.5"
                      >
                        <RotateCcw class="w-3 h-3 text-slate-400" />
                        <span>Reset Semula</span>
                      </button>
                      <button
                        type="button"
                        @click="applyCustomParams"
                        class="flex-1 h-7 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-all cursor-pointer inline-flex items-center justify-center gap-1.5 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
                      >
                        <span>Terapkan Perubahan</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Restock Kolektif (Multi-Item Header-Detail) -->
    <RestockRequestDialog
      v-model:open="isRestockDialogOpen"
      :items="selectedItems"
      @submitted="onRestockSubmitted"
    />
  </AuthenticatedLayout>
</template>
