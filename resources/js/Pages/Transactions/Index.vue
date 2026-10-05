<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  PackagePlus,
  PackageMinus,
  ArrowUpDown,
  Search,
  Calendar,
  Building2,
  FileText,
  User,
  Eye,
  Filter,
  ArrowRight,
  Boxes,
  Clock,
  CheckCircle2,
  X,
  Printer,
} from 'lucide-vue-next'

export interface TransactionProduct {
  id: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
}

export interface TransactionDetail {
  id: string
  product_id: string
  quantity: number
  unit_price: number | string
  notes?: string
  product?: TransactionProduct
}

export interface TransactionItem {
  id: string
  reference_no: string
  type: 'inbound' | 'outbound'
  transaction_date: string
  party_name?: string
  notes?: string
  created_by?: string
  creator?: { id: string; name: string }
  details_count?: number
  details?: TransactionDetail[]
}

const props = defineProps<{
  transactions: {
    data: TransactionItem[]
    current_page: number
    last_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
  }
  filters: {
    type?: string
    search?: string
  }
  stats: {
    total_inbound: number
    total_outbound: number
    today_inbound_qty: number
    today_outbound_qty: number
  }
}>()

const search = ref(props.filters.search || '')
const currentType = ref(props.filters.type || '')

let searchTimeout: any = null
watch(search, (val) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilter()
  }, 350)
})

function setTypeFilter(type: string) {
  currentType.value = type
  applyFilter()
}

function applyFilter() {
  router.get(
    route('transactions.index'),
    {
      search: search.value || undefined,
      type: currentType.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

// Modal Detail View
const selectedTransaction = ref<TransactionItem | null>(null)
const isDetailModalOpen = ref(false)

const originalTitle = document.title

function openDetail(tx: TransactionItem) {
  selectedTransaction.value = tx
  isDetailModalOpen.value = true
  document.title = `Operasional Mutasi Stok_${tx.reference_no}`
}

function closeDetail() {
  selectedTransaction.value = null
  isDetailModalOpen.value = false
  document.title = originalTitle
}

function printReceipt() {
  window.print()
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(d)
}

function formatRupiah(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}
</script>

<template>
  <Head title="Operasional Mutasi Stok" />

  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl space-y-6 pb-16">
      <!-- Header Banner & Action Buttons -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-[#2563EB] border border-blue-200/60 dark:bg-blue-950/60 dark:border-blue-800/60 uppercase tracking-wider">
              <Boxes class="h-3.5 w-3.5" />
              Operasional Gudang
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
            Mutasi Stok Barang
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Pencatatan dan pemantauan riwayat barang masuk (*Inbound*) dan pengeluaran barang (*Outbound*).
          </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-3">
          <Link
            :href="route('transactions.inbound.create')"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-500/20 hover:from-[#059669] hover:to-[#047857] transition-all cursor-pointer"
          >
            <PackagePlus class="h-4 w-4" />
            Catat Masuk (Inbound)
          </Link>

          <Link
            :href="route('transactions.outbound.create')"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-amber-600/20 hover:from-amber-700 hover:to-amber-800 transition-all cursor-pointer"
          >
            <PackageMinus class="h-4 w-4" />
            Catat Keluar (Outbound)
          </Link>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <!-- Inbound Total -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Transaksi Masuk</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-[#10B981] dark:bg-emerald-950/60">
              <PackagePlus class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.total_inbound }}
          </p>
          <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 block">
            Dokumen Inbound Terdaftar
          </span>
        </div>

        <!-- Outbound Total -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Transaksi Keluar</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60">
              <PackageMinus class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-2">
            {{ stats.total_outbound }}
          </p>
          <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-0.5 block">
            Dokumen Outbound Terdaftar
          </span>
        </div>

        <!-- Inbound Hari Ini -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Masuk Hari Ini</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-[#2563EB] dark:bg-blue-950/60">
              <Calendar class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-[#10B981] mt-2">
            + {{ stats.today_inbound_qty }} Unit
          </p>
          <span class="text-[11px] text-slate-400 mt-0.5 block">
            Kuantitas Fisik Diterima
          </span>
        </div>

        <!-- Outbound Hari Ini -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Keluar Hari Ini</span>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-[#4F46E5] dark:bg-purple-950/60">
              <Clock class="h-4 w-4" />
            </div>
          </div>
          <p class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">
            - {{ stats.today_outbound_qty }} Unit
          </p>
          <span class="text-[11px] text-slate-400 mt-0.5 block">
            Kuantitas Fisik Dikeluarkan
          </span>
        </div>
      </div>

      <!-- Main Data Table Card -->
      <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md overflow-hidden">
        <!-- Toolbar & Filter Tabs -->
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <!-- Type Filter Tabs -->
          <div class="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800 w-fit">
            <button
              type="button"
              @click="setTypeFilter('')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                !currentType
                  ? 'bg-white text-slate-900 shadow-2xs dark:bg-slate-900 dark:text-white'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              Semua Mutasi
            </button>
            <button
              type="button"
              @click="setTypeFilter('inbound')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                currentType === 'inbound'
                  ? 'bg-[#10B981] text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <PackagePlus class="h-3.5 w-3.5" />
              Barang Masuk
            </button>
            <button
              type="button"
              @click="setTypeFilter('outbound')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                currentType === 'outbound'
                  ? 'bg-amber-600 text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <PackageMinus class="h-3.5 w-3.5" />
              Barang Keluar
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full sm:w-72">
            <input
              v-model="search"
              type="text"
              placeholder="Cari No Ref, Pihak, SKU..."
              class="w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-9 pr-3 py-2 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 dark:border-slate-700 dark:bg-slate-800/60 dark:text-white dark:focus:bg-slate-800"
            />
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
          </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm">
            <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800/80">
              <tr>
                <th class="px-4 py-3">No. Referensi</th>
                <th class="px-4 py-3">Tipe</th>
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">Pemasok / Tujuan</th>
                <th class="px-4 py-3">Rincian Item</th>
                <th class="px-4 py-3">Pencatat</th>
                <th class="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-if="transactions.data.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-xs text-slate-400">
                  <Boxes class="h-8 w-8 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
                  Belum ada rekaman transaksi mutasi stok.
                </td>
              </tr>
              <tr
                v-for="tx in transactions.data"
                :key="tx.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
              >
                <!-- Reference No -->
                <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">
                  {{ tx.reference_no }}
                </td>

                <!-- Type Badge -->
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold border uppercase tracking-wider',
                      tx.type === 'inbound'
                        ? 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/60 dark:border-emerald-800/60'
                        : 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/60 dark:border-amber-800/60 dark:text-amber-300',
                    ]"
                  >
                    <component :is="tx.type === 'inbound' ? PackagePlus : PackageMinus" class="h-3 w-3" />
                    {{ tx.type === 'inbound' ? 'Masuk' : 'Keluar' }}
                  </span>
                </td>

                <!-- Date -->
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                  {{ formatDate(tx.transaction_date) }}
                </td>

                <!-- Party Name -->
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200 font-medium">
                  {{ tx.party_name || '-' }}
                </td>

                <!-- Details Preview -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-1.5">
                    <span class="inline-block px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300">
                      {{ tx.details_count || tx.details?.length || 0 }} SKU
                    </span>
                    <span class="text-xs text-slate-400 truncate max-w-[150px]">
                      {{ tx.details?.[0]?.product?.name || '' }}
                    </span>
                  </div>
                </td>

                <!-- Creator -->
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">
                  {{ tx.creator?.name || 'Sistem' }}
                </td>

                <!-- Action -->
                <td class="px-4 py-3 text-right">
                  <button
                    type="button"
                    @click="openDetail(tx)"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#2563EB] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                  >
                    <Eye class="h-3.5 w-3.5" />
                    Detail
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="transactions.last_page > 1"
          class="p-4 border-t border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500"
        >
          <span>Menampilkan {{ transactions.data.length }} dari {{ transactions.total }} transaksi</span>
          <div class="flex items-center gap-1">
            <template v-for="(link, i) in transactions.links" :key="i">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1.5 rounded-lg font-medium transition-colors',
                  link.active
                    ? 'bg-[#2563EB] text-white font-bold'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                ]"
              />
            </template>
          </div>
        </div>
      </div>

      <!-- Modal Detail Bukti Transaksi -->
      <Teleport to="body">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
    v-if="isDetailModalOpen && selectedTransaction"
    class="receipt-modal-root fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    @click.self="closeDetail"
    >
            <div id="receipt-print-area" class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="hidden print:block text-center mb-4 pb-3 border-b-2 border-slate-900">
              <h2 class="text-lg font-bold">SIPALING — Bukti Serah Terima Barang</h2>
              <p class="text-xs text-slate-500">Sistem Inventaris Prediktif & Audit Log Terintegrasi</p>
            </div>
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <div class="flex items-center gap-2">
                    <span
                      :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider',
                        selectedTransaction.type === 'inbound'
                          ? 'bg-emerald-50 text-[#10B981] border-emerald-200'
                          : 'bg-amber-50 text-amber-700 border-amber-200',
                      ]"
                    >
                      {{ selectedTransaction.type === 'inbound' ? 'Barang Masuk (Inbound)' : 'Barang Keluar (Outbound)' }}
                    </span>
                  </div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1">
                    Bukti Transaksi: {{ selectedTransaction.reference_no }}
                  </h3>
                </div>
                <button
                  type="button"
                  @click="closeDetail"
                  class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>

              <!-- Metadata Grid -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl text-xs">
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Waktu Transaksi</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatDate(selectedTransaction.transaction_date) }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ selectedTransaction.type === 'inbound' ? 'Pemasok' : 'Penerima' }}</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ selectedTransaction.party_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">Petugas Pencatat</span>
                  <span class="font-semibold text-slate-800 dark:text-slate-200">{{ selectedTransaction.creator?.name || 'Sistem' }}</span>
                </div>
              </div>

              <!-- Item List Table -->
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Rincian Barang Mutasi</h4>
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 font-semibold uppercase text-[10px]">
                      <tr>
                        <th class="px-3 py-2">SKU & Nama Produk</th>
                        <th class="px-3 py-2 text-center">Kuantitas</th>
                        <th class="px-3 py-2 text-right">Harga Unit</th>
                        <th class="px-3 py-2">Keterangan</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                      <tr v-for="dt in selectedTransaction.details" :key="dt.id">
                        <td class="px-3 py-2.5">
                          <p class="font-bold text-slate-900 dark:text-white">{{ dt.product?.name }}</p>
                          <span class="font-mono text-[10px] text-slate-400">{{ dt.product?.sku }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center font-bold" :class="selectedTransaction.type === 'inbound' ? 'text-emerald-600' : 'text-amber-600'">
                          {{ selectedTransaction.type === 'inbound' ? '+' : '-' }}{{ dt.quantity }} {{ dt.product?.unit }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-medium text-slate-700 dark:text-slate-300">
                          {{ formatRupiah(dt.unit_price) }}
                        </td>
                        <td class="px-3 py-2.5 text-slate-400">
                          {{ dt.notes || '-' }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            <!-- Notes -->
            <div v-if="selectedTransaction.notes" class="text-xs bg-slate-50 dark:bg-slate-800/40 p-3 rounded-xl">
              <span class="font-semibold text-slate-700 dark:text-slate-300 block mb-0.5">Catatan:</span>
              <p class="text-slate-500 dark:text-slate-400">{{ selectedTransaction.notes }}</p>
            </div>

            <!-- Kolom tanda tangan khusus cetak -->
            <div class="signature-grid hidden print:grid grid-cols-2 gap-8 pt-8 mt-4 text-xs">
              <div class="text-center">
                <p class="mb-12">Diserahkan oleh,</p>
                <p class="border-t border-slate-900 pt-1">( _______________ )</p>
              </div>
              <div class="text-center">
                <p class="mb-12">Diterima oleh,</p>
                <p class="border-t border-slate-900 pt-1">( _______________ )</p>
              </div>
            </div>

              <div class="flex justify-end gap-2 pt-2 no-print">
              <button
                type="button"
                @click="closeDetail"
                class="rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
              >
                Tutup
              </button>
              <button
                type="button"
                @click="printReceipt"
                class="inline-flex items-center gap-1.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 px-4 py-2 text-xs font-semibold text-white transition-colors"
              >
                <Printer class="h-3.5 w-3.5" />
                Cetak Bukti
              </button>
            </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AuthenticatedLayout>
</template>
