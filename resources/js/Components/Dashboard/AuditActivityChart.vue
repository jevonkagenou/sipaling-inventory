<script setup lang="ts">
import { computed, ref } from 'vue'

export interface AuditActivityTrend {
  date: string
  label: string
  created: number
  updated: number
}

const props = defineProps<{
  trends: AuditActivityTrend[]
}>()

const hoveredIndex = ref<number | null>(null)
const width = 720
const height = 220
const padding = { top: 20, right: 20, bottom: 35, left: 45 }
const chartWidth = width - padding.left - padding.right
const chartHeight = height - padding.top - padding.bottom

const maxValue = computed(() => {
  const largestValue = Math.max(
    ...props.trends.map((item) => Math.max(item.created, item.updated)),
    1
  )
  return Math.ceil(largestValue * 1.15)
})
const groupWidth = computed(() => props.trends.length ? chartWidth / props.trends.length : 0)
const barWidth = computed(() => Math.max(6, Math.min(14, groupWidth.value * 0.32)))
const totals = computed(() => props.trends.reduce(
  (result, item) => ({
    created: result.created + item.created,
    updated: result.updated + item.updated,
  }),
  { created: 0, updated: 0 }
))
const ticks = computed(() => {
  return Array.from({ length: 5 }, (_, index) => {
    const value = Math.round((maxValue.value / 4) * index)
    return {
      value,
      y: chartHeight - (value / maxValue.value) * chartHeight,
    }
  })
})

function barHeight(value: number) {
  return Math.max(0, (value / maxValue.value) * chartHeight)
}

function formatNumber(value: number) {
  return new Intl.NumberFormat('id-ID').format(value)
}

const tooltipPosition = computed(() => {
  if (hoveredIndex.value === null || !props.trends[hoveredIndex.value]) return null

  const centerX = padding.left + hoveredIndex.value * groupWidth.value + groupWidth.value / 2
  const percentage = (centerX / width) * 100
  let translateClass = '-translate-x-1/2'
  if (percentage < 20) {
    translateClass = '-translate-x-3'
  } else if (percentage > 80) {
    translateClass = '-translate-x-[calc(100%-0.75rem)]'
  }

  return {
    left: `${percentage}%`,
    top: '8px',
    translateClass,
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3 sm:gap-4 flex-wrap text-xs">
        <div class="inline-flex items-center gap-2">
          <span class="h-3 w-3 rounded-md bg-[#10B981] shadow-2xs shrink-0" />
          <span class="text-slate-500 dark:text-slate-400 font-medium">Create:</span>
          <strong class="text-slate-800 dark:text-slate-200 tabular-nums">{{ formatNumber(totals.created) }} rekaman audit</strong>
        </div>
        <div class="inline-flex items-center gap-2">
          <span class="h-3 w-3 rounded-md bg-[#2563EB] shadow-2xs shrink-0" />
          <span class="text-slate-500 dark:text-slate-400 font-medium">Update:</span>
          <strong class="text-slate-800 dark:text-slate-200 tabular-nums">{{ formatNumber(totals.updated) }} rekaman audit</strong>
        </div>
      </div>
    </div>

    <div class="relative w-full overflow-hidden select-none">
      <svg
        role="img"
        aria-label="Perbandingan aktivitas data dibuat dan diperbarui dalam 14 hari terakhir"
        :viewBox="`0 0 ${width} ${height}`"
        class="w-full h-auto overflow-visible"
      >
        <defs>
          <linearGradient id="auditCreatedGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#10B981" stop-opacity="1" />
            <stop offset="100%" stop-color="#059669" stop-opacity="0.85" />
          </linearGradient>
          <linearGradient id="auditUpdatedGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2563EB" stop-opacity="1" />
            <stop offset="100%" stop-color="#1D4ED8" stop-opacity="0.85" />
          </linearGradient>
        </defs>

        <g :transform="`translate(${padding.left}, ${padding.top})`">
          <g opacity="0.6">
            <g v-for="(tick, index) in ticks" :key="index">
              <line
                :x1="0"
                :y1="tick.y"
                :x2="chartWidth"
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
                {{ formatNumber(tick.value) }}
              </text>
            </g>
          </g>

          <g v-for="(item, index) in trends" :key="item.date">
            <g
              class="group cursor-pointer"
              @mouseenter="hoveredIndex = index"
              @mouseleave="hoveredIndex = null"
            >
              <rect
                :x="index * groupWidth"
                :y="0"
                :width="groupWidth"
                :height="chartHeight"
                fill="currentColor"
                class="text-slate-100/50 dark:text-slate-800/40 transition-opacity pointer-events-auto"
                :opacity="hoveredIndex === index ? 1 : 0"
                rx="6"
              />
            <rect
              :x="index * groupWidth + groupWidth / 2 - barWidth - 1.5"
              :y="chartHeight - barHeight(item.created)"
              :width="barWidth"
              :height="barHeight(item.created)"
              rx="3"
              fill="url(#auditCreatedGradient)"
              class="transition-all duration-200 group-hover:brightness-110"
            >
              <title>{{ item.label }}: Create {{ item.created }} data</title>
            </rect>
            <rect
              :x="index * groupWidth + groupWidth / 2 + 1.5"
              :y="chartHeight - barHeight(item.updated)"
              :width="barWidth"
              :height="barHeight(item.updated)"
              rx="3"
              fill="url(#auditUpdatedGradient)"
              class="transition-all duration-200 group-hover:brightness-110"
            >
              <title>{{ item.label }}: Update {{ item.updated }} data</title>
            </rect>
            <text
              :x="index * groupWidth + groupWidth / 2"
              :y="chartHeight + 18"
              text-anchor="middle"
              class="text-[10px] font-medium transition-colors"
              :class="hoveredIndex === index ? 'fill-[#2563EB] font-bold dark:fill-blue-400' : 'fill-slate-400 dark:fill-slate-500'"
            >
              {{ item.label }}
            </text>
            </g>
          </g>
        </g>
      </svg>

      <div
        v-if="tooltipPosition && hoveredIndex !== null"
        class="absolute pointer-events-none z-10 px-3 py-2 rounded-xl bg-slate-900/95 dark:bg-slate-800/95 text-white shadow-xl backdrop-blur-md border border-slate-700/60 text-xs transition-all duration-150"
        :class="tooltipPosition.translateClass"
        :style="{ left: tooltipPosition.left, top: tooltipPosition.top }"
      >
        <div class="font-semibold text-slate-200 pb-1 border-b border-slate-700/60 text-[11px] flex items-center justify-between gap-3">
          {{ trends[hoveredIndex].label }} ({{ trends[hoveredIndex].date }})
        </div>
        <div class="pt-1.5 space-y-1 text-[11px]">
          <div class="flex items-center justify-between gap-4">
            <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
              <span class="w-2 h-2 rounded-full bg-[#10B981]" />Create
            </span>
            <strong class="tabular-nums">{{ formatNumber(trends[hoveredIndex].created) }}</strong>
          </div>
          <div class="flex items-center justify-between gap-4">
            <span class="flex items-center gap-1.5 text-blue-400 font-medium">
              <span class="w-2 h-2 rounded-full bg-[#2563EB]" />Update
            </span>
            <strong class="tabular-nums">{{ formatNumber(trends[hoveredIndex].updated) }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>