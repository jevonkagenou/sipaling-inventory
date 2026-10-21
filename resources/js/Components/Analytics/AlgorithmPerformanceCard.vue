<script setup>
import { computed } from 'vue'
import { Sliders, TrendingUp, BarChart3, Sparkles } from 'lucide-vue-next'

const props = defineProps({
  alpha: { type: [Number, String], default: null },
  beta: { type: [Number, String], default: null },
  mape: { type: [Number, String], default: null },
  rmse: { type: [Number, String], default: null },
  optimalAlpha: { type: [Number, String], default: null },
  optimalBeta: { type: [Number, String], default: null },
  isOptimal: { type: Boolean, default: true },
})

const isNum = (v) => v !== null && v !== undefined && v !== '' && Number.isFinite(Number(v))
// Maksimal 2 desimal tanpa nol berlebih
const fmt = (v) => (isNum(v) ? String(Math.round(Number(v) * 100) / 100) : 'N/A')

const mapeValue = computed(() => (isNum(props.mape) ? Number(props.mape) : null))

// Klasifikasi MAPE (Lewis, 1982) dengan styling responsif dark mode yang sudah diselaraskan
const level = computed(() => {
  const m = mapeValue.value
  if (m === null) {
    return {
      label: 'Belum tersedia',
      badge: 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
      bar: 'bg-slate-300 dark:bg-slate-600',
    }
  }
  if (m < 10) {
    return {
      label: 'Sangat Akurat',
      badge: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/60',
      bar: 'bg-[#10B981]',
    }
  }
  if (m < 20) {
    return {
      label: 'Baik',
      badge: 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/60',
      bar: 'bg-[#2563EB]',
    }
  }
  if (m < 50) {
    return {
      label: 'Cukup',
      badge: 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900/60',
      bar: 'bg-amber-500',
    }
  }
  return {
    label: 'Kurang Akurat',
    badge: 'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/60',
    bar: 'bg-red-500',
  }
})

const barWidth = computed(() =>
  mapeValue.value === null ? 0 : Math.max(0, Math.min(100, 100 - mapeValue.value)),
)
</script>

<template>
  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <BarChart3 class="w-4 h-4 text-[#2563EB]" />
          Ringkasan Performa Algoritma
        </h2>
        <p class="text-[11px] text-slate-400 mt-0.5">
          Parameter Holt's Linear dan tingkat galat untuk produk yang sedang dievaluasi.
        </p>
      </div>
      <span
        class="inline-flex items-center gap-1 self-start sm:self-auto px-2.5 py-1 rounded-full text-[11px] font-medium border"
        :class="isOptimal
          ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/60'
          : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900/60'"
      >
        <Sparkles class="w-3 h-3" />
        {{ isOptimal ? 'Optimal (Grid Search)' : 'Parameter Manual' }}
      </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <!-- Alpha -->
      <div class="rounded-xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/60 dark:bg-slate-950/40 p-4">
        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
          <span>Nilai Alpha (&alpha;)</span>
          <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center">
            <Sliders class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-[#2563EB] dark:text-blue-400">
          {{ fmt(alpha) }}
        </p>
        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500 truncate">
          Bobot level<span v-if="!isOptimal"> &middot; optimal {{ fmt(optimalAlpha) }}</span>
        </p>
      </div>

      <!-- Beta -->
      <div class="rounded-xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/60 dark:bg-slate-950/40 p-4">
        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
          <span>Nilai Beta (&beta;)</span>
          <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-[#4F46E5] dark:text-indigo-400">
          {{ fmt(beta) }}
        </p>
        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500 truncate">
          Bobot tren<span v-if="!isOptimal"> &middot; optimal {{ fmt(optimalBeta) }}</span>
        </p>
      </div>

      <!-- MAPE -->
      <div class="rounded-xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/60 dark:bg-slate-950/40 p-4">
        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
          <span>Persentase MAPE</span>
          <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium border" :class="level.badge">
            {{ level.label }}
          </span>
        </div>
        <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white">
          {{ mapeValue === null ? 'N/A' : fmt(mape) + '%' }}
        </p>
        <div class="mt-2 h-1.5 w-full rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden" aria-hidden="true">
          <div class="h-full rounded-full transition-all duration-500" :class="level.bar" :style="{ width: barWidth + '%' }" />
        </div>
        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500 truncate">
          RMSE: {{ isNum(rmse) ? Math.round(Number(rmse)) : '-' }} unit
        </p>
      </div>
    </div>
  </div>
</template>