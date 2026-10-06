<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ShieldCheck,
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
    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-[#10B981] border border-emerald-200/60 dark:bg-emerald-950/60 dark:border-emerald-800/60 uppercase tracking-wider">
              <ShieldCheck class="h-3.5 w-3.5" />
              Tata Kelola & Kepatuhan
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
            Jejak Rekam Audit (Audit Trail)
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Pencatatan terpusat dan bukti forensik digital atas setiap perubahan data inventaris, mutasi stok, dan manajemen akun.
          </p>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Rekam Jejak</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
              <Activity class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.total_logs }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Mutasi & Inventaris</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-[#10B981] dark:bg-emerald-950/60">
              <Boxes class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.inventory_logs }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Aktivitas Pengguna</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-[#2563EB] dark:bg-blue-950/60">
              <Users class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.user_logs }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Log Peramalan DES</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-[#4F46E5] dark:bg-indigo-950/60">
              <Cpu class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.forecast_logs }}
          </p>
        </div>
      </div>

      <!-- Logs Table Card -->
      <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 overflow-hidden">
        <div class="border-b border-slate-100 p-4 dark:border-slate-800">
          <h2 class="text-sm font-bold text-slate-900 dark:text-white">Daftar Aktivitas Sistem Terkini</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm">
            <thead class="bg-slate-50/80 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <tr>
                <th class="py-3 px-4 font-semibold">Waktu & Tanggal</th>
                <th class="py-3 px-4 font-semibold">Kategori & Event</th>
                <th class="py-3 px-4 font-semibold">Deskripsi Aktivitas</th>
                <th class="py-3 px-4 font-semibold">Pengguna (Aktor)</th>
                <th class="py-3 px-4 font-semibold text-right">Detail</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
              >
                <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  <div class="flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                    <Clock class="h-3.5 w-3.5 text-slate-400" />
                    {{ formatDate(log.created_at) }}
                  </div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-2xs font-semibold border uppercase tracking-wider',
                      getEventBadgeClass(log.event)
                    ]"
                  >
                    {{ log.event || log.log_name }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-800 dark:text-slate-200">
                  <p class="font-medium line-clamp-1">{{ log.description }}</p>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 text-2xs font-bold">
                      {{ log.causer?.name?.charAt(0) || 'S' }}
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                      {{ log.causer?.name || 'Sistem / Anonim' }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 text-right whitespace-nowrap">
                  <button
                    @click="openDetail(log)"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-2xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer"
                  >
                    <Eye class="h-3 w-3" />
                    Lihat Diff
                  </button>
                </td>
              </tr>
              <tr v-if="logs.data.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">
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
            <pre class="mt-1 max-h-48 overflow-auto rounded-xl bg-slate-900 p-3 text-2xs text-emerald-400 font-mono">{{ JSON.stringify(selectedLog.attribute_changes || selectedLog.properties, null, 2) }}</pre>
          </div>
        </div>

        <div class="pt-2 text-right">
          <button
            @click="isDetailModalOpen = false"
            class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
