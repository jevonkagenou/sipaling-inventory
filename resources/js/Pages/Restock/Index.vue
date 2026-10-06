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
    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-[#2563EB] border border-blue-200/60 dark:bg-blue-950/60 dark:border-blue-800/60 uppercase tracking-wider">
              <ClipboardCheck class="h-3.5 w-3.5" />
              Alur Persetujuan Pengadaan
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
            Monitoring Pengajuan Restock
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Daftar usulan pengadaan barang kritis berdasarkan rekomendasi peramalan Holt DES dan persetujuan Komisaris.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            :href="route('analytics.index')"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2 text-xs sm:text-sm font-semibold text-[#2563EB] border border-blue-200 hover:bg-blue-100 transition-all dark:bg-blue-950/50 dark:border-blue-800 dark:text-blue-300"
          >
            <TrendingUp class="h-4 w-4" />
            Cek Prediksi DES
          </Link>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-4 shadow-2xs dark:border-amber-900/50 dark:bg-amber-950/30">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Menunggu Persetujuan</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/60 dark:text-amber-300">
              <Clock class="h-4 w-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2">0</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Menunggu respon Komisaris</p>
        </div>

        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-2xs dark:border-emerald-900/50 dark:bg-emerald-950/30">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Disetujui</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-[#10B981] dark:bg-emerald-900/60 dark:text-emerald-300">
              <CheckCircle2 class="h-4 w-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2">0</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Siap dipesan ke supplier</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ditolak / Direvisi</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
              <XCircle class="h-4 w-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2">0</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Memerlukan penyesuaian</p>
        </div>
      </div>

      <!-- Main Container Notice -->
      <div class="rounded-2xl border border-slate-200/80 bg-white p-8 text-center shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-[#2563EB] dark:bg-blue-950/60 dark:text-blue-400 mb-4">
          <ClipboardCheck class="h-7 w-7" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Modul Pengajuan Restock Sedang Dipersiapkan
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto mt-2 leading-relaxed">
          Alur pengajuan usulan restock terintegrasi dengan rekomendasi prediksi Holt DES. Manajer Operasional dapat menyusun draf pengadaan berdasarkan data tren, dan Komisaris dapat memberikan persetujuan pada menu Otorisasi Persetujuan.
        </p>
        <div class="mt-6 flex justify-center gap-3">
          <Link
            :href="route('inventory.index')"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-all"
          >
            <Boxes class="h-4 w-4" />
            Buka Katalog Master
          </Link>
          <Link
            :href="route('analytics.index')"
            class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition-all"
          >
            <TrendingUp class="h-4 w-4" />
            Eksplorasi Prediksi DES
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
