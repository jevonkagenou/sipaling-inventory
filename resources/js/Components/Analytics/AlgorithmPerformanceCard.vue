<script setup>
import { computed } from 'vue'
import {
  BarChart3,
  Sliders,
  TrendingUp,
  Sparkles,
  SlidersHorizontal,
} from 'lucide-vue-next'

const props = defineProps({
  alpha: {
    type: Number,
    default: null,
  },
  beta: {
    type: Number,
    default: null,
  },
  mape: {
    type: Number,
    default: null,
  },
  rmse: {
    type: Number,
    default: null,
  },
  optimalAlpha: {
    type: Number,
    default: null,
  },
  optimalBeta: {
    type: Number,
    default: null,
  },
  isOptimal: {
    type: Boolean,
    default: true,
  },
})

// Menghitung status akurasi berdasarkan nilai MAPE
const accuracyBadge = computed(() => {
  if (props.mape === null || props.mape === undefined) return null
  if (props.mape < 10) {
    return {
      label: 'Sangat Akurat',
      color:
        'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60',
    }
  } else if (props.mape <= 20) {
    return {
      label: 'Bagus',
      color:
        'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800/60',
    }
  } else if (props.mape <= 50) {
    return {
      label: 'Wajar',
      color:
        'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/60',
    }
  } else {
    return {
      label: 'Kurang Akurat',
      color:
        'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/60',
    }
  }
})

// Progress bar persentase MAPE (dibatasi maks 100%)
const mapeProgressWidth = computed(() => {
  if (props.mape === null || props.mape === undefined) return '0%'
  return `${Math.min(Math.max(props.mape, 0), 100)}%`
})
</script>

<template>
  <div
    class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4"
  >
    <!-- Header Kartu & Status Mode Parameter -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
    >
      <div>
        <h2
          class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2"
        >
          <BarChart3 class="w-4 h-4 text-[#2563EB]" />
          Ringkasan Performa Algoritma
        </h2>
        <p class="text-[11px] text-slate-400 mt-0.5">
          Parameter Holt's Linear dan tingkat galat untuk produk yang sedang
          dievaluasi.
        </p>
      </div>

      <!-- Badge Mode: Optimal vs Manual -->
      <div class="self-start sm:self-auto">
        <span
          v-if="isOptimal"
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 shadow-2xs"
        >
          <Sparkles class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
          Optimal (Grid Search)
        </span>
        <span
          v-else
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60 shadow-2xs"
        >
          <SlidersHorizontal
            class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"
          />
          Parameter Manual
        </span>
      </div>
    </div>

    <!-- Grid 3 Sub-Kartu Indikator Utama -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
      <!-- 1. Sub-Kartu Nilai Alpha -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-blue-500/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer space-y-1"
      >
        <div class="flex items-center justify-between text-xs text-slate-400">
          <span class="font-medium text-slate-600 dark:text-slate-400">Nilai Alpha (&alpha;)</span>
          <div
            class="h-7 w-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
          >
            <Sliders class="w-3.5 h-3.5" />
          </div>
        </div>
        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums pt-1">
          {{ alpha !== null ? alpha : '-' }}
        </div>
        <p class="text-[11px] text-slate-400">
          Bobot level &bull; optimal {{ optimalAlpha ?? '-' }}
        </p>
      </div>

      <!-- 2. Sub-Kartu Nilai Beta -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer space-y-1"
      >
        <div class="flex items-center justify-between text-xs text-slate-400">
          <span class="font-medium text-slate-600 dark:text-slate-400">Nilai Beta (&beta;)</span>
          <div
            class="h-7 w-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
          >
            <TrendingUp class="w-3.5 h-3.5" />
          </div>
        </div>
        <div class="text-2xl font-bold tracking-tight text-[#4F46E5] dark:text-indigo-400 tabular-nums pt-1">
          {{ beta !== null ? beta : '-' }}
        </div>
        <p class="text-[11px] text-slate-400">
          Bobot tren &bull; optimal {{ optimalBeta ?? '-' }}
        </p>
      </div>

      <!-- 3. Sub-Kartu Persentase MAPE -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-slate-300 dark:hover:border-slate-700 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer space-y-2"
      >
        <div class="flex items-center justify-between text-xs">
          <span class="font-medium text-slate-600 dark:text-slate-400">Persentase MAPE</span>
          <span
            v-if="accuracyBadge"
            :class="accuracyBadge.color"
            class="px-2 py-0.5 rounded-full text-[10px] font-semibold border group-hover:scale-105 transition-transform duration-200"
          >
            {{ accuracyBadge.label }}
          </span>
        </div>

        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
          {{ mape !== null ? mape + '%' : 'N/A' }}
        </div>

        <!-- Progress Bar Visualisasi Galat -->
        <div class="w-full bg-slate-200/70 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
          <div
            class="bg-red-500 h-full transition-all duration-500"
            :style="{ width: mapeProgressWidth }"
          />
        </div>

        <p class="text-[11px] text-slate-400 pt-0.5">
          RMSE: {{ rmse !== null ? Math.round(rmse) : '-' }} unit
        </p>
      </div>
    </div>
  </div>
</template>