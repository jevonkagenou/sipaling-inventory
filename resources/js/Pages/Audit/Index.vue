<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Search,
  Filter,
  FileText,
  User,
  Clock,
  Eye,
  Download,
  Activity,
  Boxes,
  Users,
  CheckCircle2,
  Calendar,
  X,
  ArrowUpDown,
  Cpu,
} from 'lucide-vue-next'

export interface LogItem {
  id: number
  log_name: string
  description: string
  subject_type?: string
  subject_id?: string
  causer_type?: string
  causer_id?: string
  causer?: { id: string; name: string; email: string }
  event: string
  properties?: any
  created_at: string
}

const props = defineProps<{
  logs: {
    data: LogItem[]
    current_page: number
    last_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
  }
  stats: {
    total_logs: number
    inventory_logs: number
    user_logs: number
    forecast_logs: number
  }
  filters?: {
    search?: string
    log_name?: string
  }
}>()

const search = ref(props.filters?.search || '')
const selectedLog = ref<LogItem | null>(null)
const isDetailModalOpen = ref(false)

function openDetail(log: LogItem) {
  selectedLog.value = log
  isDetailModalOpen.value = true
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'medium',
  }).format(new Date(dateStr))
}

function getEventBadgeClass(event: string) {
  switch (event) {
    case 'created':
    case 'inbound':
      return 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/60 dark:border-emerald-800/60'
    case 'updated':
    case 'status_changed':
      return 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/60 dark:border-blue-800/60'
    case 'deleted':
    case 'outbound':
      return 'bg-amber-50 text-amber-600 border-amber-200/80 dark:bg-amber-950/60 dark:border-amber-800/60'
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300'
  }
}
</script>

<template>
  <Head title="Jejak Rekam Audit (Audit Trail)" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Tata Kelola</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Jejak Rekam Audit
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Jejak Rekam Audit (Audit Trail)
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Pencatatan terpusat dan bukti forensik digital atas setiap perubahan data inventaris, mutasi stok, dan manajemen akun.
          </p>
        </div>
      </div>

      <!-- KPI Summary Cards (Unified Interactive Cards) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Rekam Jejak</span>
            <div class="h-8 w-8 rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Activity class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">entri</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Seluruh rekam jejak sistem
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Mutasi & Inventaris</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Boxes class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.inventory_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Aktivitas produk &amp; stok gudang
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Aktivitas Pengguna</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Users class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.user_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Autentikasi &amp; peran pengguna
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#4F46E5]/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Log Peramalan DES</span>
            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Cpu class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.forecast_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">log</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Riwayat komputasi &amp; simulasi
          </p>
        </div>
      </div>

      <!-- Logs Table Card -->
      <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 bg-slate-50/40 dark:bg-slate-900/40">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Daftar Aktivitas Sistem Terkini</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/80 text-xs font-semibold text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr class="h-10">
                <th class="px-3 py-2.5 whitespace-nowrap">Waktu &amp; Tanggal</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Kategori &amp; Event</th>
                <th class="px-3 py-2.5">Deskripsi Aktivitas</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Pengguna (Aktor)</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="px-3 py-2.5 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap tabular-nums">
                  <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <Clock class="h-3.5 w-3.5 text-slate-400" />
                    {{ formatDate(log.created_at) }}
                  </div>
                </td>
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium border uppercase tracking-wider',
                      getEventBadgeClass(log.event)
                    ]"
                  >
                    {{ log.event || log.log_name }}
                  </span>
                </td>
                <td class="px-3 py-2.5 text-slate-800 dark:text-slate-200 text-xs">
                  <p class="font-medium line-clamp-1">{{ log.description }}</p>
                </td>
                <td class="px-3 py-2.5 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 text-[10px] font-bold">
                      {{ log.causer?.name?.charAt(0) || 'S' }}
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                      {{ log.causer?.name || 'Sistem / Anonim' }}
                    </span>
                  </div>
                </td>
                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                  <button
                    @click="openDetail(log)"
                    class="h-7 px-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs inline-flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <Eye class="h-3.5 w-3.5 text-slate-400" />
                    Detail
                  </button>
                </td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="5" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400 text-xs">
                  Belum ada rekaman audit log.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Detail Diff / Properties -->
    <div
      v-if="isDetailModalOpen && selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
      @click.self="isDetailModalOpen = false"
    >
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Detail Forensik Rekam Audit
          </h3>
          <button @click="isDetailModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs sm:text-sm">
          <div>
            <span class="text-xs font-semibold text-slate-400">Deskripsi:</span>
            <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedLog.description }}</p>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div>
              <span class="text-slate-400">Waktu:</span>
              <p class="font-medium text-slate-700 dark:text-slate-300">{{ formatDate(selectedLog.created_at) }}</p>
            </div>
            <div>
              <span class="text-slate-400">Aktor:</span>
              <p class="font-medium text-slate-700 dark:text-slate-300">{{ selectedLog.causer?.name || 'Sistem' }}</p>
            </div>
          </div>

          <div>
            <span class="text-xs font-semibold text-slate-400">Payload Forensik / Diff (JSON):</span>
            <pre class="mt-1 max-h-48 overflow-auto rounded-xl bg-slate-900 p-3 text-[11px] text-emerald-400 font-mono">{{ JSON.stringify(selectedLog.attribute_changes || selectedLog.properties, null, 2) }}</pre>
          </div>
        </div>

        <div class="pt-2 text-right">
          <button
            @click="isDetailModalOpen = false"
            class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-colors cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
