<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ClipboardCheck,
  ShieldCheck,
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
    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-[#4F46E5] border border-indigo-200/60 dark:bg-indigo-950/60 dark:border-indigo-800/60 uppercase tracking-wider">
              <ShieldCheck class="h-3.5 w-3.5" />
              Otorisasi Komisaris
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
            Persetujuan Usulan Restock
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Halaman peninjauan dan pengambilan keputusan persetujuan (*Setujui* atau *Tolak dengan Catatan*) oleh Komisaris.
          </p>
        </div>
      </div>

      <!-- Main Box -->
      <div class="rounded-2xl border border-slate-200/80 bg-white p-8 text-center shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] dark:bg-indigo-950/60 dark:text-indigo-400 mb-4">
          <ClipboardCheck class="h-7 w-7" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Antrean Persetujuan Bersih
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto mt-2 leading-relaxed">
          Saat ini belum ada draf usulan restock baru yang diajukan oleh Manajer Operasional dengan status <span class="font-semibold text-amber-600 dark:text-amber-400">PENDING_APPROVAL</span>.
        </p>
        <div class="mt-6 flex justify-center gap-3">
          <Link
            :href="route('restock.index')"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-all"
          >
            <Boxes class="h-4 w-4" />
            Monitoring Pengajuan
          </Link>
          <Link
            :href="route('analytics.index')"
            class="inline-flex items-center gap-2 rounded-xl bg-[#4F46E5] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700 transition-all"
          >
            <TrendingUp class="h-4 w-4" />
            Tinjau Prediksi DES
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
