<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import {
  TrendingUp,
  Cpu,
  AlertCircle,
  CheckCircle2,
  AlertTriangle,
  RotateCcw,
  Sparkles,
  Layers,
  Info,
} from 'lucide-vue-next'
import DemandForecastChart from '@/Components/Inventory/DemandForecastChart.vue'


export interface ProductItem {
  id: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
  current_stock: number
  minimum_stock: number
  category?: { id: string; name: string } | null
}

interface ForecastResponse {
  product: {
    id: string
    sku: string
    name: string
    current_stock: number
    minimum_stock: number
    unit: string
  }
  historical_periods: string[]
  actual_series: number[]
  fitted_series: (number | null)[]
  future_periods: string[]
  future_forecasts: number[]
  optimal_alpha: number
  optimal_beta: number
  mape: number | null
  accuracy_rate: number | null
  rmse: number | null
  mae: number | null
  restock_recommendation: {
    suggested_quantity: number
    status: 'RESTOCK_URGENT' | 'RESTOCK_SUGGESTED' | 'STOCK_ADEQUATE'
    status_label: string
    reorder_point: number
    deficit: number
  }
  has_sufficient_data: boolean
}

const props = defineProps<{
  open: boolean
  product: ProductItem | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const forecastData = ref<ForecastResponse | null>(null)

// Parameter simulasi interaktif
const simAlpha = ref(0.2)
const simBeta = ref(0.1)
const isSimulating = ref(false)
const hoveredIndex = ref<number | null>(null)

async function fetchForecast(alpha?: number, beta?: number) {
  if (!props.product?.id) return

  loading.value = true
  errorMessage.value = null

  try {
    const params = new URLSearchParams()
    if (alpha !== undefined) params.append('alpha', alpha.toString())
    if (beta !== undefined) params.append('beta', beta.toString())
    params.append('horizon', '3')

    const url = `/analytics/simulate/${props.product.id}?${params.toString()}`
    const res = await fetch(url, {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!res.ok) {
      throw new Error(`Gagal memuat kalkulasi: HTTP ${res.status}`)
    }

    const json = await res.json()
    forecastData.value = json.data

    if (json.data) {
      simAlpha.value = json.data.optimal_alpha
      simBeta.value = json.data.optimal_beta
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat memproses peramalan.'
  } finally {
    loading.value = false
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && props.product) {
      isSimulating.value = false
      hoveredIndex.value = null
      fetchForecast()
    } else {
      forecastData.value = null
    }
  }
)

function applySimulation() {
  isSimulating.value = true
  fetchForecast(simAlpha.value, simBeta.value)
}

function resetToOptimal() {
  isSimulating.value = false
  fetchForecast()
}

// === Kalkulasi SVG Chart (Deret Waktu Historis vs Prediksi) ===
const chartPoints = computed(() => {
  if (!forecastData.value || !forecastData.value.has_sufficient_data) return null

  const actuals = forecastData.value.actual_series || []
  const fitted = forecastData.value.fitted_series || []
  const futures = forecastData.value.future_forecasts || []
  const histPeriods = forecastData.value.historical_periods || []
  const futurePeriods = forecastData.value.future_periods || []

  const totalPeriods = histPeriods.length + futurePeriods.length
  if (totalPeriods === 0) return null

  const allValues = [...actuals, ...futures, ...fitted.filter((v): v is number => v !== null)]
  const maxVal = Math.max(...allValues, 10) * 1.15 // 15% headroom
  const minVal = 0

  const width = 560
  const height = 180
  const paddingLeft = 45
  const paddingRight = 25
  const paddingTop = 20
  const paddingBottom = 30

  const graphWidth = width - paddingLeft - paddingRight
  const graphHeight = height - paddingTop - paddingBottom

  const stepX = graphWidth / Math.max(1, totalPeriods - 1)

  // Titik Aktual
  const actualPoints: { x: number; y: number; val: number; period: string }[] = actuals.map((val, idx) => {
    const x = paddingLeft + idx * stepX
    const y = paddingTop + graphHeight - ((val - minVal) / (maxVal - minVal)) * graphHeight
    return { x, y, val, period: histPeriods[idx] || `P-${idx + 1}` }
  })

  // Titik Fitted
  const fittedPoints: { x: number; y: number; val: number; period: string }[] = []
  fitted.forEach((val, idx) => {
    if (val !== null) {
      const x = paddingLeft + idx * stepX
      const y = paddingTop + graphHeight - ((val - minVal) / (maxVal - minVal)) * graphHeight
      fittedPoints.push({ x, y, val, period: histPeriods[idx] || `P-${idx + 1}` })
    }
  })

  // Titik Proyeksi Masa Depan (Dimulai dari titik aktual terakhir)
  const lastActual = actualPoints[actualPoints.length - 1]
  const futurePoints: { x: number; y: number; val: number; period: string }[] = []

  if (lastActual) {
    futurePoints.push(lastActual)
  }

  futures.forEach((val, idx) => {
    const pointIdx = actuals.length + idx
    const x = paddingLeft + pointIdx * stepX
    const y = paddingTop + graphHeight - ((val - minVal) / (maxVal - minVal)) * graphHeight
    futurePoints.push({ x, y, val, period: futurePeriods[idx] || `Fut-${idx + 1}` })
  })

  const makePath = (pts: { x: number; y: number }[]) =>
    pts.reduce((acc, pt, i) => `${acc} ${i === 0 ? 'M' : 'L'} ${pt.x},${pt.y}`, '')

  return {
    width,
    height,
    paddingLeft,
    paddingRight,
    paddingTop,
    paddingBottom,
    maxVal: Math.round(maxVal),
    actualPoints,
    fittedPoints,
    futurePoints,
    actualPath: makePath(actualPoints),
    fittedPath: makePath(fittedPoints),
    futurePath: makePath(futurePoints),
    periods: [...histPeriods, ...futurePeriods],
  }
})

// Evaluasi Tingkat Akurasi
const accuracyBadge = computed(() => {
  if (!forecastData.value?.has_sufficient_data) {
    return {
      rateText: 'Safe Mode',
      label: 'Data Belum Cukup',
      class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
      valueColor: 'text-slate-700 dark:text-slate-300',
    }
  }

  const rate = forecastData.value?.accuracy_rate ?? (
    forecastData.value?.mape !== null ? Math.max(0, Math.round(100 - (forecastData.value?.mape || 0))) : null
  )

  if (rate === null) {
    return {
      rateText: '-',
      label: 'Data Terbatas',
      class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
      valueColor: 'text-slate-700 dark:text-slate-300',
    }
  }

  if (rate >= 80) {
    return {
      rateText: `${rate}%`,
      label: 'Sangat Akurat (Prima)',
      class: 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60',
      valueColor: 'text-[#10B981] dark:text-emerald-400',
    }
  }
  if (rate >= 65) {
    return {
      rateText: `${rate}%`,
      label: 'Akurat / Baik',
      class: 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/60',
      valueColor: 'text-[#2563EB] dark:text-blue-400',
    }
  }
  if (rate >= 40) {
    return {
      rateText: `${rate}%`,
      label: 'Cukup (Layak Pakai)',
      class: 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/60',
      valueColor: 'text-amber-600 dark:text-amber-400',
    }
  }
  return {
    rateText: `${rate}%`,
    label: 'Fluktuasi Tinggi',
    class: 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/60',
    valueColor: 'text-rose-600 dark:text-rose-400',
  }
})

// Status Rekomendasi Restock Badge
const restockBadge = computed(() => {
  const status = forecastData.value?.restock_recommendation?.status
  if (status === 'RESTOCK_URGENT') {
    return {
      class: 'bg-rose-50 text-rose-900 border-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:border-rose-800/60',
      iconClass: 'text-rose-600 dark:text-rose-400',
      icon: AlertTriangle,
    }
  }
  if (status === 'RESTOCK_SUGGESTED') {
    return {
      class: 'bg-blue-50 text-blue-900 border-blue-200 dark:bg-blue-950/50 dark:text-blue-200 dark:border-blue-800/60',
      iconClass: 'text-[#2563EB] dark:text-blue-400',
      icon: TrendingUp,
    }
  }
  return {
    class: 'bg-emerald-50 text-emerald-900 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:border-emerald-800/60',
    iconClass: 'text-[#10B981] dark:text-emerald-400',
    icon: CheckCircle2,
  }
})
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-3xl max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-2xl">
      <!-- Fixed Pinned Header (Close button never obscured) -->
      <DialogHeader class="px-5 sm:px-6 pt-5 pb-3 border-b border-slate-100 dark:border-slate-800 shrink-0">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-8">
          <div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-[#2563EB] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/40">
                {{ product?.sku }}
              </span>
              <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ product?.category?.name || 'Katalog Master' }}
              </span>
            </div>
            <DialogTitle class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100 mt-1">
              {{ product?.name }}
            </DialogTitle>
          </div>

          <div class="text-right">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Stok Fisik Saat Ini</span>
            <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100 tabular-nums">
              {{ product?.current_stock?.toLocaleString() }}
              <span class="text-xs font-normal text-slate-500">{{ product?.unit }}</span>
            </span>
          </div>
        </div>
        <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-normal pr-8">
          Peramalan tren persediaan menggunakan metode <strong class="text-slate-700 dark:text-slate-200 font-semibold">Double Exponential Smoothing (Holt's Linear)</strong> berbasis data transaksi bulanan tertutup.
        </DialogDescription>
      </DialogHeader>

      <!-- Scrollable Body with Sleek Custom Scrollbar -->
      <div class="flex-1 overflow-y-auto px-5 sm:px-6 pt-3.5 pb-4 space-y-3.5 custom-scrollbar min-h-0">
        <!-- Loading State -->
        <div v-if="loading" class="py-12 text-center space-y-3">
        <div class="w-8 h-8 mx-auto border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-xs text-slate-500">Menganalisis deret waktu & parameter pemulusan Holt...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="py-8 text-center space-y-2">
        <AlertCircle class="w-8 h-8 text-rose-500 mx-auto" />
        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ errorMessage }}</p>
        <Button variant="outline" size="sm" @click="fetchForecast()">Coba Lagi</Button>
      </div>

      <!-- Content Utama -->
      <div v-else-if="forecastData" class="space-y-3.5">
        <!-- Kasus A: Data Historis Belum Cukup (< 2 Periode Tutup Buku) -->
        <div v-if="!forecastData.has_sufficient_data" class="rounded-xl border border-blue-200/80 dark:border-blue-900/50 bg-blue-50/40 dark:bg-blue-950/20 p-4 sm:p-5 space-y-3.5">
          <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-[#2563EB] dark:text-blue-400 shrink-0 mt-0.5">
              <Info class="w-5 h-5" />
            </div>
            <div>
              <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Data Historis Belum Mencukupi (&lt; 2 Periode Tutup Buku)
              </h4>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                Produk ini baru memiliki transaksi pada periode berjalan atau belum memiliki riwayat minimal 2 bulan tutup buku. Secara statistik, garis tren deret waktu membutuhkan minimal 2 titik periode untuk menghitung kemiringan (<span class="italic font-medium">slope</span>).
              </p>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                Sistem secara otomatis mengaktifkan <strong class="text-[#2563EB] dark:text-blue-400 font-semibold">Mode Persediaan Pengaman (Safety Stock Buffer)</strong> untuk menjaga ketersediaan barang di gudang tanpa memaksakan proyeksi buatan.
              </p>
            </div>
          </div>

          <!-- Rangkuman Buffer Pengadaan -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-3 border-t border-blue-200/60 dark:border-blue-900/40">
            <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Stok Fisik Saat Ini</span>
              <span class="text-base font-semibold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ product?.current_stock?.toLocaleString() }} {{ product?.unit }}
              </span>
            </div>

            <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Batas Minimum (Buffer)</span>
              <span class="text-base font-semibold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ product?.minimum_stock?.toLocaleString() }} {{ product?.unit }}
              </span>
            </div>

            <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Saran Pengadaan Buffer</span>
              <span class="text-base font-semibold text-[#2563EB] dark:text-blue-400 tabular-nums">
                {{ forecastData.restock_recommendation.suggested_quantity?.toLocaleString() }} {{ product?.unit }}
              </span>
            </div>
          </div>
        </div>

        <!-- Kasus B: Data Historis Mencukupi (Tampilkan Analisis Lengkap) -->
        <template v-else>
          <!-- 1. Grid 4 Kartu Metrik Operasional -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <!-- Bobot Alpha -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/40 dark:bg-slate-950/40">
              <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-medium">Bobot Level (&alpha;)</span>
                <Cpu class="w-4 h-4 text-[#2563EB] dark:text-blue-400" />
              </div>
              <div class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-1 tabular-nums">
                {{ forecastData.optimal_alpha }}
              </div>
              <span class="text-xs text-slate-400 block mt-0.5">Respons baseline aktual</span>
            </div>

            <!-- Bobot Beta -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/40 dark:bg-slate-950/40">
              <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-medium">Bobot Tren (&beta;)</span>
                <TrendingUp class="w-4 h-4 text-[#4F46E5] dark:text-indigo-400" />
              </div>
              <div class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-1 tabular-nums">
                {{ forecastData.optimal_beta }}
              </div>
              <span class="text-xs text-slate-400 block mt-0.5">Adaptasi arah laju tren</span>
            </div>

            <!-- Akurasi Model -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/40 dark:bg-slate-950/40">
              <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-medium">Akurasi Model</span>
                <Sparkles class="w-4 h-4 text-[#10B981] dark:text-emerald-400" />
              </div>
              <div class="text-lg font-bold mt-1 tabular-nums" :class="accuracyBadge.valueColor">
                {{ accuracyBadge.rateText }}
              </div>
              <span class="text-xs text-slate-400 block mt-0.5">
                MAPE: {{ forecastData.mape !== null ? `${forecastData.mape}%` : '-' }}
              </span>
            </div>

            <!-- Rekomendasi Restock -->
            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/40 dark:bg-slate-950/40">
              <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-medium">Saran Restock</span>
                <Layers class="w-4 h-4 text-amber-600 dark:text-amber-400" />
              </div>
              <div class="text-lg font-bold text-[#2563EB] dark:text-blue-400 mt-1 tabular-nums">
                {{ forecastData.restock_recommendation.suggested_quantity?.toLocaleString() }}
                <span class="text-xs font-normal text-slate-500">{{ product?.unit }}</span>
              </div>
              <span class="text-xs text-slate-400 block mt-0.5">Safety Stock: {{ product?.minimum_stock }}</span>
            </div>
          </div>

          <!-- 2. Status Rekomendasi Pengadaan -->
          <div class="rounded-lg border p-3 flex items-start gap-3" :class="restockBadge.class">
            <component :is="restockBadge.icon" class="w-5 h-5 shrink-0 mt-0.5" :class="restockBadge.iconClass" />
            <div class="flex-1 text-xs">
              <div class="font-semibold text-sm">
                Status: {{ forecastData.restock_recommendation.status_label }}
              </div>
              <p class="mt-1 opacity-90 leading-relaxed">
                Perkiraan kebutuhan periode depan adalah <strong class="tabular-nums">{{ Math.round(forecastData.future_forecasts[0] || 0) }} {{ product?.unit }}</strong>.
                Usulan kuantitas restock: <code class="px-1.5 py-0.5 rounded bg-black/5 dark:bg-white/10 font-medium">Max(0, Forecast + Safety Stock - Current Stock)</code> menghasilkan <strong>{{ forecastData.restock_recommendation.suggested_quantity }} {{ product?.unit }}</strong>.
              </p>
            </div>
          </div>

          <!-- 3. Grafik Tren Permintaan & Proyeksi Stok -->
          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-950 shadow-2xs">
            <DemandForecastChart
              v-if="forecastData && forecastData.has_sufficient_data"
              :historical-periods="forecastData.historical_periods"
              :actual-series="forecastData.actual_series"
              :fitted-series="forecastData.fitted_series"
              :future-periods="forecastData.future_periods"
              :future-forecasts="forecastData.future_forecasts"
              :unit="product?.unit || 'unit'"
              :height="220"
            />
          </div>

          <!-- 4. Uji Sensitivitas Parameter Alpha & Beta -->
          <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3.5 bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center justify-between mb-2.5">
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <Cpu class="w-3.5 h-3.5 text-[#4F46E5]" />
                Simulasi Sensitivitas Parameter Pemulusan
              </span>
              <span v-if="isSimulating" class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 px-2 py-0.5 rounded font-medium">
                Simulasi Manual Aktif
              </span>
              <span v-else class="text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2 py-0.5 rounded font-medium">
                Optimasi Otomatis (Grid Search)
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Slider Alpha -->
              <div class="space-y-1">
                <div class="flex justify-between text-xs">
                  <label class="text-slate-600 dark:text-slate-400 font-medium">Parameter &alpha; (Level):</label>
                  <span class="font-semibold text-slate-800 dark:text-slate-200 tabular-nums">{{ simAlpha }}</span>
                </div>
                <input
                  type="range"
                  min="0.05"
                  max="0.95"
                  step="0.05"
                  v-model.number="simAlpha"
                  class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-[#2563EB]"
                />
              </div>

              <!-- Slider Beta -->
              <div class="space-y-1">
                <div class="flex justify-between text-xs">
                  <label class="text-slate-600 dark:text-slate-400 font-medium">Parameter &beta; (Tren):</label>
                  <span class="font-semibold text-slate-800 dark:text-slate-200 tabular-nums">{{ simBeta }}</span>
                </div>
                <input
                  type="range"
                  min="0.05"
                  max="0.95"
                  step="0.05"
                  v-model.number="simBeta"
                  class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-[#4F46E5]"
                />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800">
              <Button
                v-if="isSimulating"
                variant="ghost"
                size="sm"
                class="text-xs h-7 text-slate-600 dark:text-slate-400 hover:text-slate-900"
                @click="resetToOptimal"
              >
                <RotateCcw class="w-3 h-3 mr-1" /> Reset ke Rekomendasi
              </Button>
              <Button
                size="sm"
                class="text-xs h-7 bg-[#2563EB] hover:bg-blue-700 text-white font-medium"
                @click="applySimulation"
              >
                Hitung Simulasi
              </Button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Fixed Footer -->
    <DialogFooter class="p-3.5 sm:px-6 border-t border-slate-100 dark:border-slate-800 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
      <Button variant="outline" size="sm" @click="emit('update:open', false)">
        Tutup
      </Button>
    </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
