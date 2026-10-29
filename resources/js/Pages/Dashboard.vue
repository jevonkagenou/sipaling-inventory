<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InboundOutboundChart, { type TrendItem } from '@/Components/Dashboard/InboundOutboundChart.vue'
import AuditActivityChart, { type AuditActivityTrend } from '@/Components/Dashboard/AuditActivityChart.vue'
import UserStatusDonutChart, { type UserStatsProps } from '@/Components/Dashboard/UserStatusDonutChart.vue'
import {
  Boxes,
  ArrowLeftRight,
  TrendingUp,
  AlertTriangle,
  PackagePlus,
  PackageMinus,
  ShieldCheck,
  CheckCircle2,
  ArrowRight,
  BarChart3,
  Users,
  Activity,
  FileEdit,
  History,
  ClipboardCheck,
  Clock3,
  XCircle,
} from 'lucide-vue-next'

interface ProductItem {
  id: string
  sku: string
  name: string
  current_stock: number
  minimum_stock: number
  unit: string
  unit_price: number | string
  category?: { id: string; name: string }
}

interface TransactionItem {
  id: string
  reference_no: string
  type: 'inbound' | 'outbound'
  transaction_date: string
  party_name?: string
  creator?: { id: string; name: string }
  details_count?: number
}

interface LogItem {
  id: number
  log_name: string
  description: string
  event: string
  created_at: string
  causer?: { id: string; name: string }
}

interface OutboundProductItem {
  id: string
  sku: string
  name: string
  unit: string
  outbound_count: number
  total_quantity: number
}

interface CommissionerDashboardData {
  pending_count: number
  approved_this_month: number
  rejected_this_month: number
  oldest_pending_at: string | null
  pending_requests: Array<{
    id: string
    request_no: string
    created_at: string
    total_estimated_cost: number | string
    requester?: { id: string; name: string }
    details_count: number
    details?: Array<{
      current_stock: number
      safety_stock: number
      requested_quantity: number
      product?: {
        sku: string
        name: string
        unit: string
        current_stock: number
      }
    }>
  }>
  recent_decisions: Array<{
    id: string
    request_no: string
    status: 'approved' | 'rejected'
    reviewed_at: string | null
    total_estimated_cost: number | string
    requester?: { id: string; name: string }
    reviewer?: { id: string; name: string } | null
  }>
}

const props = defineProps<{
  stats: {
    total_products: number
    total_categories: number
    total_transactions: number
    inbound_transactions: number
    outbound_transactions: number
    today_inbound_qty: number
    today_outbound_qty: number
    reorder_count: number
    aman_count: number
    total_inventory_value: number
    total_audit_logs: number
    today_audit_logs: number
    sensitive_changes_count?: number
  }
  criticalProducts: ProductItem[]
  recentTransactions: TransactionItem[]
  recentLogs: LogItem[]
  mutationTrends?: TrendItem[]
  topOutboundProducts?: OutboundProductItem[]
  auditActivityTrends?: AuditActivityTrend[]
  userStats?: UserStatsProps
  // Analitik DES
  products?: ProductItem[]
  selectedProductId?: string
  forecastData?: Record<string, any> | null
  restockAlerts?: Array<Record<string, any>>
  commissionerDashboard?: CommissionerDashboardData | null
  filters?: { alpha?: string | null; beta?: string | null }
}>()

const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const roles = computed<string[]>(() => page.props.auth?.roles || [])
const maxOutboundFrequency = computed(() => Math.max(
  1,
  ...(props.topOutboundProducts || []).map((item) => item.outbound_count)
))

const primaryRole = computed(() => {
  if (roles.value.includes('komisaris')) return 'komisaris'
  if (roles.value.includes('manajer-operasional')) return 'manajer-operasional'
  if (roles.value.includes('staf-gudang')) return 'staf-gudang'
  if (roles.value.includes('auditor-internal')) return 'auditor-internal'
  return roles.value[0] || 'User'
})

const roleBadge = computed(() => {
  switch (primaryRole.value) {
    case 'komisaris':
      return {
        label: 'Komisaris',
        cls: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60',
        desc: 'Pengawasan eksekutif, evaluasi kelayakan restock, dan tata kelola sistem.',
      }
    case 'manajer-operasional':
      return {
        label: 'Manajer Operasional',
        cls: 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800/60',
        desc: 'Pengelolaan katalog master data inventaris, evaluasi prediksi Holt DES, dan pengajuan restock.',
      }
    case 'staf-gudang':
      return {
        label: 'Staf Gudang',
        cls: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800/60',
        desc: 'Pencatatan mutasi fisik barang masuk (Inbound) dan barang keluar (Outbound).',
      }
    case 'auditor-internal':
      return {
        label: 'Auditor Internal',
        cls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
        desc: 'Investigasi log aktivitas, verifikasi integritas mutasi persediaan, dan jejak audit forensik.',
      }
    default:
      return {
        label: primaryRole.value,
        cls: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        desc: 'Akses operasional standar pengguna sistem SIPALING.',
      }
  }
})

function formatCurrency(val: number | string) {
  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
    minimumFractionDigits: 2,
  }).format(Number(val) || 0)
}

function formatRupiah(val: number | string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(val) || 0)
}

function pendingAge(dateStr: string | null) {
  if (!dateStr) return 'Tidak ada antrean'
  const days = Math.floor((Date.now() - new Date(dateStr).getTime()) / 86_400_000)
  return days <= 0 ? 'Masuk hari ini' : `${days} hari menunggu`
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(dateStr))
}

function getEventBadge(event?: string) {
  switch (event?.toLowerCase()) {
    case 'created':
      return {
        label: 'Dibuat',
        cls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
      }
    case 'updated':
      return {
        label: 'Diperbarui',
        cls: 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800/60',
      }
    case 'deleted':
      return {
        label: 'Dihapus',
        cls: 'bg-rose-50 text-rose-600 border-rose-200/80 dark:bg-rose-950/70 dark:text-rose-300 dark:border-rose-800/60',
      }
    case 'inbound':
      return {
        label: 'Inbound',
        cls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
      }
    case 'outbound':
      return {
        label: 'Outbound',
        cls: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800/60',
      }
    case 'exported':
      return {
        label: 'Ekspor',
        cls: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60',
      }
    default:
      return {
        label: event ? event.toUpperCase() : 'AKTIVITAS',
        cls: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
      }
  }
}
</script>

<template>
  <Head title="Dashboard - SIPALING" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Utama</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          {{ primaryRole === 'komisaris' ? 'Dasbor Komisaris' : 'Dasbor Operasional' }}
        </h1>
      </div>
    </template>

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- 1. Header Banner & Quick Actions -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Dasbor {{ roleBadge.label }}
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Selamat datang kembali, <strong class="text-slate-700 dark:text-slate-300">{{ user.name }}</strong>. {{ roleBadge.desc }}
          </p>
        </div>

        <!-- Role-Specific Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Staf Gudang Actions -->
          <template v-if="primaryRole === 'staf-gudang'">
            <Link
              :href="route('transactions.inbound.create')"
              class="h-9 px-3.5 rounded-xl bg-[#10B981] hover:bg-emerald-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <PackagePlus class="w-3.5 h-3.5" />
              Catat Masuk (Inbound)
            </Link>
            <Link
              :href="route('transactions.outbound.create')"
              class="h-9 px-3.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <PackageMinus class="w-3.5 h-3.5" />
              Catat Keluar (Outbound)
            </Link>
          </template>

          <!-- Manajer Operasional Actions -->
          <template v-else-if="primaryRole === 'manajer-operasional'">
            <Link
              :href="route('inventory.index')"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-700 font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <Boxes class="w-3.5 h-3.5 text-slate-500" />
              Katalog Master
            </Link>
            <Link
              :href="route('analytics.index')"
              class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <TrendingUp class="w-3.5 h-3.5" />
              Analisis DES & Restock
            </Link>
          </template>

          <!-- Komisaris Actions -->
          <template v-else-if="primaryRole === 'komisaris'">
            <Link
              :href="route('restock.approval')"
              class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <ClipboardCheck class="w-3.5 h-3.5" />
              Tinjau Pengajuan
            </Link>
            <Link
              :href="route('users.index')"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <Users class="w-3.5 h-3.5" />
              Pengguna
            </Link>
            <Link
              :href="route('audit.index')"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <ShieldCheck class="w-3.5 h-3.5" />
              Audit Trail
            </Link>
          </template>

          <!-- Auditor Internal Actions -->
          <template v-else>
            <Link
              :href="route('audit.index')"
              class="h-9 px-3.5 rounded-xl bg-[#10B981] hover:bg-emerald-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <ShieldCheck class="w-3.5 h-3.5" />
              Portal Investigasi Log
            </Link>
            <Link
              :href="route('transactions.index')"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-700 font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <ArrowLeftRight class="w-3.5 h-3.5 text-slate-500" />
              Verifikasi Mutasi
            </Link>
          </template>
        </div>
      </div>

      <!-- 2. Interactive KPI Stat Cards (Disajikan Sesuai Tanggung Jawab Role) -->
      <!-- A. KHUSUS AUDITOR INTERNAL: KPI Kepatuhan, Keamanan & Jejak Forensik -->
      <div v-if="primaryRole === 'auditor-internal'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Log Audit -->
        <Link
          :href="route('audit.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#10B981]/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Log Audit</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <ShieldCheck class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_audit_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">rekaman</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Jejak aktivitas forensik append-only
          </p>
        </Link>

        <!-- Card 2: Aktivitas Sistem Hari Ini -->
        <Link
          :href="route('audit.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Aktivitas Hari Ini</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Activity class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#2563EB] dark:text-blue-400 tabular-nums">
              {{ stats.today_audit_logs }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">kejadian</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Frekuensi aksi pengguna hari ini
          </p>
        </Link>

        <!-- Card 3: Objek Verifikasi Mutasi -->
        <Link
          :href="route('transactions.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#4F46E5]/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Objek Verifikasi Mutasi</span>
            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <ArrowLeftRight class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_transactions }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">transaksi</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            {{ stats.inbound_transactions }} Inbound &bull; {{ stats.outbound_transactions }} Outbound
          </p>
        </Link>

        <!-- Card 4: Perubahan Data Sensitif -->
        <Link
          :href="route('audit.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-amber-500/50 dark:hover:border-amber-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Perubahan Sensitif</span>
            <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <FileEdit class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
              {{ stats.sensitive_changes_count ?? 0 }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">rekaman</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Event modifikasi & penghapusan data
          </p>
        </Link>
      </div>

      <!-- B. ROLE STANDAR (Staf Gudang, Manajer Operasional, Komisaris): KPI Operasional & Stok -->
      <div v-else-if="primaryRole === 'komisaris'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Link
          :href="route('restock.approval')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-amber-500/50 dark:hover:border-amber-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Menunggu Persetujuan</span>
            <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <ClipboardCheck class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 text-2xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">
            {{ commissionerDashboard?.pending_count ?? 0 }}
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
            {{ pendingAge(commissionerDashboard?.oldest_pending_at ?? null) }}
          </p>
        </Link>

        <Link
          :href="route('restock.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Disetujui Bulan Ini</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <CheckCircle2 class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 text-2xl font-bold text-[#10B981] dark:text-emerald-400 tabular-nums">
            {{ commissionerDashboard?.approved_this_month ?? 0 }}
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Keputusan persetujuan restock</p>
        </Link>

        <Link
          :href="route('restock.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-rose-500/50 dark:hover:border-rose-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Ditolak Bulan Ini</span>
            <div class="h-8 w-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <XCircle class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 text-2xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">
            {{ commissionerDashboard?.rejected_this_month ?? 0 }}
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Keputusan dengan catatan evaluasi</p>
        </Link>

        <Link
          :href="route('users.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Tata Kelola Pengguna</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Users class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 text-sm font-bold text-slate-900 dark:text-white">Kelola akun & akses</div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Pengguna aktif dan hak akses sistem</p>
        </Link>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Katalog Barang -->
        <Link
          :href="route('inventory.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#2563EB]/50 dark:hover:border-blue-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Katalog Master</span>
            <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <Boxes class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_products }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">item barang</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Terbagi dalam {{ stats.total_categories }} kategori master
          </p>
        </Link>

        <!-- Card 2: Total Mutasi Transaksi -->
        <Link
          :href="route('transactions.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-[#4F46E5]/50 dark:hover:border-indigo-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Mutasi Stok</span>
            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#4F46E5] dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <ArrowLeftRight class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_transactions }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">transaksi</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            {{ stats.inbound_transactions }} Inbound &bull; {{ stats.outbound_transactions }} Outbound
          </p>
        </Link>

        <!-- Card 3: Stok Aman -->
        <Link
          :href="route('inventory.index')"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Stok Fisik Aman</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <CheckCircle2 class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
              {{ stats.aman_count }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">SKU</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Kuantitas di atas batas minimum stok
          </p>
        </Link>

        <!-- Card 4: Perlu Restock Segera (Amber Alert) -->
        <Link
          :href="route('analytics.index', { section: 'rekomendasi' }) + '#ringkasan-rekomendasi'"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs hover:border-amber-500/50 dark:hover:border-amber-500/50 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Perlu Pengadaan</span>
            <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
              <AlertTriangle class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
              {{ stats.reorder_count }}
            </span>
            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">SKU kritis</span>
          </div>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">
            Mencapai batas safety stock &bull; Evaluasi DES
          </p>
        </Link>
      </div>

      <div v-if="primaryRole === 'staf-gudang'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <ArrowLeftRight class="w-4 h-4 text-[#2563EB]" />
                Aktivitas Gudang (14 Hari Terakhir)
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Perbandingan barang masuk dan keluar harian.
              </p>
            </div>
            <Link
              :href="route('transactions.index')"
              class="h-8 px-3 rounded-lg bg-[#2563EB]/10 hover:bg-[#2563EB]/20 text-[#2563EB] dark:text-blue-400 text-xs font-semibold inline-flex items-center gap-1 transition-colors self-start sm:self-auto"
            >
              Riwayat Mutasi <ArrowRight class="w-3 h-3" />
            </Link>
          </div>

          <InboundOutboundChart :trends="props.mutationTrends || []" :height="220" />
        </section>

        <section class="lg:col-span-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
          <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <PackageMinus class="w-4 h-4 text-amber-600" />
              Barang Paling Sering Keluar
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
              Frekuensi transaksi outbound selama 14 hari.
            </p>
          </div>

          <div v-if="topOutboundProducts?.length" class="space-y-4">
            <div v-for="(item, index) in topOutboundProducts" :key="item.id" class="space-y-1.5">
              <div class="flex items-start justify-between gap-3 text-xs">
                <div class="min-w-0">
                  <div class="font-semibold text-slate-800 dark:text-slate-200 truncate" :title="item.name">
                    <span class="text-slate-400 font-mono mr-1">{{ index + 1 }}.</span>{{ item.name }}
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                </div>
                <div class="shrink-0 text-right">
                  <div class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ item.outbound_count }} trx</div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ item.total_quantity }} {{ item.unit }}</div>
                </div>
              </div>
              <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div
                  class="h-full rounded-full bg-amber-500 transition-all duration-300"
                  :style="{ width: `${Math.max(8, (item.outbound_count / maxOutboundFrequency) * 100)}%` }"
                />
              </div>
            </div>
          </div>
          <div v-else class="py-8 text-center text-xs text-slate-400">
            Belum ada transaksi barang keluar dalam periode ini.
          </div>
        </section>
      </div>

      <!-- ===== SECTION GRAFIK OPERASIONAL & KELOLA PENGGUNA (Manajer Operasional & Komisaris) ===== -->
      <div v-if="primaryRole === 'manajer-operasional'" class="space-y-4">
        <!-- Divider Header -->
        <div class="flex items-center gap-3 pt-2">
          <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
          <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
            <BarChart3 class="w-3.5 h-3.5 text-[#2563EB]" />
            Operasional Mutasi & Tata Kelola Pengguna
          </span>
          <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- 1. Grafik Tren Mutasi: Inbound vs Outbound (8 Cols) -->
          <div class="lg:col-span-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <ArrowLeftRight class="w-4 h-4 text-[#2563EB]" />
                  Tren Pergerakan Mutasi Stok (14 Hari Terakhir)
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  Perbandingan volume barang masuk (Inbound) dan barang keluar (Outbound) harian.
                </p>
              </div>

              <!-- Action Link: Seluruh Mutasi -->
              <div class="flex items-center gap-2">
                <Link
                  :href="route('transactions.index')"
                  class="h-8 px-3 rounded-lg bg-[#2563EB]/10 hover:bg-[#2563EB]/20 text-[#2563EB] dark:text-blue-400 text-xs font-semibold inline-flex items-center gap-1 transition-colors"
                >
                  Semua Mutasi <ArrowRight class="w-3 h-3" />
                </Link>
              </div>
            </div>

            <!-- Inbound vs Outbound SVG Chart Component -->
            <InboundOutboundChart :trends="props.mutationTrends || []" :height="220" />
          </div>

          <!-- 2. Pie/Donut Chart Status Akun Pengguna (4 Cols) -->
          <div class="lg:col-span-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs flex flex-col justify-between">
            <div>
              <div class="pb-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <Users class="w-4 h-4 text-[#2563EB]" />
                    Status Akun Pengguna
                  </h3>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Rasio akun aktif vs diblokir sistem.
                  </p>
                </div>
                <Link
                  :href="route('users.index')"
                  class="text-xs text-[#2563EB] dark:text-blue-400 hover:underline font-medium inline-flex items-center gap-0.5"
                >
                  Kelola <ArrowRight class="w-3 h-3" />
                </Link>
              </div>

              <!-- Donut Chart Component -->
              <div class="pt-4">
                <UserStatusDonutChart
                  :user-stats="props.userStats || { total: 0, active: 0, blocked: 0, active_percentage: 0 }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="primaryRole === 'komisaris'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
          <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <ClipboardCheck class="w-4 h-4 text-amber-600" />
                Antrean Persetujuan Restock
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pengajuan terlama ditampilkan lebih dahulu untuk ditinjau.
              </p>
            </div>
            <Link
              :href="route('restock.approval')"
              class="shrink-0 text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
            >
              Buka antrean <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div v-if="commissionerDashboard?.pending_requests.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <article
              v-for="request in commissionerDashboard.pending_requests"
              :key="request.id"
              class="p-4 sm:px-5 hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors"
            >
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-slate-900 dark:text-white">{{ request.request_no }}</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 dark:text-amber-300">
                      <Clock3 class="w-3 h-3" />{{ pendingAge(request.created_at) }}
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Diajukan {{ request.requester?.name || 'Pengguna' }} · {{ formatDate(request.created_at) }}
                  </p>
                </div>
                <div class="sm:text-right shrink-0">
                  <div class="text-xs font-bold text-slate-900 dark:text-white">{{ formatRupiah(request.total_estimated_cost) }}</div>
                  <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ request.details_count }} jenis barang</div>
                </div>
              </div>

              <div v-if="request.details?.length" class="mt-3 space-y-1.5">
                <div
                  v-for="detail in request.details.slice(0, 2)"
                  :key="detail.product?.sku || detail.requested_quantity"
                  class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-[11px]"
                >
                  <span class="min-w-0 truncate text-slate-700 dark:text-slate-300">
                    <span class="font-mono text-slate-400">{{ detail.product?.sku }}</span>
                    {{ detail.product?.name || 'Produk tidak tersedia' }}
                  </span>
                  <span class="shrink-0 text-slate-500 dark:text-slate-400">
                    Minta {{ detail.requested_quantity }} {{ detail.product?.unit }} · Stok saat pengajuan {{ detail.current_stock }} / batas {{ detail.safety_stock }}
                  </span>
                </div>
                <p v-if="request.details_count > 2" class="text-[10px] text-slate-400">
                  dan {{ request.details_count - 2 }} jenis barang lainnya
                </p>
              </div>

              <div class="mt-3 flex justify-end">
                <Link
                  :href="route('restock.approval')"
                  class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
                >
                  Tinjau pengajuan <ArrowRight class="w-3.5 h-3.5" />
                </Link>
              </div>
            </article>
          </div>
          <div v-else class="px-5 py-12 text-center">
            <CheckCircle2 class="mx-auto h-8 w-8 text-emerald-500" />
            <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Tidak ada pengajuan menunggu</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Semua pengajuan restock sudah ditinjau.</p>
          </div>
        </section>

        <section class="lg:col-span-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
          <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <History class="w-4 h-4 text-[#2563EB]" />
              Keputusan Terbaru
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Riwayat persetujuan dan penolakan.</p>
          </div>

          <div v-if="commissionerDashboard?.recent_decisions.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="decision in commissionerDashboard.recent_decisions"
              :key="decision.id"
              class="px-4 py-3.5"
            >
              <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[11px] font-semibold text-slate-800 dark:text-slate-200">{{ decision.request_no }}</span>
                <span
                  class="text-[10px] font-semibold uppercase"
                  :class="decision.status === 'approved' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'"
                >
                  {{ decision.status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                </span>
              </div>
              <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                {{ decision.requester?.name || 'Pengguna' }} · {{ formatDate(decision.reviewed_at || '') }}
              </p>
              <p class="mt-1 text-[11px] font-medium text-slate-700 dark:text-slate-300">
                {{ formatRupiah(decision.total_estimated_cost) }}
              </p>
            </div>
          </div>
          <div v-else class="px-5 py-10 text-center text-xs text-slate-400">
            Belum ada keputusan restock.
          </div>
          <div class="p-3 bg-slate-50/40 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link
              :href="route('restock.index')"
              class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
            >
              Semua Pengajuan <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </section>
      </div>

      <section v-if="primaryRole === 'auditor-internal'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Activity class="w-4 h-4 text-[#2563EB]" />
              Perbandingan Aktivitas Create & Update (14 Hari Terakhir)
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
              Jumlah rekaman audit Create dan Update per hari.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Link
              :href="route('audit.index')"
              class="h-8 px-3 rounded-lg bg-[#2563EB]/10 hover:bg-[#2563EB]/20 text-[#2563EB] dark:text-blue-400 text-xs font-semibold inline-flex items-center gap-1 transition-colors"
            >
              Lihat Jejak Audit <ArrowRight class="w-3 h-3" />
            </Link>
          </div>
        </div>
        <AuditActivityChart :trends="auditActivityTrends || []" />
      </section>

      <!-- 3. Operational Highlights & Actionable Lists -->
      <!-- A. KHUSUS AUDITOR INTERNAL: Sorotan Audit Trail Terkini (7 cols) & Verifikasi Dokumen Mutasi Fisik (5 cols) -->
      <div v-if="primaryRole === 'auditor-internal'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Sorotan Jejak Audit Terkini (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div>
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-[#10B981]" />
                  Sorotan Jejak Audit Terkini (Audit Trail Stream)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Pemantauan real-time integritas transaksi dan riwayat aktivitas pengguna.
                </p>
              </div>
              <Link
                :href="route('audit.index')"
                class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
              >
                Lihat Semua <ArrowRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <div class="p-4 space-y-3">
              <div
                v-for="log in recentLogs"
                :key="log.id"
                class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 flex items-start justify-between gap-3 text-xs hover:border-slate-200 dark:hover:border-slate-700 transition-colors"
              >
                <div class="min-w-0 space-y-1.5 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase border"
                      :class="getEventBadge(log.event).cls"
                    >
                      {{ getEventBadge(log.event).label }}
                    </span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                      Oleh: <strong class="text-slate-700 dark:text-slate-300 font-medium">{{ log.causer?.name || 'Sistem' }}</strong>
                    </span>
                    <span class="text-slate-300 dark:text-slate-700 text-[10px]">&bull;</span>
                    <span class="text-[10px] text-slate-400 font-mono uppercase">
                      Modul {{ log.log_name || 'System' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-700 dark:text-slate-300 font-medium leading-relaxed">
                    {{ log.description }}
                  </p>
                </div>
                <div class="text-[10px] text-slate-400 shrink-0 font-mono mt-0.5">
                  {{ formatDate(log.created_at) }}
                </div>
              </div>

              <div v-if="recentLogs.length === 0" class="py-10 text-center text-slate-400 text-xs">
                Belum ada rekaman audit log yang tercatat.
              </div>
            </div>
          </div>

          <div class="p-3 bg-slate-50/40 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link
              :href="route('audit.index')"
              class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
            >
              Buka Portal Investigasi Forensik Lengkap <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- Right: Dokumen Mutasi Fisik untuk Cross-Check (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div>
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <History class="w-4 h-4 text-[#2563EB]" />
                  Dokumen Mutasi Terkini
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Objek verifikasi pencocokan fisik / stock opname.
                </p>
              </div>
              <Link
                :href="route('transactions.index')"
                class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
              >
                Lihat Semua <ArrowRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <div class="p-4 space-y-3">
              <div
                v-for="tx in recentTransactions"
                :key="tx.id"
                class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 flex items-center justify-between gap-3 text-xs"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold"
                    :class="tx.type === 'inbound'
                      ? 'bg-emerald-100 text-[#10B981] dark:bg-emerald-950/80'
                      : 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-400'"
                  >
                    <PackagePlus v-if="tx.type === 'inbound'" class="w-3.5 h-3.5" />
                    <PackageMinus v-else class="w-3.5 h-3.5" />
                  </div>
                  <div class="min-w-0">
                    <div class="font-mono font-semibold text-slate-900 dark:text-white truncate">
                      {{ tx.reference_no }}
                    </div>
                    <div class="text-[10px] text-slate-400 truncate">
                      {{ tx.party_name || (tx.type === 'inbound' ? 'Supplier' : 'Customer') }} &bull; {{ tx.details_count }} SKU
                    </div>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <span
                    class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase"
                    :class="tx.type === 'inbound'
                      ? 'bg-emerald-50 text-[#10B981] dark:bg-emerald-950/60'
                      : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'"
                  >
                    {{ tx.type }}
                  </span>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    {{ formatDate(tx.transaction_date) }}
                  </div>
                </div>
              </div>

              <div v-if="recentTransactions.length === 0" class="py-8 text-center text-slate-400 text-xs">
                Belum ada transaksi mutasi yang tercatat.
              </div>
            </div>
          </div>

          <div class="p-3 bg-slate-50/40 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link
              :href="route('transactions.index')"
              class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
            >
              Buka Seluruh Mutasi Stok <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>

      <!-- B. ROLE STANDAR (Staf Gudang, Manajer Operasional, Komisaris) -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Critical Stock Alert Table (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div>
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <AlertTriangle class="w-4 h-4 text-amber-500" />
                  Barang Mendekati Batas Minimum (Safety Stock)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Daftar prioritas pengadaan barang untuk menjaga kontinuitas persediaan.
                </p>
              </div>
              <Link
                :href="route('analytics.index')"
                class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
              >
                Analisis DES <ArrowRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
                  <tr class="h-10">
                    <th class="px-3 py-2.5 text-left whitespace-nowrap">SKU</th>
                    <th class="px-3 py-2.5 text-left whitespace-nowrap">Nama Barang</th>
                    <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok Fisik</th>
                    <th class="px-3 py-2.5 text-right whitespace-nowrap">Batas Min</th>
                    <th class="px-3 py-2.5 text-center whitespace-nowrap">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr
                    v-for="item in criticalProducts"
                    :key="item.id"
                    class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td class="px-3 py-2.5 whitespace-nowrap">
                      <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono tracking-wider text-[11px] font-semibold">
                        {{ item.sku }}
                      </span>
                    </td>
                    <td class="px-3 py-2.5">
                      <div class="font-semibold text-slate-900 dark:text-white text-xs truncate max-w-[190px]" :title="item.name">
                        {{ item.name }}
                      </div>
                      <div class="text-[10px] text-slate-400">
                        {{ item.category?.name || 'Retail' }}
                      </div>
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums font-bold text-amber-600 dark:text-amber-400 text-xs whitespace-nowrap">
                      {{ item.current_stock }} {{ item.unit }}
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 text-xs whitespace-nowrap">
                      {{ item.minimum_stock }} {{ item.unit }}
                    </td>
                    <td class="px-3 py-2.5 text-center whitespace-nowrap">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                        Reorder
                      </span>
                    </td>
                  </tr>
                  <tr v-if="criticalProducts.length === 0">
                    <td colspan="5" class="px-3 py-8 text-center text-slate-400 text-xs">
                      Seluruh produk saat ini berada dalam level stok aman.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="p-3 bg-slate-50/40 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link
              :href="route('inventory.index')"
              class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
            >
              Buka Katalog Master Lengkap <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- Right: Recent Activity / Transactions Stream (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden flex flex-col justify-between">
          <div>
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <History class="w-4 h-4 text-[#2563EB]" />
                  Riwayat Mutasi Terkini
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Arus keluar-masuk barang fisik gudang.
                </p>
              </div>
              <Link
                :href="route('transactions.index')"
                class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
              >
                Lihat Semua <ArrowRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <div class="p-4 space-y-3">
              <div
                v-for="tx in recentTransactions"
                :key="tx.id"
                class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 flex items-center justify-between gap-3 text-xs"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold"
                    :class="tx.type === 'inbound'
                      ? 'bg-emerald-100 text-[#10B981] dark:bg-emerald-950/80'
                      : 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-400'"
                  >
                    <PackagePlus v-if="tx.type === 'inbound'" class="w-3.5 h-3.5" />
                    <PackageMinus v-else class="w-3.5 h-3.5" />
                  </div>
                  <div class="min-w-0">
                    <div class="font-mono font-semibold text-slate-900 dark:text-white truncate">
                      {{ tx.reference_no }}
                    </div>
                    <div class="text-[10px] text-slate-400 truncate">
                      {{ tx.party_name || (tx.type === 'inbound' ? 'Supplier' : 'Customer') }} &bull; {{ tx.details_count }} SKU
                    </div>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <span
                    class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase"
                    :class="tx.type === 'inbound'
                      ? 'bg-emerald-50 text-[#10B981] dark:bg-emerald-950/60'
                      : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'"
                  >
                    {{ tx.type }}
                  </span>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    {{ formatDate(tx.transaction_date) }}
                  </div>
                </div>
              </div>

              <div v-if="recentTransactions.length === 0" class="py-8 text-center text-slate-400 text-xs">
                Belum ada transaksi mutasi yang tercatat.
              </div>
            </div>
          </div>

          <div class="p-3 bg-slate-50/40 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link
              :href="route('transactions.index')"
              class="text-xs text-slate-600 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium inline-flex items-center gap-1"
            >
              Buka Seluruh Mutasi Stok <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>

      <!-- 4. Sorotan Jejak Audit Bawah (Hanya untuk Komisaris, karena Auditor Internal sudah tampil di bagian utama) -->
      <div v-if="primaryRole === 'komisaris'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-[#10B981]" />
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              Sorotan Jejak Audit (Audit Trail Activity Stream)
            </h3>
          </div>
          <Link
            :href="route('audit.index')"
            class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
          >
            Portal Audit Lengkap <ArrowRight class="w-3.5 h-3.5" />
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
          <div
            v-for="log in recentLogs"
            :key="log.id"
            class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/30 text-xs space-y-1"
          >
            <div class="flex items-center justify-between text-[11px]">
              <span class="font-semibold text-slate-700 dark:text-slate-300 capitalize">
                {{ log.event || 'Aktivitas' }}
              </span>
              <span class="text-[10px] text-slate-400 font-mono">
                {{ formatDate(log.created_at) }}
              </span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2">
              {{ log.description }}
            </p>
            <div class="text-[10px] text-slate-400 pt-0.5">
              Oleh: <strong class="text-slate-700 dark:text-slate-300">{{ log.causer?.name || 'Sistem' }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
