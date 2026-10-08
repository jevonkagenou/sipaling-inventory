<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Boxes,
  ArrowLeftRight,
  TrendingUp,
  AlertTriangle,
  CheckCircle2,
  PackagePlus,
  PackageMinus,
  ShieldCheck,
  History,
  ArrowRight,
  Clock,
  Layers,
  Sparkles,
  ExternalLink,
  Users,
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
  }
  criticalProducts: ProductItem[]
  recentTransactions: TransactionItem[]
  recentLogs: LogItem[]
}>()

const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const roles = computed<string[]>(() => page.props.auth?.roles || [])

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

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(dateStr))
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
          Dasbor Operasional
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
              class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
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
              Katalog Inventaris
            </Link>
            <Link
              :href="route('analytics.index')"
              class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <TrendingUp class="w-3.5 h-3.5" />
              Peramalan DES
            </Link>
          </template>

          <!-- Komisaris Actions -->
          <template v-else-if="primaryRole === 'komisaris'">
            <Link
              :href="route('analytics.index')"
              class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-700 font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <TrendingUp class="w-3.5 h-3.5 text-slate-500" />
              Analisis Permintaan
            </Link>
            <Link
              :href="route('audit.index')"
              class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
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
          </template>
        </div>
      </div>

      <!-- 2. Unified Interactive KPI Stat Cards (Matching Inventory / Stitch AI Standards) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
            {{ stats.inbound_transactions }} Inbound • {{ stats.outbound_transactions }} Outbound
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
          :href="route('analytics.index')"
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

      <!-- 3. Operational Highlights & Actionable Lists -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
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
                    <td class="px-3 py-2.5 text-right tabular-nums font-bold text-amber-600 dark:text-amber-400 text-xs">
                      {{ item.current_stock }} {{ item.unit }}
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 text-xs">
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
                      : 'bg-blue-100 text-[#2563EB] dark:bg-blue-950/80'"
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
                      : 'bg-blue-50 text-[#2563EB] dark:bg-blue-950/60'"
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

      <!-- 4. Quick Governance & Audit Trail Strip (For Auditor & Executive roles) -->
      <div v-if="primaryRole === 'auditor-internal' || primaryRole === 'komisaris'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-2xs space-y-3">
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
