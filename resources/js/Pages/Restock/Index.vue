<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ClipboardCheck,
  AlertTriangle,
  Clock,
  CheckCircle2,
  XCircle,
  Plus,
  ArrowRight,
  Boxes,
  TrendingUp,
  FileText,
  ShieldAlert,
} from 'lucide-vue-next'

const props = defineProps<{
  alerts?: any[]
  proposals?: any[]
}>()
</script>

<template>
  <Head title="Manajemen Pengajuan Restock" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Operasional Gudang</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Monitoring Restock
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Monitoring Pengajuan Restock
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Daftar usulan pengadaan barang kritis berdasarkan rekomendasi peramalan Holt DES dan persetujuan Komisaris.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <Link
            :href="route('analytics.index')"
            class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            Cek Prediksi DES
          </Link>
        </div>
      </div>

      <!-- KPI Summary Cards (Unified Interactive Cards) -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Menunggu Persetujuan -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-amber-500/50 dark:hover:border-amber-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Menunggu Persetujuan</span>
            <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Clock class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
              0
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">usulan</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Menunggu respon Komisaris
          </p>
        </div>

        <!-- Disetujui -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Disetujui</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <CheckCircle2 class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
              0
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">pesanan</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Siap dipesan ke supplier
          </p>
        </div>

        <!-- Ditolak / Direvisi -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Ditolak / Direvisi</span>
            <div class="h-8 w-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <XCircle class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              0
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">berkas</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Memerlukan penyesuaian
          </p>
        </div>
      </div>

      <!-- Main Container Notice -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center shadow-2xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-[#2563EB] dark:bg-blue-950/60 dark:text-blue-400 mb-4">
          <ClipboardCheck class="h-7 w-7" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Modul Pengajuan Restock Sedang Dipersiapkan
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto mt-2 leading-relaxed">
          Alur pengajuan usulan restock terintegrasi dengan rekomendasi prediksi Holt DES. Manajer Operasional dapat menyusun draf pengadaan berdasarkan data tren, dan Komisaris dapat memberikan persetujuan pada menu Otorisasi Persetujuan.
        </p>
        <div class="mt-6 flex justify-center gap-2.5">
          <Link
            :href="route('inventory.index')"
            class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <Boxes class="w-3.5 h-3.5 text-slate-500" />
            Buka Katalog Master
          </Link>
          <Link
            :href="route('analytics.index')"
            class="h-9 px-4 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            Eksplorasi Prediksi DES
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
