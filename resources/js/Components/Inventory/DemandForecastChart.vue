<script setup lang="ts">
import { ref, computed } from 'vue'

export interface DemandForecastChartProps {
  historicalPeriods?: string[]
  actualSeries?: number[]
  fittedSeries?: (number | null)[]
  futurePeriods?: string[]
  futureForecasts?: number[]
  unit?: string
  height?: number
  showFitted?: boolean
  showArea?: boolean
}

const props = withDefaults(defineProps<DemandForecastChartProps>(), {
  historicalPeriods: () => [],
  actualSeries: () => [],
  fittedSeries: () => [],
  futurePeriods: () => [],
  futureForecasts: () => [],
  unit: 'unit',
  height: 240,
  showFitted: true,
  showArea: true,
})

const chartWidth = 760
const chartHeight = computed(() => props.height || 240)
const padding = { top: 28, right: 35, bottom: 42, left: 60 }

interface ChartPoint {
  x: number
  y: number
  val: number
  period: string
  type: 'actual' | 'fitted' | 'future'
}

const hoveredPoint = ref<ChartPoint | null>(null)

// Posisi Tooltip Dinamis (Mencegah terpotong di bagian atas / kanan container)
const tooltipStyle = computed(() => {
  if (!hoveredPoint.value) return {}
  const { x, y } = hoveredPoint.value

  const posXPercent = (x / chartWidth) * 100
  const isNearTop = y < 95

  let translateX = '-50%'
  if (posXPercent > 80) {
    translateX = '-92%'
  } else if (posXPercent < 20) {
    translateX = '-8%'
  }

  const translateY = isNearTop ? '16px' : '-115%'

  return {
    left: `${posXPercent}%`,
    top: `${y}px`,
    transform: `translate(${translateX}, ${translateY})`,
  }
})

const chartData = computed(() => {
  const histPeriods = props.historicalPeriods || []
  const actuals = props.actualSeries || []
  const fitted = props.fittedSeries || []
  const futurePeriods = props.futurePeriods || []
  const futureForecasts = props.futureForecasts || []

  const totalHist = Math.min(histPeriods.length, actuals.length)
  if (totalHist === 0 && futureForecasts.length === 0) return null

  const allPeriods = [
    ...histPeriods.slice(0, totalHist),
    ...futurePeriods.slice(0, futureForecasts.length),
  ]

  const validFitted = fitted.filter((v): v is number => v !== null && v !== undefined)
  const allValues = [...actuals, ...futureForecasts, ...validFitted]

  const maxRaw = Math.max(...allValues, 10)
  const maxVal = Math.ceil(maxRaw * 1.15)
  const minVal = 0

  const usableW = chartWidth - padding.left - padding.right
  const usableH = chartHeight.value - padding.top - padding.bottom

  const getX = (idx: number) => {
    const step = usableW / Math.max(allPeriods.length - 1, 1)
    return padding.left + idx * step
  }

  const getY = (val: number) => {
    if (val === null || val === undefined) return 0
    const ratio = (val - minVal) / (maxVal - minVal || 1)
    return chartHeight.value - padding.bottom - ratio * usableH
  }

  // Titik Historis (Aktual)
  const actualPoints: ChartPoint[] = actuals.slice(0, totalHist).map((val, i) => ({
    x: getX(i),
    y: getY(val),
    val,
    period: histPeriods[i] || `P-${i + 1}`,
    type: 'actual',
  }))

  // Titik Fitted Model
  const fittedPoints: ChartPoint[] = []
  fitted.slice(0, totalHist).forEach((val, i) => {
    if (val !== null && val !== undefined) {
      fittedPoints.push({
        x: getX(i),
        y: getY(val),
        val,
        period: histPeriods[i] || `P-${i + 1}`,
        type: 'fitted',
      })
    }
  })

  // Titik Proyeksi Masa Depan (Dimulai dari titik historis terakhir agar kurva menyambung)
  const lastActual = actualPoints[actualPoints.length - 1]
  const futurePoints: ChartPoint[] = futureForecasts.map((val, i) => ({
    x: getX(totalHist + i),
    y: getY(val),
    val,
    period: futurePeriods[i] || `Fut-${i + 1}`,
    type: 'future',
  }))

  const projLinePoints = lastActual ? [lastActual, ...futurePoints] : futurePoints

  const toLinearPath = (pts: { x: number; y: number }[]) => {
    if (!pts.length) return ''
    return pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ')
  }

  // Smooth Bezier Curve (Catmull-Rom spline conversion)
  const toSmoothPath = (pts: { x: number; y: number }[]) => {
    if (pts.length < 2) return toLinearPath(pts)
    let d = `M ${pts[0].x.toFixed(1)} ${pts[0].y.toFixed(1)}`
    for (let i = 0; i < pts.length - 1; i++) {
      const p0 = pts[i - 1] || pts[i]
      const p1 = pts[i]
      const p2 = pts[i + 1]
      const p3 = pts[i + 2] || p2
      const cp1x = (p1.x + (p2.x - p0.x) / 6).toFixed(1)
      const cp1y = (p1.y + (p2.y - p0.y) / 6).toFixed(1)
      const cp2x = (p2.x - (p3.x - p1.x) / 6).toFixed(1)
      const cp2y = (p2.y - (p3.y - p1.y) / 6).toFixed(1)
      d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x.toFixed(1)} ${p2.y.toFixed(1)}`
    }
    return d
  }

  // Area Path di bawah Kurva Historis
  const baselineY = chartHeight.value - padding.bottom
  const actualAreaPath = (() => {
    if (actualPoints.length < 2) return ''
    const linePath = toSmoothPath(actualPoints)
    const last = actualPoints[actualPoints.length - 1]
    const first = actualPoints[0]
    return `${linePath} L ${last.x.toFixed(1)} ${baselineY} L ${first.x.toFixed(1)} ${baselineY} Z`
  })()

  // Pembatas Garis Antara Historis & Proyeksi
  const dividerX = futurePoints.length && lastActual
    ? (lastActual.x + futurePoints[0].x) / 2
    : null

  return {
    periods: allPeriods,
    maxVal,
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
</script>

<template>
  <div class="space-y-3">
    <!-- Header Legenda Penjelas Kurva (Mode Terang & Gelap Selaras) -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs border-b border-slate-100 dark:border-slate-800/80 pb-2">
      <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200">
        <span class="inline-block w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-500 animate-pulse"></span>
        Visualisasi Kebutuhan Barang
      </div>

      <div class="flex flex-wrap items-center gap-4 text-xs">
        <!-- Legenda Historis -->
        <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-medium">
          <span class="w-3.5 h-1 rounded-full bg-blue-600 dark:bg-blue-500"></span>
          <span>Data Historis (Aktual)</span>
        </div>

        <!-- Legenda Fitted Model (Opsional) -->
        <div v-if="props.showFitted && chartData?.fittedPoints.length" class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
          <span class="w-3.5 h-0.5 rounded-full bg-indigo-500 dark:bg-indigo-400 stroke-dash"></span>
          <span>Taksiran Model (Fitted)</span>
        </div>

        <!-- Legenda Proyeksi DES -->
        <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-semibold">
          <span class="w-4 h-0.5 border-t-2 border-dashed border-emerald-600 dark:border-emerald-400"></span>
          <span>Proyeksi Peramalan</span>
        </div>
      </div>
    </div>

    <!-- SVG Container Grafik -->
    <div v-if="chartData" class="relative w-full overflow-x-auto custom-scrollbar">
      <svg
        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
        class="w-full min-w-[580px] select-none"
        :style="{ height: `${chartHeight}px` }"
      >
        <defs>
          <!-- Gradient Area Data Historis -->
          <linearGradient id="demandChartAreaGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2563EB" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#2563EB" stop-opacity="0.02" />
          </linearGradient>
          
          <!-- Gradient Area Proyeksi Masa Depan -->
          <linearGradient id="demandChartProjAreaGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#10B981" stop-opacity="0.2" />
            <stop offset="100%" stop-color="#10B981" stop-opacity="0.0" />
          </linearGradient>
        </defs>

        <!-- Dynamic Grid Lines (Mode Terang & Gelap) -->
        <g class="stroke-slate-200 dark:stroke-slate-800/80" stroke-width="1">
          <!-- Baseline Bottom -->
          <line
            :x1="padding.left"
            :y1="chartHeight - padding.bottom"
            :x2="chartWidth - padding.right"
            :y2="chartHeight - padding.bottom"
          />
          <!-- Midline Grid -->
          <line
            :x1="padding.left"
            :y1="(padding.top + chartHeight - padding.bottom) / 2"
            :x2="chartWidth - padding.right"
            :y2="(padding.top + chartHeight - padding.bottom) / 2"
            stroke-dasharray="3 3"
          />
          <!-- Topline Grid -->
          <line
            :x1="padding.left"
            :y1="padding.top"
            :x2="chartWidth - padding.right"
            :y2="padding.top"
            stroke-dasharray="3 3"
          />
        </g>

        <!-- Garis Vertikal Pemisah Zona Historis vs Proyeksi -->
        <g v-if="chartData.dividerX">
          <line
            :x1="chartData.dividerX"
            :y1="padding.top - 8"
            :x2="chartData.dividerX"
            :y2="chartHeight - padding.bottom"
            class="stroke-slate-400 dark:stroke-slate-600"
            stroke-width="1.5"
            stroke-dasharray="4 3"
          />
          <!-- Tag Penanda Batas Zona -->
          <rect
            :x="chartData.dividerX - 44"
            :y="padding.top - 20"
            width="88"
            height="16"
            rx="4"
            class="fill-slate-100 dark:fill-slate-800 stroke-slate-300 dark:stroke-slate-700"
            stroke-width="1"
          />
          <text
            :x="chartData.dividerX"
            :y="padding.top - 8"
            text-anchor="middle"
            class="text-[9px] font-bold fill-slate-600 dark:fill-slate-300 tracking-wider uppercase"
          >
            Batas Proyeksi &rarr;
          </text>
        </g>

        <!-- Y-Axis Values -->
        <text
          :x="padding.left - 10"
          :y="padding.top + 4"
          text-anchor="end"
          class="text-[10px] font-semibold fill-slate-500 dark:fill-slate-400 tabular-nums"
        >
          {{ chartData.maxVal.toLocaleString() }}
        </text>
        <text
          :x="padding.left - 10"
          :y="(padding.top + chartHeight - padding.bottom) / 2 + 4"
          text-anchor="end"
          class="text-[10px] fill-slate-400 dark:fill-slate-500 tabular-nums"
        >
          {{ Math.round(chartData.maxVal / 2).toLocaleString() }}
        </text>
        <text
          :x="padding.left - 10"
          :y="chartHeight - padding.bottom"
          text-anchor="end"
          class="text-[10px] fill-slate-400 dark:fill-slate-500 tabular-nums"
        >
          0
        </text>

        <!-- Area Fill Historis (Solid Gradient Blue) -->
        <path
          v-if="props.showArea && chartData.actualAreaPath"
          :d="chartData.actualAreaPath"
          fill="url(#demandChartAreaGrad)"
        />

        <!-- Garis Fitted Model (Indigo Dashed) -->
        <path
          v-if="props.showFitted && chartData.fittedPath"
          :d="chartData.fittedPath"
          fill="none"
          class="stroke-indigo-400/80 dark:stroke-indigo-400/70"
          stroke-width="1.75"
          stroke-dasharray="4 4"
        />

        <!-- Garis Proyeksi Peramalan (Emerald Dashed - Membedakan dengan Jelas) -->
        <path
          v-if="chartData.projPath"
          :d="chartData.projPath"
          fill="none"
          class="stroke-emerald-600 dark:stroke-emerald-400"
          stroke-width="2.5"
          stroke-dasharray="6 4"
          stroke-linecap="round"
        />

        <!-- Garis Data Historis Utama (Royal Blue Solid - Membedakan dengan Jelas) -->
        <path
          v-if="chartData.actualPath"
          :d="chartData.actualPath"
          fill="none"
          class="stroke-blue-600 dark:stroke-blue-500"
          stroke-width="2.75"
          stroke-linecap="round"
        />

        <!-- Node Circle: Data Historis (Blue Dots) -->
        <g v-for="(p, i) in chartData.actualPoints" :key="'act-pt-' + i">
          <!-- Visible Point -->
          <circle
            :cx="p.x"
            :cy="p.y"
            :r="hoveredPoint === p ? 6.5 : 4.5"
            class="fill-blue-600 dark:fill-blue-400 stroke-white dark:stroke-slate-900 transition-all duration-150 pointer-events-none"
            stroke-width="2"
          />
          <!-- Invisible Hit Area (Stops flickering & jittering) -->
          <circle
            :cx="p.x"
            :cy="p.y"
            r="14"
            fill="transparent"
            class="cursor-pointer"
            @mouseenter="hoveredPoint = p"
            @mouseleave="hoveredPoint = null"
          />
        </g>

        <!-- Node Circle: Data Proyeksi (Emerald Dots with Outer Ring) -->
        <g v-for="(p, i) in chartData.futurePoints" :key="'fut-pt-' + i">
          <!-- Pulse Halo Effect -->
          <circle
            :cx="p.x"
            :cy="p.y"
            :r="hoveredPoint === p ? 9 : 7"
            class="fill-emerald-500/20 dark:fill-emerald-400/20 stroke-emerald-500/40 dark:stroke-emerald-400/40 transition-all duration-150 pointer-events-none"
          />
          <!-- Visible Point -->
          <circle
            :cx="p.x"
            :cy="p.y"
            :r="hoveredPoint === p ? 6.5 : 4.5"
            class="fill-emerald-600 dark:fill-emerald-400 stroke-white dark:stroke-slate-900 transition-all duration-150 pointer-events-none"
            stroke-width="2"
          />
          <!-- Invisible Hit Area (Stops flickering & jittering) -->
          <circle
            :cx="p.x"
            :cy="p.y"
            r="14"
            fill="transparent"
            class="cursor-pointer"
            @mouseenter="hoveredPoint = p"
            @mouseleave="hoveredPoint = null"
          />
        </g>

        <!-- X-Axis Labels (Periode Waktu) -->
        <g v-for="(period, idx) in chartData.periods" :key="'lbl-' + idx">
          <text
            :x="chartData.getX(idx)"
            :y="chartHeight - 12"
            text-anchor="middle"
            class="text-[10px] tabular-nums font-medium transition-colors"
            :class="
              idx >= chartData.actualPoints.length
                ? 'fill-emerald-700 dark:fill-emerald-400 font-bold'
                : 'fill-slate-600 dark:fill-slate-400'
            "
          >
            {{ period }}
          </text>
        </g>
      </svg>

      <!-- Tooltip Interaktif Hover Point (Light & Dark mode ready) -->
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
          class="absolute pointer-events-none rounded-xl bg-white/95 dark:bg-slate-900/95 text-slate-800 dark:text-slate-100 text-xs font-medium px-3 py-2 shadow-xl border border-slate-200/90 dark:border-slate-800 whitespace-nowrap z-20 backdrop-blur-md transition-colors"
          :style="tooltipStyle"
        >
          <div class="flex items-center gap-1.5">
            <span
              class="inline-block w-2 h-2 rounded-full"
              :class="hoveredPoint.type === 'future' ? 'bg-emerald-600 dark:bg-emerald-400' : 'bg-blue-600 dark:bg-blue-400'"
            ></span>
            <span class="font-bold text-slate-900 dark:text-white">{{ hoveredPoint.period }}</span>
          </div>
          <div class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-300">
            Kebutuhan: <strong class="text-slate-900 dark:text-white font-bold tabular-nums">{{ Math.round(hoveredPoint.val).toLocaleString() }}</strong> {{ props.unit }}
          </div>
          <span
            class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-wider border"
            :class="
              hoveredPoint.type === 'future'
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300 dark:border-emerald-800/60'
                : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/80 dark:text-blue-300 dark:border-blue-800/60'
            "
          >
            {{ hoveredPoint.type === 'future' ? 'Proyeksi Peramalan' : 'Riwayat Historis' }}
          </span>
        </div>
      </Transition>
    </div>
  </div>
</template>
