<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowDownLeft, ArrowUpRight } from 'lucide-vue-next'

export interface TrendItem {
  date: string
  label: string
  inbound_qty: number
  outbound_qty: number
  inbound_count: number
  outbound_count: number
}

const props = withDefaults(
  defineProps<{
    trends: TrendItem[]
    height?: number
  }>(),
  {
    trends: () => [],
    height: 220,
  }
)

// Mode tampilan: Qty barang fisik vs Jumlah transaksi
const activeMetric = ref<'qty' | 'count'>('qty')

const hoveredIndex = ref<number | null>(null)

const chartData = computed(() => {
  return props.trends.map((item) => ({
    ...item,
    inbound: activeMetric.value === 'qty' ? item.inbound_qty : item.inbound_count,
    outbound: activeMetric.value === 'qty' ? item.outbound_qty : item.outbound_count,
  }))
})

const totalInbound = computed(() => {
  return props.trends.reduce(
    (acc, cur) => acc + (activeMetric.value === 'qty' ? cur.inbound_qty : cur.inbound_count),
    0
  )
})

const totalOutbound = computed(() => {
  return props.trends.reduce(
    (acc, cur) => acc + (activeMetric.value === 'qty' ? cur.outbound_qty : cur.outbound_count),
    0
  )
})

const netMovement = computed(() => totalInbound.value - totalOutbound.value)

// Dimensi SVG
const svgWidth = 720
const svgHeight = computed(() => props.height || 220)
const padding = { top: 20, right: 20, bottom: 35, left: 45 }

const innerWidth = computed(() => svgWidth - padding.left - padding.right)
const innerHeight = computed(() => svgHeight.value - padding.top - padding.bottom)

const maxValue = computed(() => {
  const max = Math.max(
    ...chartData.value.map((d) => Math.max(d.inbound, d.outbound)),
    10 // minimal batas atas agar tidak datar
  )
  // Pembulatan ke kelipatan 5 atau 10
  return Math.ceil(max * 1.15)
})

// Skala Y
function getY(val: number) {
  if (maxValue.value === 0) return innerHeight.value
  return innerHeight.value - (val / maxValue.value) * innerHeight.value
}

// Skala X (Bar positions)
const barGroupWidth = computed(() => {
  if (chartData.value.length === 0) return 0
  return innerWidth.value / chartData.value.length
})

const barWidth = computed(() => {
  return Math.max(6, Math.min(14, barGroupWidth.value * 0.32))
})

// Y Grid lines (4 garis horizontal)
const yTicks = computed(() => {
  const steps = 4
  const ticks = []
  for (let i = 0; i <= steps; i++) {
    const val = Math.round((maxValue.value / steps) * i)
    ticks.push({
      val,
      y: getY(val),
    })
  }
  return ticks
})

// Tooltip positioning responsif berbasis persentase
const tooltipPosition = computed(() => {
  if (hoveredIndex.value === null || !chartData.value[hoveredIndex.value]) return null
  const centerSvgX = padding.left + hoveredIndex.value * barGroupWidth.value + barGroupWidth.value / 2
  const pct = (centerSvgX / svgWidth) * 100

  let translateClass = '-translate-x-1/2'
  if (pct < 20) {
    translateClass = '-translate-x-3'
  } else if (pct > 80) {
    translateClass = '-translate-x-[calc(100%-0.75rem)]'
  }

  return {
    left: `${pct}%`,
    top: '8px',
    translateClass,
  }
})

function formatNumber(num: number) {
  return new Intl.NumberFormat('id-ID').format(num)
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header Ringkasan & Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <!-- Mini Legend / Metrik Ringkas -->
      <div class="flex items-center gap-3 sm:gap-4 flex-wrap text-xs">
        <!-- Inbound (Barang Masuk) - Emerald -->
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-md bg-[#10B981] shadow-2xs shrink-0" />
          <span class="text-slate-500 dark:text-slate-400 font-medium">Inbound:</span>
          <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">
            {{ formatNumber(totalInbound) }} {{ activeMetric === 'qty' ? 'unit' : 'trx' }}
          </span>
        </div>

        <!-- Outbound (Barang Keluar) - Amber -->
        <div class="flex items-center gap-2">
          <div class="h-3 w-3 rounded-md bg-amber-600 shadow-2xs shrink-0" />
          <span class="text-slate-500 dark:text-slate-400 font-medium">Outbound:</span>
          <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">
            {{ formatNumber(totalOutbound) }} {{ activeMetric === 'qty' ? 'unit' : 'trx' }}
          </span>
        </div>

        <!-- Net Movement Badge -->
        <div
          v-if="activeMetric === 'qty'"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border transition-colors"
          :class="
            netMovement >= 0
              ? 'bg-emerald-50 dark:bg-emerald-950/40 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'
              : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800/60'
          "
        >
          <ArrowDownLeft v-if="netMovement >= 0" class="w-3 h-3 text-[#10B981]" />
          <ArrowUpRight v-else class="w-3 h-3 text-amber-600 dark:text-amber-400" />
          <span>Net: {{ netMovement >= 0 ? '+' : '' }}{{ formatNumber(netMovement) }} unit</span>
        </div>
      </div>

      <!-- Mode Selector (Qty vs Trx) -->
      <div class="inline-flex items-center p-0.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100/70 dark:bg-slate-900 self-start sm:self-auto">
        <button
          type="button"
          @click="activeMetric = 'qty'"
          class="px-2.5 py-1 text-[11px] font-medium rounded-lg transition-all cursor-pointer"
          :class="
            activeMetric === 'qty'
              ? 'bg-white dark:bg-slate-800 text-[#2563EB] dark:text-blue-400 shadow-2xs font-semibold'
              : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
          "
        >
          Volume Qty
        </button>
        <button
          type="button"
          @click="activeMetric = 'count'"
          class="px-2.5 py-1 text-[11px] font-medium rounded-lg transition-all cursor-pointer"
          :class="
            activeMetric === 'count'
              ? 'bg-white dark:bg-slate-800 text-[#2563EB] dark:text-blue-400 shadow-2xs font-semibold'
              : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
          "
        >
          Jumlah Transaksi
        </button>
      </div>
    </div>

    <!-- Chart Container SVG -->
    <div class="relative w-full overflow-hidden select-none">
      <svg
        :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
        class="w-full h-auto overflow-visible"
      >
        <defs>
          <!-- Gradient Inbound: Emerald (Selaras Halaman Mutasi Stok) -->
          <linearGradient id="inboundGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#10B981" stop-opacity="1" />
            <stop offset="100%" stop-color="#059669" stop-opacity="0.85" />
          </linearGradient>

          <!-- Gradient Outbound: Amber (Selaras Halaman Mutasi Stok) -->
          <linearGradient id="outboundGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#F59E0B" stop-opacity="1" />
            <stop offset="100%" stop-color="#D97706" stop-opacity="0.9" />
          </linearGradient>
        </defs>

        <g :transform="`translate(${padding.left}, ${padding.top})`">
          <!-- Y-Axis Grid Lines & Labels -->
          <g class="grid-lines" opacity="0.6">
            <g v-for="(tick, idx) in yTicks" :key="idx">
              <line
                :x1="0"
                :y1="tick.y"
                :x2="innerWidth"
                :y2="tick.y"
                stroke="currentColor"
                class="text-slate-200 dark:text-slate-800"
                stroke-dasharray="3 3"
              />
              <text
                :x="-8"
                :y="tick.y + 3"
                text-anchor="end"
                class="text-[10px] fill-slate-400 dark:fill-slate-500 font-mono"
              >
                {{ formatNumber(tick.val) }}
              </text>
            </g>
          </g>

          <!-- Grouped Bars per Date -->
          <g
            v-for="(item, idx) in chartData"
            :key="item.date"
            class="group cursor-pointer"
            @mouseenter="hoveredIndex = idx"
            @mouseleave="hoveredIndex = null"
          >
            <!-- Highlight background col on hover -->
            <rect
              :x="idx * barGroupWidth"
              :y="0"
              :width="barGroupWidth"
              :height="innerHeight"
              fill="currentColor"
              class="text-slate-100/50 dark:text-slate-800/40 transition-opacity pointer-events-auto"
              :opacity="hoveredIndex === idx ? 1 : 0"
              rx="6"
            />

            <!-- Inbound Bar (Emerald) -->
            <rect
              :x="idx * barGroupWidth + (barGroupWidth / 2) - barWidth - 1.5"
              :y="getY(item.inbound)"
              :width="barWidth"
              :height="Math.max(0, innerHeight - getY(item.inbound))"
              fill="url(#inboundGrad)"
              rx="3"
              class="transition-all duration-200 group-hover:brightness-110"
            />

            <!-- Outbound Bar (Amber) -->
            <rect
              :x="idx * barGroupWidth + (barGroupWidth / 2) + 1.5"
              :y="getY(item.outbound)"
              :width="barWidth"
              :height="Math.max(0, innerHeight - getY(item.outbound))"
              fill="url(#outboundGrad)"
              rx="3"
              class="transition-all duration-200 group-hover:brightness-110"
            />

            <!-- X-Axis Date Label -->
            <text
              :x="idx * barGroupWidth + barGroupWidth / 2"
              :y="innerHeight + 18"
              text-anchor="middle"
              class="text-[10px] font-medium transition-colors"
              :class="
                hoveredIndex === idx
                  ? 'fill-[#2563EB] font-bold dark:fill-blue-400'
                  : 'fill-slate-400 dark:fill-slate-500'
              "
            >
              {{ item.label }}
            </text>
          </g>
        </g>
      </svg>

      <!-- Tooltip Floating Card (Responsive Percentage Positioned) -->
      <div
        v-if="tooltipPosition && chartData[hoveredIndex!]"
        class="absolute pointer-events-none z-10 px-3 py-2 rounded-xl bg-slate-900/95 dark:bg-slate-800/95 text-white shadow-xl backdrop-blur-md border border-slate-700/60 text-xs transition-all duration-150"
        :class="tooltipPosition.translateClass"
        :style="{
          left: tooltipPosition.left,
          top: tooltipPosition.top,
        }"
      >
        <div class="font-semibold text-slate-200 pb-1 border-b border-slate-700/60 text-[11px] flex items-center justify-between gap-3">
          <span>{{ chartData[hoveredIndex!].label }} ({{ chartData[hoveredIndex!].date }})</span>
        </div>
        <div class="pt-1.5 space-y-1 text-[11px]">
          <!-- Inbound (Emerald) -->
          <div class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
              <span class="w-2 h-2 rounded-full bg-[#10B981]" />
              Inbound:
            </span>
            <span class="font-bold tabular-nums text-white">
              {{ formatNumber(chartData[hoveredIndex!].inbound) }}
              {{ activeMetric === 'qty' ? 'unit' : 'transaksi' }}
            </span>
          </div>

          <!-- Outbound (Amber) -->
          <div class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-1.5 text-amber-400 font-medium">
              <span class="w-2 h-2 rounded-full bg-amber-500" />
              Outbound:
            </span>
            <span class="font-bold tabular-nums text-white">
              {{ formatNumber(chartData[hoveredIndex!].outbound) }}
              {{ activeMetric === 'qty' ? 'unit' : 'transaksi' }}
            </span>
          </div>

          <!-- Difference / Net Hari Ini -->
          <div
            v-if="activeMetric === 'qty'"
            class="flex items-center justify-between gap-3 pt-1 border-t border-slate-700/40 text-[10px] text-slate-300"
          >
            <span>Selisih Hari Ini:</span>
            <span
              :class="
                chartData[hoveredIndex!].inbound - chartData[hoveredIndex!].outbound >= 0
                  ? 'text-emerald-400 font-semibold'
                  : 'text-amber-400 font-semibold'
              "
            >
              {{ chartData[hoveredIndex!].inbound - chartData[hoveredIndex!].outbound >= 0 ? '+' : '' }}
              {{ formatNumber(chartData[hoveredIndex!].inbound - chartData[hoveredIndex!].outbound) }} unit
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
