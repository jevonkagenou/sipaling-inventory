<script setup>
import { computed } from 'vue'
import {
  BarChart3,
  Sliders,
  TrendingUp,
  Sparkles,
  SlidersHorizontal,
  CheckCircle2,
  Info,
  ShieldCheck,
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

// Tingkat keyakinan sistem (100 - MAPE)
const accuracyRate = computed(() => {
  if (props.mape === null || props.mape === undefined) return null
  return Math.max(0, 100 - Number(props.mape)).toFixed(1)
})

// Menghitung status akurasi dan narasi ramah pengguna awam
const accuracyBadge = computed(() => {
  if (props.mape === null || props.mape === undefined) return null
  if (props.mape < 10) {
    return {
      label: 'Sangat Akurat (Tinggi)',
      color:
        'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60',
      insight: 'Pola penjualan sangat konsisten. Proyeksi ini sangat aman dan direkomendasikan langsung untuk pengajuan restock.',
    }
  } else if (props.mape <= 20) {
    return {
      label: 'Akurasi Baik (Andal)',
      color:
        'bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 border-blue-200 dark:border-blue-800/60',
      insight: 'Pola penjualan teratur dengan fluktuasi wajar. Estimasi dapat diandalkan untuk perencanaan pengadaan bulanan.',
    }
  } else if (props.mape <= 50) {
    return {
      label: 'Akurasi Cukup (Moderat)',
      color:
        'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/60',
      insight: 'Terdapat fluktuasi permintaan musiman. Disarankan menyesuaikan jumlah restock dengan stok penyangga (safety stock).',
    }
  } else {
    return {
      label: 'Perlu Penyesuaian Manual',
      color:
        'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/60',
      insight: 'Riwayat transaksi penjualan sangat dinamis atau belum mencukupi. Gunakan angka ini sebagai estimasi kasar saja.',
    }
  }
})

// Kategori karakter parameter (Stabil vs Seimbang vs Responsif)
const parameterCategory = computed(() => {
  if (props.isOptimal) {
    return {
      title: 'Tingkat Seimbang (Optimal)',
      badgeCls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800/60',
      desc: 'Hasil kalkulasi otomatis terbaik dengan tingkat akurasi tertinggi.',
    }
  }
  if (props.alpha !== null && props.alpha <= 0.25) {
    return {
      title: 'Tingkat Stabil (Konservatif)',
      badgeCls: 'bg-blue-50 text-[#2563EB] border-blue-200 dark:bg-blue-950/60 dark:text-blue-400 dark:border-blue-800/60',
      desc: 'Meredam lonjakan sesaat, cocok untuk produk dengan permintaan stabil.',
    }
  }
  return {
    title: 'Tingkat Responsif (Cepat Tanggap)',
    badgeCls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-400 dark:border-amber-800/60',
    desc: 'Cepat menyesuaikan tren terbaru, cocok untuk tren penjualan yang sedang naik.',
  }
})

// Progress bar persentase keyakinan (0 - 100%)
const accuracyProgressWidth = computed(() => {
  if (accuracyRate.value === null) return '0%'
  return `${Math.min(Math.max(Number(accuracyRate.value), 0), 100)}%`
})
</script>

<template>
  <div
    class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4"
  >
    <!-- Header Kartu & Status Mode Parameter -->
    <!-- Header Ringkasan Performa Algoritma -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <BarChart3 class="w-4 h-4 text-[#2563EB]" />
          Ringkasan Performa Algoritma
        </h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
          Tingkat keandalan estimasi stok dan parameter Double Exponential Smoothing (DES).
        </p>
      </div>

      <!-- Badge Mode: Optimal vs Manual + Kategori Parameter -->
      <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto">
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shadow-2xs"
          :class="parameterCategory.badgeCls"
        >
          <Sparkles v-if="isOptimal" class="w-3.5 h-3.5" />
          <SlidersHorizontal v-else class="w-3.5 h-3.5" />
          {{ parameterCategory.title }}
        </span>
      </div>
    </div>

    <!-- Grid 3 Sub-Kartu Indikator -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
      <!-- 1. Sensitivitas Tren Terkini (Alpha) -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-blue-500/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 space-y-1.5"
      >
        <div class="flex items-center justify-between text-xs text-slate-400">
          <div>
            <span class="font-semibold text-slate-700 dark:text-slate-300 block">Nilai Alpha (&alpha;)</span>
            <span class="text-[10px] text-slate-400 font-mono">Sensitivitas Level</span>
          </div>
          <div
            class="h-7 w-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
          >
            <Sliders class="w-3.5 h-3.5" />
          </div>
        </div>
        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums pt-0.5">
          {{ alpha !== null ? alpha : '0' }}
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
          Respons riwayat terkini &bull; Rekomendasi: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ optimalAlpha ?? '-' }}</span>
        </p>
      </div>

      <!-- 2. Sensitivitas Arah Pertumbuhan (Beta) -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 space-y-1.5"
      >
        <div class="flex items-center justify-between text-xs text-slate-400">
          <div>
            <span class="font-semibold text-slate-700 dark:text-slate-300 block">Nilai Beta (&beta;)</span>
            <span class="text-[10px] text-slate-400 font-mono">Sensitivitas Tren</span>
          </div>
          <div
            class="h-7 w-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200"
          >
            <TrendingUp class="w-3.5 h-3.5" />
          </div>
        </div>
        <div class="text-2xl font-bold tracking-tight text-[#4F46E5] dark:text-indigo-400 tabular-nums pt-0.5">
          {{ beta !== null ? beta : '0' }}
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
          Adaptasi laju tren &bull; Rekomendasi: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ optimalBeta ?? '-' }}</span>
        </p>
      </div>

      <!-- 3. Tingkat Keyakinan Prediksi (Persentase MAPE) -->
      <div
        class="group p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 space-y-2"
      >
        <div class="flex items-center justify-between text-xs">
          <div>
            <span class="font-semibold text-slate-700 dark:text-slate-300 block">Persentase MAPE</span>
            <span class="text-[10px] text-slate-400 font-mono">Tingkat Galat Estimasi</span>
          </div>
          <span
            v-if="accuracyBadge"
            :class="accuracyBadge.color"
            class="px-2 py-0.5 rounded-full text-[10px] font-semibold border group-hover:scale-105 transition-transform duration-200"
          >
            {{ accuracyBadge.label }}
          </span>
        </div>

        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
          {{ mape !== null ? mape + '%' : '0%' }}
        </div>

        <!-- Progress Bar Visualisasi Tingkat Keyakinan -->
        <div class="w-full bg-slate-200/70 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
          <div
            class="bg-[#10B981] h-full transition-all duration-500"
            :style="{ width: accuracyProgressWidth }"
          />
        </div>

        <p class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
          Akurasi: {{ accuracyRate !== null ? accuracyRate + '%' : '-' }} &bull; Deviasi: {{ rmse !== null ? Math.round(rmse) : '-' }} unit
        </p>
      </div>
    </div>

    <!-- Wawasan Ringkas Bisnis (Humanized Insight Banner) -->
    <div
      v-if="accuracyBadge"
      class="flex items-start gap-2.5 p-3 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 text-xs text-slate-700 dark:text-slate-300"
    >
      <ShieldCheck class="w-4 h-4 text-[#2563EB] shrink-0 mt-0.5" />
      <div class="space-y-0.5">
        <span class="font-semibold text-slate-900 dark:text-white">Wawasan Operasional:</span>
        <p class="text-slate-600 dark:text-slate-400 text-[11px] leading-relaxed">
          {{ accuracyBadge.insight }} {{ parameterCategory.desc }}
        </p>
      </div>
    </div>
  </div>
</template>
