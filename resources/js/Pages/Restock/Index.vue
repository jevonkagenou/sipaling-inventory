<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ClipboardCheck, Clock, CheckCircle2, XCircle, Eye, Search, X, TrendingUp, FileText, Boxes, ShieldCheck, PackagePlus, AlertTriangle, RotateCcw,
} from 'lucide-vue-next'

export interface RestockProduct {
  id: string
  sku: string
  name: string
  unit: string
  unit_price?: number | string
  current_stock?: number
  minimum_stock?: number
}

export interface RestockRequestDetail {
  id: string
  restock_request_id?: string
  product_id: string
  product?: RestockProduct
  current_stock: number
  safety_stock: number
  suggested_quantity: number
  requested_quantity: number
  approved_quantity?: number | null
  estimated_unit_price: number | string
  subtotal: number | string
  notes?: string
}

export interface RestockRequest {
  id: string
  request_no: string
  requested_by: string
  requester?: { id: string; name: string; email?: string }
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  reviewed_by?: string | null
  reviewer?: { id: string; name: string; email?: string } | null
  reviewed_at?: string | null
  review_notes?: string | null
  total_estimated_cost: number | string
  general_notes?: string | null
  created_at: string
  details?: RestockRequestDetail[]
  details_count?: number
}

const props = defineProps<{
  requests?: {
    data: RestockRequest[]
  } | RestockRequest[]
}>()

// RBAC
const page = usePage()
const currentUser = computed(() => (page.props.auth as any)?.user || { name: 'Pengguna SIPALING' })
const userRoles = computed<string[]>(() => {
  const r = (page.props.auth as any)?.roles
  return Array.isArray(r) ? r : []
})
const isKomisaris = computed(() => userRoles.value.includes('komisaris'))
const isManager = computed(() => userRoles.value.includes('manajer-operasional'))

// Sample blueprint dataset
const sampleRequests: RestockRequest[] = [
  {
    id: 'req-001',
    request_no: 'REQ-202610-0001',
    requested_by: 'Budi Santoso',
    requester: { id: 'usr-1', name: 'Budi Santoso', email: 'budi.manajer@sipaling.id' },
    status: 'pending',
    reviewed_by: null,
    reviewer: null,
    reviewed_at: null,
    review_notes: null,
    total_estimated_cost: 12850000,
    general_notes: 'Usulan pengadaan mendesak berdasarkan sinyal RESTOCK_URGENT Holt DES untuk mengantisipasi lonjakan permintaan ritel akhir bulan.',
    created_at: '2026-10-19T14:30:00+07:00',
    details_count: 3,
    details: [
      {
        id: 'det-101',
        product_id: 'prd-1',
        product: { id: 'prd-1', sku: 'PRD-MKO-001', name: 'Minyak Goreng Sawit 2L', unit: 'Pouch' },
        current_stock: 14,
        safety_stock: 40,
        suggested_quantity: 120,
        requested_quantity: 120,
        approved_quantity: null,
        estimated_unit_price: 34000,
        subtotal: 4080000,
      },
      {
        id: 'det-102',
        product_id: 'prd-2',
        product: { id: 'prd-2', sku: 'PRD-BRS-002', name: 'Beras Premium Ramos 5kg', unit: 'Sak' },
        current_stock: 8,
        safety_stock: 30,
        suggested_quantity: 80,
        requested_quantity: 80,
        approved_quantity: null,
        estimated_unit_price: 72000,
        subtotal: 5760000,
      },
      {
        id: 'det-103',
        product_id: 'prd-3',
        product: { id: 'prd-3', sku: 'PRD-GLA-003', name: 'Gula Pasir Kristal Putih 1kg', unit: 'Pack' },
        current_stock: 25,
        safety_stock: 50,
        suggested_quantity: 172,
        requested_quantity: 172,
        approved_quantity: null,
        estimated_unit_price: 17500,
        subtotal: 3010000,
      },
    ],
  },
  {
    id: 'req-002',
    request_no: 'REQ-202610-0002',
    requested_by: 'Budi Santoso',
    requester: { id: 'usr-1', name: 'Budi Santoso', email: 'budi.manajer@sipaling.id' },
    status: 'approved',
    reviewed_by: 'Ir. Hendra Kusuma',
    reviewer: { id: 'usr-2', name: 'Ir. Hendra Kusuma' },
    reviewed_at: '2026-10-18T10:15:00+07:00',
    review_notes: 'Disetujui penuh sesuai rekomendasi model Holt DES. Segera koordinasikan dengan distributor utama.',
    total_estimated_cost: 8400000,
    general_notes: 'Pengadaan bahan baku kemasan dan kopi untuk penyangga stok awal kuartal IV.',
    created_at: '2026-10-17T09:00:00+07:00',
    details_count: 2,
    details: [
      {
        id: 'det-201',
        product_id: 'prd-4',
        product: { id: 'prd-4', sku: 'PRD-TPG-004', name: 'Tepung Terigu Segitiga 1kg', unit: 'Pack' },
        current_stock: 20,
        safety_stock: 50,
        suggested_quantity: 200,
        requested_quantity: 200,
        approved_quantity: 200,
        estimated_unit_price: 12000,
        subtotal: 2400000,
      },
      {
        id: 'det-202',
        product_id: 'prd-5',
        product: { id: 'prd-5', sku: 'PRD-KPI-005', name: 'Kopi Bubuk Robusta Malang 250g', unit: 'Pcs' },
        current_stock: 12,
        safety_stock: 35,
        suggested_quantity: 200,
        requested_quantity: 200,
        approved_quantity: 200,
        estimated_unit_price: 30000,
        subtotal: 6000000,
      },
    ],
  },
  {
    id: 'req-003',
    request_no: 'REQ-202610-0003',
    requested_by: 'Budi Santoso',
    requester: { id: 'usr-1', name: 'Budi Santoso', email: 'budi.manajer@sipaling.id' },
    status: 'rejected',
    reviewed_by: 'Ir. Hendra Kusuma',
    reviewer: { id: 'usr-2', name: 'Ir. Hendra Kusuma' },
    reviewed_at: '2026-10-16T16:45:00+07:00',
    review_notes: 'Harga estimasi unit dari supplier saat ini terlalu tinggi (+18% acuan kontrak). Mohon negosiasikan ulang.',
    total_estimated_cost: 5250000,
    general_notes: 'Pengadaan pelengkap produk sirup & perisa minuman ringan.',
    created_at: '2026-10-16T11:20:00+07:00',
    details_count: 2,
    details: [
      {
        id: 'det-301',
        product_id: 'prd-6',
        product: { id: 'prd-6', sku: 'PRD-SRP-006', name: 'Sirup Cocopandan Botol 460ml', unit: 'Botol' },
        current_stock: 18,
        safety_stock: 30,
        suggested_quantity: 150,
        requested_quantity: 150,
        approved_quantity: 0,
        estimated_unit_price: 22000,
        subtotal: 3300000,
      },
      {
        id: 'det-302',
        product_id: 'prd-7',
        product: { id: 'prd-7', sku: 'PRD-THC-007', name: 'Teh Celup Melati Kotak 25s', unit: 'Kotak' },
        current_stock: 40,
        safety_stock: 60,
        suggested_quantity: 300,
        requested_quantity: 300,
        approved_quantity: 0,
        estimated_unit_price: 6500,
        subtotal: 1950000,
      },
    ],
  },
]

// State list
const initialData = computed<RestockRequest[]>(() => {
  if (props.requests) {
    if (Array.isArray(props.requests)) return props.requests
    if (Array.isArray(props.requests.data)) return props.requests.data
  }
  return sampleRequests
})

const requestsList = ref<RestockRequest[]>([...initialData.value])
const search = ref('')
const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>('all')
const flashMessage = ref('')

// Filter logic
const filteredRequests = computed(() => {
  let list = requestsList.value

  if (selectedStatus.value !== 'all') {
    list = list.filter((r) => r.status === selectedStatus.value)
  }

  if (search.value.trim()) {
    const q = search.value.toLowerCase().trim()
    list = list.filter((r) => {
      const matchNo = r.request_no.toLowerCase().includes(q)
      const matchRequester = (r.requester?.name || r.requested_by || '').toLowerCase().includes(q)
      const matchReviewer = (r.reviewer?.name || r.reviewed_by || '').toLowerCase().includes(q)
      const matchNotes = (r.general_notes || '').toLowerCase().includes(q)
      const matchItems = r.details?.some((d) => d.product?.name.toLowerCase().includes(q) || d.product?.sku.toLowerCase().includes(q))
      return matchNo || matchRequester || matchReviewer || matchNotes || matchItems
    })
  }

  return list
})

// KPI Metrics
const stats = computed(() => {
  const all = requestsList.value
  const pending = all.filter((r) => r.status === 'pending').length
  const approved = all.filter((r) => r.status === 'approved').length
  const rejected = all.filter((r) => r.status === 'rejected' || r.status === 'cancelled').length

  return { pending, approved, rejected }
})

// Detail modal state
const selectedRequest = ref<RestockRequest | null>(null)
const isDetailOpen = ref(false)
const reviewNotesInput = ref('')

function openDetail(req: RestockRequest) {
  selectedRequest.value = req
  reviewNotesInput.value = req.review_notes || ''
  isDetailOpen.value = true
}

function closeDetail() {
  selectedRequest.value = null
  isDetailOpen.value = false
  reviewNotesInput.value = ''
}

// Otorisasi Komisaris
function handleApprove(req: RestockRequest) {
  if (!isKomisaris.value) return
  req.status = 'approved'
  req.reviewed_by = currentUser.value?.name || 'Komisaris'
  req.reviewer = { id: currentUser.value?.id || 'kom', name: req.reviewed_by }
  req.reviewed_at = new Date().toISOString()
  req.review_notes = reviewNotesInput.value.trim() || 'Disetujui penuh oleh Komisaris sesuai rekomendasi model Holt DES.'
  req.details?.forEach((d) => { d.approved_quantity = d.requested_quantity })

  flashMessage.value = `Berkas ${req.request_no} telah disetujui!`
  setTimeout(() => { flashMessage.value = '' }, 4000)
}

function handleReject(req: RestockRequest) {
  if (!isKomisaris.value) return
  if (!reviewNotesInput.value.trim()) {
    alert('Harap lampirkan alasan penolakan pada catatan evaluasi.')
    return
  }
  req.status = 'rejected'
  req.reviewed_by = currentUser.value?.name || 'Komisaris'
  req.reviewer = { id: currentUser.value?.id || 'kom', name: req.reviewed_by }
  req.reviewed_at = new Date().toISOString()
  req.review_notes = reviewNotesInput.value.trim()
  req.details?.forEach((d) => { d.approved_quantity = 0 })

  flashMessage.value = `Berkas ${req.request_no} ditolak dengan catatan evaluasi.`
  setTimeout(() => { flashMessage.value = '' }, 4000)
}

function resetFilters() {
  search.value = ''
  selectedStatus.value = 'all'
}

function resetSampleData() {
  requestsList.value = JSON.parse(JSON.stringify(sampleRequests))
  resetFilters()
  flashMessage.value = 'Data simulasi restock berhasil dikembalikan ke sampel awal.'
  setTimeout(() => { flashMessage.value = '' }, 3000)
}

function formatRupiah(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch (e) {
    return dateStr
  }
}
</script>

<template>
  <Head title="Monitoring Pengajuan Restock" />

  <AuthenticatedLayout>
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
      <!-- Flash Alert -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="flashMessage"
          class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs sm:text-sm font-semibold text-emerald-800 dark:text-emerald-300 shadow-2xs backdrop-blur-md"
        >
          <div class="flex items-center gap-2.5">
            <CheckCircle2 class="h-4 w-4 shrink-0 text-[#10B981]" />
            <span>{{ flashMessage }}</span>
          </div>
          <button type="button" @click="flashMessage = ''" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 cursor-pointer p-1">
            <X class="w-4 h-4" />
          </button>
        </div>
      </Transition>

      <!-- Top Header & Action Buttons -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Monitoring Pengajuan Restock
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Tabel berkas pengadaan multi-item berbasis rekomendasi peramalan Holt DES dan riwayat persetujuan Komisaris.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <Link
            v-if="isManager"
            :href="route('analytics.index', { section: 'rekomendasi' }) + '#ringkasan-rekomendasi'"
            class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
            title="Pilih produk dari analitik DES untuk mengajukan restock"
          >
            <Boxes class="w-3.5 h-3.5" />
            <span>Ajukan via Analitik DES</span>
          </Link>

          <span
            v-if="isKomisaris"
            class="h-9 px-3 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/80 dark:bg-indigo-950/40 text-[#4F46E5] dark:text-indigo-300 text-xs font-semibold inline-flex items-center gap-1.5"
          >
            <ShieldCheck class="w-3.5 h-3.5 text-[#4F46E5]" />
            <span>Otorisator Komisaris</span>
          </span>

          <Link
            :href="route('analytics.index')"
            class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <TrendingUp class="w-3.5 h-3.5 text-[#2563EB]" />
            <span>Tinjau Prediksi DES</span>
          </Link>
        </div>
      </div>

      <!-- Metric KPI Cards (Interactive Filters) -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 1. Menunggu Persetujuan -->
        <div
          @click="selectedStatus = 'pending'"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="selectedStatus === 'pending'
            ? 'border-amber-500 ring-2 ring-amber-500/20'
            : 'border-slate-200 dark:border-slate-800 hover:border-amber-500/50'"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Menunggu Persetujuan</span>
            <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <Clock class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
              {{ stats.pending }}
            </span>
            <span class="text-xs text-slate-500">berkas</span>
          </div>
          <p class="text-[11px] text-slate-400 mt-1 truncate">Menunggu keputusan Komisaris</p>
        </div>

        <!-- 2. Disetujui -->
        <div
          @click="selectedStatus = 'approved'"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="selectedStatus === 'approved'
            ? 'border-emerald-500 ring-2 ring-emerald-500/20'
            : 'border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Disetujui Komisaris</span>
            <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <CheckCircle2 class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
              {{ stats.approved }}
            </span>
            <span class="text-xs text-slate-500">berkas</span>
          </div>
          <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 truncate">Siap terbit Inbound gudang</p>
        </div>

        <!-- 3. Ditolak -->
        <div
          @click="selectedStatus = 'rejected'"
          class="rounded-2xl border bg-white dark:bg-slate-900 p-5 shadow-2xs hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 cursor-pointer group"
          :class="selectedStatus === 'rejected'
            ? 'border-rose-500 ring-2 ring-rose-500/20'
            : 'border-slate-200 dark:border-slate-800 hover:border-rose-500/50'"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Ditolak / Perlu Revisi</span>
            <div class="h-8 w-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <XCircle class="h-4 w-4" />
            </div>
          </div>
          <div class="mt-2.5 flex items-baseline gap-1.5">
            <span class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
              {{ stats.rejected }}
            </span>
            <span class="text-xs text-slate-500">berkas</span>
          </div>
          <p class="text-[11px] text-slate-400 mt-1 truncate">Membutuhkan evaluasi / negosiasi</p>
        </div>
      </div>

      <!-- Main Data Table Container -->
      <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md overflow-hidden">
        <!-- Toolbar & Filter Tabs -->
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-col gap-3.5 xl:flex-row xl:items-center xl:justify-between">
          <!-- Status Tabs -->
          <div class="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800 w-full sm:w-fit overflow-x-auto">
            <button
              type="button"
              @click="selectedStatus = 'all'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer shrink-0',
                selectedStatus === 'all'
                  ? 'bg-white text-slate-900 shadow-2xs dark:bg-slate-900 dark:text-white'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              Semua ({{ requestsList.length }})
            </button>
            <button
              type="button"
              @click="selectedStatus = 'pending'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                selectedStatus === 'pending'
                  ? 'bg-amber-500 text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <Clock class="h-3.5 w-3.5" />
              Menunggu ({{ stats.pending }})
            </button>
            <button
              type="button"
              @click="selectedStatus = 'approved'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                selectedStatus === 'approved'
                  ? 'bg-[#10B981] text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              Disetujui ({{ stats.approved }})
            </button>
            <button
              type="button"
              @click="selectedStatus = 'rejected'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0',
                selectedStatus === 'rejected'
                  ? 'bg-rose-600 text-white shadow-2xs'
                  : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
            >
              <XCircle class="h-3.5 w-3.5" />
              Ditolak ({{ stats.rejected }})
            </button>
          </div>

          <!-- Search & Reset Filter -->
          <div class="flex items-center gap-2.5 w-full xl:w-auto">
            <div class="relative w-full sm:w-72 xl:w-80">
              <input
                v-model="search"
                type="text"
                placeholder="Cari No. Berkas (REQ-...), barang, pemohon..."
                class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-8 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-900 dark:text-white shadow-2xs"
              />
              <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
              <button
                v-if="search"
                type="button"
                @click="search = ''"
                class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                title="Hapus pencarian"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <button
              v-if="search || selectedStatus !== 'all'"
              type="button"
              @click="resetFilters"
              class="h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-rose-600 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer shrink-0"
            >
              <X class="h-3.5 w-3.5" />
              <span>Reset</span>
            </button>
          </div>
        </div>

        <!-- Clean, Polished Table View -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
              <tr class="h-10">
                <th class="px-4 py-2.5 whitespace-nowrap">No. Pengajuan</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Waktu Dibuat</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Pemohon</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Rincian Barang</th>
                <th class="px-4 py-2.5 whitespace-nowrap text-right">Estimasi Biaya</th>
                <th class="px-4 py-2.5 whitespace-nowrap text-center">Status Otorisasi</th>
                <th class="px-4 py-2.5 whitespace-nowrap">Peninjau & Catatan</th>
                <th class="px-4 py-2.5 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <!-- Empty State -->
              <tr v-if="filteredRequests.length === 0">
                <td colspan="8" class="px-4 py-12 text-center text-xs text-slate-400">
                  <ClipboardCheck class="h-8 w-8 mx-auto text-slate-300 dark:text-slate-600 mb-2 stroke-[1.5]" />
                  <p class="font-medium text-slate-600 dark:text-slate-400">
                    Tidak ada berkas pengajuan restock yang sesuai kriteria.
                  </p>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Gunakan tombol reset untuk menampilkan kembali seluruh pengadaan.
                  </p>
                  <div class="mt-3">
                    <button
                      type="button"
                      @click="resetFilters"
                      class="text-xs font-semibold text-[#2563EB] hover:underline cursor-pointer inline-flex items-center gap-1"
                    >
                      Reset Filter
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr
                v-for="req in filteredRequests"
                :key="req.id"
                class="group hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
              >
                <!-- 1. No. Pengajuan -->
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px] font-semibold">
                    <FileText class="w-3 h-3 text-slate-400" />
                    {{ req.request_no }}
                  </span>
                </td>

                <!-- 2. Waktu Dibuat -->
                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs whitespace-nowrap tabular-nums">
                  {{ formatDate(req.created_at) }}
                </td>

                <!-- 3. Pemohon -->
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-100 text-[#2563EB] dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-[10px] font-bold shrink-0">
                      {{ (req.requester?.name || req.requested_by || 'U')[0] }}
                    </div>
                    <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                      {{ req.requester?.name || req.requested_by }}
                    </span>
                  </div>
                </td>

                <!-- 4. Rincian Barang (Multi-Item) -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-1.5 flex-wrap max-w-xs">
                    <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-300 text-[11px] font-bold tabular-nums">
                      {{ req.details_count || req.details?.length || 0 }} SKU
                    </span>
                    <span class="text-[11px] text-slate-600 dark:text-slate-300 truncate max-w-[170px]" :title="req.details?.[0]?.product?.name">
                      {{ req.details?.[0]?.product?.name || 'Produk Restock' }}
                      <span v-if="(req.details?.length || 0) > 1" class="text-slate-400 font-normal">
                        +{{ (req.details?.length || 0) - 1 }} lainnya
                      </span>
                    </span>
                  </div>
                </td>

                <!-- 5. Total Biaya -->
                <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-900 dark:text-white whitespace-nowrap">
                  {{ formatRupiah(req.total_estimated_cost) }}
                </td>

                <!-- 6. Status Otorisasi Badge -->
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border',
                      req.status === 'pending'
                        ? 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/60 dark:border-amber-800/60 dark:text-amber-300'
                        : req.status === 'approved'
                          ? 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/60 dark:border-emerald-800/60'
                          : req.status === 'rejected'
                            ? 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/60 dark:border-rose-800/60 dark:text-rose-300'
                            : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400',
                    ]"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full"
                      :class="{
                        'bg-amber-500': req.status === 'pending',
                        'bg-[#10B981]': req.status === 'approved',
                        'bg-rose-500': req.status === 'rejected',
                        'bg-slate-400': req.status === 'cancelled',
                      }"
                    />
                    <span>
                      {{
                        req.status === 'pending'
                          ? 'Menunggu'
                          : req.status === 'approved'
                            ? 'Disetujui'
                            : req.status === 'rejected'
                              ? 'Ditolak'
                              : 'Dibatalkan'
                      }}
                    </span>
                  </span>
                </td>

                <!-- 7. Peninjau & Catatan -->
                <td class="px-4 py-3 max-w-[200px]">
                  <div v-if="req.reviewed_by || req.reviewer">
                    <p class="font-semibold text-slate-800 dark:text-slate-200 text-xs truncate">
                      {{ req.reviewer?.name || req.reviewed_by }}
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate" :title="req.review_notes || '-'">
                      {{ req.review_notes || 'Telah dievaluasi' }}
                    </p>
                  </div>
                  <div v-else class="text-slate-400 text-[11px] italic">
                    Belum ditinjau
                  </div>
                </td>

                <!-- 8. Aksi -->
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <button
                    type="button"
                    @click="openDetail(req)"
                    class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:text-[#2563EB] hover:border-[#2563EB]/40 dark:hover:border-blue-500/40 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 cursor-pointer"
                  >
                    <Eye class="h-3.5 w-3.5" />
                    <span>{{ req.status === 'pending' && isKomisaris ? 'Otorisasi' : 'Detail' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer Summary -->
        <div class="p-3.5 bg-slate-50/70 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
          <span>
            Menampilkan {{ filteredRequests.length }} dari total {{ requestsList.length }} berkas pengadaan.
          </span>
          <button
            type="button"
            @click="resetSampleData"
            class="text-[11px] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 inline-flex items-center gap-1 cursor-pointer"
          >
            <RotateCcw class="w-3 h-3" />
            <span>Reset Data Simulasi</span>
          </button>
        </div>
      </div>

      <!-- Detail Modal Dialog (Clean Multi-Item Preview) -->
      <Teleport to="body">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="isDetailOpen && selectedRequest"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="closeDetail"
          >
            <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col">
              <!-- Header -->
              <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md z-10">
                <div class="flex items-center gap-2.5">
                  <div class="h-9 w-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center">
                    <FileText class="h-5 w-5" />
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Berkas Pengadaan: {{ selectedRequest.request_no }}
                      </h3>
                      <span
                        :class="[
                          'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                          selectedRequest.status === 'pending'
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : selectedRequest.status === 'approved'
                              ? 'bg-emerald-50 text-[#10B981] border-emerald-200'
                              : 'bg-rose-50 text-rose-700 border-rose-200',
                        ]"
                      >
                        {{ selectedRequest.status === 'pending' ? 'Menunggu Persetujuan' : selectedRequest.status }}
                      </span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                      Diajukan pada {{ formatDate(selectedRequest.created_at) }}
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="closeDetail"
                  class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 cursor-pointer transition-colors"
                >
                  <X class="h-5 w-5" />
                </button>
              </div>


              <div class="p-6 space-y-5 flex-1">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl text-xs">
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Pemohon (Manajer)</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                      {{ selectedRequest.requester?.name || selectedRequest.requested_by }}
                    </span>
                  </div>
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Peninjau (Komisaris)</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                      {{ selectedRequest.reviewer?.name || selectedRequest.reviewed_by || 'Menunggu Keputusan' }}
                    </span>
                  </div>
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Waktu Keputusan</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                      {{ selectedRequest.reviewed_at ? formatDate(selectedRequest.reviewed_at) : '-' }}
                    </span>
                  </div>
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Estimasi Total Biaya</span>
                    <span class="font-bold text-[#2563EB] dark:text-blue-400 tabular-nums">
                      {{ formatRupiah(selectedRequest.total_estimated_cost) }}
                    </span>
                  </div>
                </div>

                <!-- Justifikasi Pengadaan -->
                <div v-if="selectedRequest.general_notes" class="text-xs bg-slate-50 dark:bg-slate-800/40 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                  <span class="font-semibold text-slate-700 dark:text-slate-300 block mb-1">
                    Justifikasi Kebutuhan Pengadaan:
                  </span>
                  <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    {{ selectedRequest.general_notes }}
                  </p>
                </div>

                <!-- Keputusan Otorisasi Komisaris -->
                <div
                  v-if="selectedRequest.status === 'pending' && isKomisaris"
                  class="rounded-xl border border-indigo-200 dark:border-indigo-800/80 bg-indigo-50/50 dark:bg-indigo-950/30 p-4 space-y-3"
                >
                  <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-200 font-bold text-xs">
                    <ShieldCheck class="w-4 h-4 text-[#4F46E5]" />
                    <span>Otorisasi Keputusan Komisaris</span>
                  </div>

                  <div>
                    <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                      Catatan / Arahan Komisaris <span class="text-slate-400 font-normal">(Wajib jika menolak)</span>
                    </label>
                    <textarea
                      v-model="reviewNotesInput"
                      rows="2"
                      placeholder="Tuliskan arahan batas kuantitas, instruksi negosiasi supplier, atau alasan penolakan..."
                      class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-[#4F46E5] focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/15"
                    ></textarea>
                  </div>

                  <div class="flex items-center justify-end gap-2.5 pt-1">
                    <button
                      type="button"
                      @click="handleReject(selectedRequest)"
                      class="h-8 px-3 rounded-lg border border-rose-300 text-rose-600 dark:border-rose-800 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950 text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5"
                    >
                      <XCircle class="w-3.5 h-3.5" />
                      <span>Tolak Pengajuan</span>
                    </button>
                    <button
                      type="button"
                      @click="handleApprove(selectedRequest)"
                      class="h-8 px-3.5 rounded-lg bg-[#10B981] hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer inline-flex items-center gap-1.5"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5" />
                      <span>Setujui Pengajuan</span>
                    </button>
                  </div>
                </div>

                <!-- Catatan Evaluasi Pasca Keputusan -->
                <div
                  v-else-if="selectedRequest.review_notes"
                  :class="[
                    'text-xs p-3.5 rounded-xl border',
                    selectedRequest.status === 'approved'
                      ? 'bg-emerald-50/70 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300'
                      : 'bg-rose-50/70 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300',
                  ]"
                >
                  <div class="flex items-center gap-1.5 font-bold mb-1">
                    <CheckCircle2 v-if="selectedRequest.status === 'approved'" class="w-4 h-4 text-[#10B981]" />
                    <AlertTriangle v-else class="w-4 h-4 text-rose-600" />
                    <span>Catatan Keputusan Komisaris:</span>
                  </div>
                  <p class="leading-relaxed">{{ selectedRequest.review_notes }}</p>
                </div>

                <!-- Multi-Item Detail Table -->
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                      Rincian Barang Yang Diajukan ({{ selectedRequest.details?.length || 0 }} SKU)
                    </h4>
                    <span class="text-[11px] text-slate-400">Rekomendasi Holt DES</span>
                  </div>

                  <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <table class="w-full text-xs text-left">
                      <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 font-semibold uppercase text-[10px]">
                        <tr>
                          <th class="px-3 py-2.5">Produk</th>
                          <th class="px-3 py-2.5 text-right">Stok / Safety</th>
                          <th class="px-3 py-2.5 text-right">Saran DES</th>
                          <th class="px-3 py-2.5 text-right">Diminta</th>
                          <th class="px-3 py-2.5 text-right">Disetujui</th>
                          <th class="px-3 py-2.5 text-right">Harga Satuan</th>
                          <th class="px-3 py-2.5 text-right">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr
                          v-for="item in selectedRequest.details"
                          :key="item.id"
                          class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
                        >
                          <td class="px-3 py-2.5">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ item.product?.name }}</p>
                            <span class="font-mono text-[10px] text-slate-400">{{ item.product?.sku }}</span>
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums text-slate-600 dark:text-slate-300">
                            <span :class="item.current_stock < item.safety_stock ? 'text-rose-600 font-bold' : ''">
                              {{ item.current_stock }}
                            </span>
                            <span class="text-slate-400"> / {{ item.safety_stock }} {{ item.product?.unit }}</span>
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-[#2563EB] dark:text-blue-400">
                            {{ item.suggested_quantity }} {{ item.product?.unit }}
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                            {{ item.requested_quantity }} {{ item.product?.unit }}
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums font-bold" :class="item.approved_quantity !== null ? 'text-[#10B981]' : 'text-slate-400'">
                            {{ item.approved_quantity !== null ? item.approved_quantity + ' ' + (item.product?.unit || '') : '-' }}
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums text-slate-600 dark:text-slate-300">
                            {{ formatRupiah(item.estimated_unit_price) }}
                          </td>
                          <td class="px-3 py-2.5 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                            {{ formatRupiah(item.subtotal) }}
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="bg-slate-50/80 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 font-semibold text-slate-700 dark:text-slate-300">
                        <tr>
                          <td colspan="6" class="px-3 py-2.5 text-right text-xs">
                            Total Estimasi Anggaran Pengadaan:
                          </td>
                          <td class="px-3 py-2.5 text-right font-bold text-[#2563EB] dark:text-blue-400 tabular-nums">
                            {{ formatRupiah(selectedRequest.total_estimated_cost) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="p-4 bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <Link
                  :href="route('analytics.index')"
                  class="text-xs text-[#2563EB] hover:underline font-semibold inline-flex items-center gap-1"
                >
                  <TrendingUp class="w-3.5 h-3.5" />
                  <span>Buka Analitik Prediksi DES</span>
                </Link>

                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="closeDetail"
                    class="rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                  >
                    Tutup
                  </button>

                  <Link
                    v-if="selectedRequest.status === 'approved'"
                    :href="route('transactions.inbound.create')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#10B981] hover:bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition-colors cursor-pointer"
                  >
                    <PackagePlus class="w-3.5 h-3.5" />
                    <span>Terbitkan Inbound Gudang</span>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AuthenticatedLayout>
</template>
