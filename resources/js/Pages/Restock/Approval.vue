<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ClipboardCheck,
  CheckCircle2,
  XCircle,
  Clock,
  Boxes,
  TrendingUp,
  FileText,
  AlertCircle,
} from 'lucide-vue-next'
</script>

<template>
  <Head title="Otorisasi Persetujuan Restock" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Operasional Gudang</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Persetujuan Usulan Restock
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Persetujuan Usulan Restock
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Halaman peninjauan dan pengambilan keputusan persetujuan (Setujui atau Tolak dengan Catatan) oleh Komisaris.
          </p>
        </div>
      </div>

      <!-- Main Box -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center shadow-2xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] dark:bg-indigo-950/60 dark:text-indigo-400 mb-4">
          <ClipboardCheck class="h-7 w-7" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Antrean Persetujuan Bersih
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto mt-2 leading-relaxed">
          Saat ini belum ada draf usulan restock baru yang diajukan oleh Manajer Operasional dengan status <span class="font-semibold text-amber-600 dark:text-amber-400">PENDING_APPROVAL</span>.
        </p>
        <div class="mt-6 flex justify-center gap-2.5">
          <Link
            :href="route('restock.index')"
            class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <Boxes class="w-3.5 h-3.5 text-slate-500" />
            Monitoring Pengajuan
          </Link>
          <Link
            :href="route('analytics.index')"
            class="h-9 px-4 rounded-xl bg-[#4F46E5] hover:bg-indigo-700 text-white font-semibold text-xs shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            Tinjau Prediksi DES
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
