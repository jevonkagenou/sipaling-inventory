<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
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
} from 'lucide-vue-next'

const props = defineProps({
  products: {
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
  if (!props.forecastData) return
  alphaInput.value = props.forecastData.optimal_alpha
  betaInput.value = props.forecastData.optimal_beta
  router.get(
    route('analytics.index'),
    {
      product_id: currentProduct.value?.id,
    },
    { preserveState: true, preserveScroll: true }
  )
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

const hoveredPoint = ref(null)
</script>

<template>
  <Head title="Analitik Peramalan DES - SIPALING" />

  <AuthenticatedLayout>
    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Header Banner & Keterangan Modul -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <div class="flex items-center gap-2.5">
            <div class="h-9 w-9 rounded-xl bg-[#2563EB]/10 text-[#2563EB] dark:text-blue-400 flex items-center justify-center font-bold">
              <TrendingUp class="w-5 h-5" />
            </div>
            <div>
              <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Mesin Peramalan Double Exponential Smoothing
              </h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Proyeksi tren permintaan retail dengan metode Holt's Linear berdasar data penutupan transaksi bulanan.
              </p>
            </div>
          </div>
        </div>

        <!-- Mode Referensi Badge -->
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-[#10B981] border border-emerald-200 dark:border-emerald-800/60 shadow-2xs">
            <span class="h-2 w-2 rounded-full bg-[#10B981] animate-pulse" />
            Akses Referensi Tim Aktif
          </span>
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
            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950 px-3.5 py-2.5 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#2563EB] cursor-pointer"
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

        <!-- Kolom Tengah & Kanan: Pengatur Parameter Alpha & Beta -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <Sliders class="w-4 h-4 text-[#2563EB]" />
                Simulasi Bobot Smoothing (Holt's Linear)
              </h2>
              <p class="text-[11px] text-slate-400 mt-0.5">
                Ubah parameter level (&alpha;) dan tren (&beta;) untuk melihat dampaknya terhadap akurasi.
              </p>
            </div>
            <button
              type="button"
              @click="resetToOptimal"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-[#2563EB] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/60 dark:border-blue-800/40 transition-colors cursor-pointer self-start sm:self-auto"
            >
              <Sparkles class="w-3.5 h-3.5" />
              Gunakan Optimal Grid (&alpha;={{ forecastData?.optimal_alpha }}, &beta;={{ forecastData?.optimal_beta }})
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
            <!-- Alpha Slider -->
            <div class="space-y-2">
              <div class="flex justify-between items-center text-xs">
                <label class="font-semibold text-slate-700 dark:text-slate-300">
                  Bobot Level (&alpha;): <span class="text-[#2563EB] tabular-nums font-bold">{{ alphaInput }}</span>
                </label>
                <span class="text-[10px] text-slate-400">Respons baseline</span>
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
                  Bobot Tren (&beta;): <span class="text-indigo-600 dark:text-indigo-400 tabular-nums font-bold">{{ betaInput }}</span>
                </label>
                <span class="text-[10px] text-slate-400">Adaptasi tren</span>
              </div>
              <input
                v-model.number="betaInput"
                type="range"
                min="0.05"
                max="0.95"
                step="0.05"
                class="w-full accent-indigo-600 cursor-pointer"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="button"
              @click="applyCustomParams"
              class="px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              Kalkulasi Ulang Simulasi
            </button>
          </div>
        </div>
      </div>

      <!-- Section: Kartu Hasil Akurasi & Rekomendasi Restock -->
      <div v-if="forecastData" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Akurasi Model -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-2xs">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Akurasi Model</span>
            <CheckCircle2 class="w-4 h-4 text-[#10B981]" />
          </div>
          <div class="mt-2 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tabular-nums text-[#10B981]">
              {{ forecastData.accuracy_rate ? forecastData.accuracy_rate + '%' : 'N/A' }}
            </span>
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            100% &minus; MAPE (Tingkat Ketepatan)
          </p>
        </div>

        <!-- 2. Galat MAPE & RMSE -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-2xs">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Metrik Galat (MAPE)</span>
            <BarChart3 class="w-4 h-4 text-slate-400" />
          </div>
          <div class="mt-2 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tabular-nums text-slate-800 dark:text-slate-100">
              {{ forecastData.mape ? forecastData.mape + '%' : 'N/A' }}
            </span>
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            RMSE: {{ forecastData.rmse ? Math.round(forecastData.rmse) : '-' }} unit
          </p>
        </div>

        <!-- 3. Proyeksi Periode Depan -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-2xs">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Proyeksi Periode Depan</span>
            <TrendingUp class="w-4 h-4 text-[#2563EB]" />
          </div>
          <div class="mt-2 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tabular-nums text-[#2563EB]">
              {{ Math.round(forecastData.future_forecasts?.[0] || 0) }}
            </span>
            <span class="text-xs text-slate-400">unit</span>
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            Periode: {{ forecastData.future_periods?.[0] || '-' }}
          </p>
        </div>

        <!-- 4. Saran Restock Pengadaan -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-2xs">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Saran Restock</span>
            <AlertTriangle
              :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'text-amber-500' : 'text-[#10B981]'"
              class="w-4 h-4"
            />
          </div>
          <div class="mt-2 flex items-baseline gap-1.5">
            <span
              :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'text-amber-600 dark:text-amber-400' : 'text-[#10B981]'"
              class="text-2xl font-bold tabular-nums"
            >
              {{ forecastData.restock_recommendation?.suggested_quantity || 0 }}
            </span>
            <span class="text-xs text-slate-400">unit</span>
          </div>
          <p class="mt-1 text-[11px] font-medium" :class="forecastData.restock_recommendation?.status === 'RESTOCK_URGENT' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'">
            {{ forecastData.restock_recommendation?.status_label }}
          </p>
        </div>
      </div>

      <!-- Section: Visualisasi Kurva Tren DES -->
      <div v-if="chartData" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Layers class="w-4 h-4 text-[#2563EB]" />
              Kurva Permintaan Aktual vs Taksiran Model DES &amp; Proyeksi Masa Depan
            </h2>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Data transaksi historis bulanan tertutup dan estimasi 3 periode ke depan.
            </p>
          </div>

          <!-- Legenda Garis -->
          <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-[#2563EB]" />
              <span class="text-slate-600 dark:text-slate-400">Aktual</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-1 bg-indigo-400 rounded-full" />
              <span class="text-slate-600 dark:text-slate-400">Fitted Model</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-1 border-t-2 border-dashed border-[#10B981]" />
              <span class="text-[#10B981] font-semibold">Proyeksi</span>
            </div>
          </div>
        </div>

        <!-- SVG Container (relative buat posisi tooltip mengambang) -->
        <div class="relative w-full overflow-x-auto custom-scrollbar">
          <svg
            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
            class="w-full min-w-[640px] h-[240px] select-none"
          >
            <defs>
              <linearGradient id="actualAreaGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#2563EB" stop-opacity="0.22" />
                <stop offset="100%" stop-color="#2563EB" stop-opacity="0" />
              </linearGradient>
            </defs>

            <!-- Grid Lines -->
            <g class="stroke-slate-100 dark:stroke-slate-800/80" stroke-width="1">
              <line
                :x1="padding.left"
                :y1="chartHeight - padding.bottom"
                :x2="chartWidth - padding.right"
                :y2="chartHeight - padding.bottom"
              />
              <line
                :x1="padding.left"
                :y1="padding.top"
                :x2="chartWidth - padding.right"
                :y2="padding.top"
                stroke-dasharray="3 3"
              />
              <line
                :x1="padding.left"
                :y1="(padding.top + chartHeight - padding.bottom) / 2"
                :x2="chartWidth - padding.right"
                :y2="(padding.top + chartHeight - padding.bottom) / 2"
                stroke-dasharray="3 3"
              />
            </g>

            <!-- Garis Pembatas Histori vs Proyeksi -->
            <g v-if="chartData.dividerX">
              <line
                :x1="chartData.dividerX"
                :y1="padding.top"
                :x2="chartData.dividerX"
                :y2="chartHeight - padding.bottom"
                stroke="#94A3B8"
                stroke-width="1"
                stroke-dasharray="2 3"
              />
              <text
                :x="chartData.dividerX"
                :y="padding.top - 6"
                text-anchor="middle"
                class="text-[9px] fill-slate-400 font-medium uppercase tracking-wider"
              >
                Proyeksi &rarr;
              </text>
            </g>

            <!-- Y-Axis Labels -->
            <text
              :x="padding.left - 10"
              :y="padding.top + 4"
              text-anchor="end"
              class="text-[10px] fill-slate-400"
            >
              {{ chartData.maxVal }}
            </text>
            <text
              :x="padding.left - 10"
              :y="chartHeight - padding.bottom"
              text-anchor="end"
              class="text-[10px] fill-slate-400"
            >
              0
            </text>

            <!-- Area Fill di bawah garis Aktual -->
            <path
              v-if="chartData.actualAreaPath"
              :d="chartData.actualAreaPath"
              fill="url(#actualAreaGradient)"
              stroke="none"
            />

            <!-- Fitted Model Path -->
            <path
              v-if="chartData.fittedPath"
              :d="chartData.fittedPath"
              fill="none"
              stroke="#818CF8"
              stroke-width="2"
              stroke-dasharray="4 4"
            />

            <!-- Proyeksi Path -->
            <path
              v-if="chartData.projPath"
              :d="chartData.projPath"
              fill="none"
              stroke="#10B981"
              stroke-width="2.5"
              stroke-dasharray="5 3"
            />

            <!-- Actual Path (smooth curve) -->
            <path
              v-if="chartData.actualPath"
              :d="chartData.actualPath"
              fill="none"
              stroke="#2563EB"
              stroke-width="2.5"
              stroke-linecap="round"
            />

            <!-- Points: Actual -->
            <circle
              v-for="(p, i) in chartData.actualPoints"
              :key="'act-' + i"
              :cx="p.x"
              :cy="p.y"
              r="4"
              fill="#2563EB"
              class="transition-transform hover:scale-150 cursor-pointer"
              @mouseenter="hoveredPoint = p"
              @mouseleave="hoveredPoint = null"
            />

            <!-- Points: Future -->
            <circle
              v-for="(p, i) in chartData.futurePoints"
              :key="'fut-' + i"
              :cx="p.x"
              :cy="p.y"
              r="4.5"
              fill="#10B981"
              class="transition-transform hover:scale-150 cursor-pointer"
              @mouseenter="hoveredPoint = p"
              @mouseleave="hoveredPoint = null"
            />

            <!-- X-Axis Labels -->
            <text
              v-for="(period, idx) in chartData.periods"
              :key="'lbl-' + idx"
              :x="chartData.getX(idx)"
              :y="chartHeight - 12"
              text-anchor="middle"
              :class="idx >= chartData.actualPoints.length ? 'fill-[#10B981] font-bold' : 'fill-slate-400'"
              class="text-[10px]"
            >
              {{ period }}
            </text>
          </svg>

          <!-- Tooltip Mengambang -->
          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="hoveredPoint"
              class="absolute pointer-events-none rounded-lg bg-slate-900 dark:bg-slate-700 text-white text-[11px] font-medium px-2.5 py-1.5 shadow-lg whitespace-nowrap z-10"
              :style="{
                left: (hoveredPoint.x / chartWidth) * 100 + '%',
                top: hoveredPoint.y + 'px',
                transform: 'translate(-50%, -130%)',
              }"
            >
              <div class="flex items-center gap-1.5">
                <span class="inline-block w-1.5 h-1.5 rounded-full" :class="hoveredPoint.type === 'future' ? 'bg-[#10B981]' : 'bg-[#2563EB]'" />
                <span>{{ hoveredPoint.period }}: <strong class="tabular-nums">{{ Math.round(hoveredPoint.val) }} unit</strong></span>
              </div>
              <span class="text-[9px] text-slate-300 block">
                {{ hoveredPoint.type === 'future' ? 'Hasil Proyeksi DES' : 'Riwayat Aktual' }}
              </span>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Section: Matriks Peringatan Restock Seluruh Master Produk -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Boxes class="w-4 h-4 text-[#2563EB]" />
              Ringkasan Rekomendasi Pengadaan Seluruh Katalog Master
            </h2>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Diurutkan berdasarkan prioritas urgensi pengadaan (safety stock deficit).
            </p>
          </div>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
            {{ restockAlerts.length }} Produk
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-3 px-4 text-left">SKU</th>
                <th class="py-3 px-4 text-left">Nama Barang</th>
                <th class="py-3 px-4 text-right">Stok Aktual</th>
                <th class="py-3 px-4 text-right">Safety Stock</th>
                <th class="py-3 px-4 text-right">Prediksi Kebutuhan</th>
                <th class="py-3 px-4 text-right">Saran Restock</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="item in restockAlerts"
                :key="item.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                :class="item.id === selectedProductId ? 'bg-blue-50/40 dark:bg-blue-950/20' : ''"
              >
                <td class="py-3 px-4 font-mono font-medium text-slate-700 dark:text-slate-300">
                  {{ item.sku }}
                </td>
                <td class="py-3 px-4 font-medium text-slate-900 dark:text-white">
                  {{ item.name }}
                </td>
                <td class="py-3 px-4 text-right tabular-nums text-slate-700 dark:text-slate-300">
                  {{ item.current_stock }} {{ item.unit }}
                </td>
                <td class="py-3 px-4 text-right tabular-nums text-slate-500">
                  {{ item.minimum_stock }} {{ item.unit }}
                </td>
                <td class="py-3 px-4 text-right tabular-nums font-semibold text-[#2563EB] dark:text-blue-400">
                  {{ item.next_forecast }} {{ item.unit }}
                </td>
                <td class="py-3 px-4 text-right tabular-nums font-bold" :class="item.suggested_quantity > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-[#10B981]'">
                  {{ item.suggested_quantity }} {{ item.unit }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    :class="item.status === 'RESTOCK_URGENT'
                      ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60'
                      : 'bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] border-emerald-200 dark:border-emerald-800/60'"
                    class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium border"
                  >
                    {{ item.status_label }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    type="button"
                    @click="onProductChange(item.id)"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-medium text-[#2563EB] dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/60 cursor-pointer transition-colors"
                  >
                    Simulasi
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>