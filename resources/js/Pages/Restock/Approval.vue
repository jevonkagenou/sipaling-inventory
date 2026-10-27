<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ClipboardCheck,
  CheckCircle2,
  XCircle,
  Clock,
  Boxes,
  TrendingUp,
  FileText,
  AlertTriangle,
  ShieldCheck,
  PackagePlus,
  X,
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
  status: 'pending' | 'approved' | 'rejected'
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
  requests?: RestockRequest[]
}>()

const page = usePage()
const pendingList = computed(() => props.requests || [])

const selectedRequest = ref<RestockRequest | null>(null)
const actionType = ref<'approve' | 'reject' | null>(null)
const reviewNotes = ref('')
const isSubmitting = ref(false)

function openModal(req: RestockRequest, type: 'approve' | 'reject') {
  selectedRequest.value = req
  actionType.value = type
  reviewNotes.value = type === 'approve'
    ? 'Disetujui penuh oleh Komisaris sesuai rekomendasi peramalan Holt DES.'
    : ''
}

function closeModal() {
  selectedRequest.value = null
  actionType.value = null
  reviewNotes.value = ''
}

function submitAction() {
  if (!selectedRequest.value || !actionType.value || isSubmitting.value) return

  if (actionType.value === 'reject' && !reviewNotes.value.trim()) {
    alert('Harap lampirkan catatan evaluasi / alasan penolakan secara jelas.')
    return
  }

  isSubmitting.value = true
  const routeName = actionType.value === 'approve' ? 'restock.approve' : 'restock.reject'

  router.post(
    route(routeName, selectedRequest.value.id),
    {
      review_notes: reviewNotes.value.trim(),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        closeModal()
        isSubmitting.value = false
      },
      onError: () => {
        isSubmitting.value = false
      },
    }
  )
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
          v-if="(page.props.flash as any)?.success"
          class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs sm:text-sm font-semibold text-emerald-800 dark:text-emerald-300 shadow-2xs backdrop-blur-md"
        >
          <div class="flex items-center gap-2.5">
            <CheckCircle2 class="h-4 w-4 shrink-0 text-[#10B981]" />
            <span>{{ (page.props.flash as any)?.success }}</span>
          </div>
        </div>
      </Transition>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-[#4F46E5]" />
            <span>Otorisasi Persetujuan Usulan Restock</span>
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Daftar pengajuan pengadaan multi-item oleh Manajer Operasional yang menunggu keputusan persetujuan Dewan Komisaris.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <Link
            :href="route('restock.index')"
            class="h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <Boxes class="w-3.5 h-3.5 text-slate-500" />
            <span>Semua Pengajuan</span>
          </Link>
          <Link
            :href="route('analytics.index')"
            class="h-9 px-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-semibold text-xs shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            <span>Tinjau Prediksi DES</span>
          </Link>
        </div>
      </div>

      <!-- Clean Empty State if no pending requests -->
      <div
        v-if="pendingList.length === 0"
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center shadow-2xs"
      >
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] dark:bg-indigo-950/60 dark:text-indigo-400 mb-4">
          <ClipboardCheck class="h-7 w-7" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          Antrean Persetujuan Bersih
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto mt-2 leading-relaxed">
          Saat ini belum ada berkas usulan restock baru yang diajukan oleh Manajer Operasional dengan status <span class="font-semibold text-amber-600 dark:text-amber-400">PENDING_APPROVAL</span>.
        </p>
        <div class="mt-6 flex justify-center gap-2.5">
          <Link
            :href="route('restock.index')"
            class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-all inline-flex items-center gap-1.5"
          >
            <Boxes class="w-3.5 h-3.5 text-slate-500" />
            Monitoring Pengajuan
          </Link>
        </div>
      </div>

      <!-- Pending List Cards -->
      <div v-else class="space-y-6">
        <div
          v-for="req in pendingList"
          :key="req.id"
          class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden"
        >
          <!-- Card Header -->
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <Clock class="h-5 w-5" />
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono text-xs font-bold text-slate-900 dark:text-white px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800">
                    {{ req.request_no }}
                  </span>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                    Menunggu Keputusan Komisaris
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Diajukan oleh <strong class="text-slate-700 dark:text-slate-200">{{ req.requester?.name || req.requested_by }}</strong> pada {{ formatDate(req.created_at) }}
                </p>
              </div>
            </div>

            <div class="flex items-baseline gap-2 self-start md:self-auto bg-blue-50/60 dark:bg-blue-950/40 px-3 py-1.5 rounded-xl border border-blue-100 dark:border-blue-900/50 whitespace-nowrap">
              <span class="text-xs text-slate-500 dark:text-slate-400">Total Estimasi:</span>
              <span class="text-base font-bold text-[#2563EB] dark:text-blue-400 tabular-nums font-mono">
                {{ formatRupiah(req.total_estimated_cost) }}
              </span>
            </div>
          </div>

          <!-- Justification if exists -->
          <div v-if="req.general_notes" class="px-5 py-3 bg-amber-50/30 dark:bg-amber-950/20 border-b border-amber-100/60 dark:border-amber-900/30 text-xs text-slate-700 dark:text-slate-300">
            <span class="font-semibold text-amber-900 dark:text-amber-300">Justifikasi Pengadaan:</span>
            {{ req.general_notes }}
          </div>

          <!-- Multi-Item Detail Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-semibold uppercase text-[10px] border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="px-3 py-2.5 whitespace-nowrap">Produk</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Stok / Safety</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Saran Holt DES</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Jumlah Diminta</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Estimasi Harga</th>
                  <th class="px-3 py-2.5 text-right whitespace-nowrap">Subtotal</th>
                  <th class="px-3 py-2.5 whitespace-nowrap">Catatan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-for="item in req.details" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                  <td class="px-3 py-2.5">
                    <p class="font-semibold text-slate-900 dark:text-white truncate max-w-[200px]" :title="item.product?.name">{{ item.product?.name }}</p>
                    <span class="font-mono text-[10px] text-slate-400">{{ item.product?.sku }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-right tabular-nums text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    <span :class="item.current_stock < item.safety_stock ? 'text-rose-600 font-bold' : ''">
                      {{ item.current_stock }}
                    </span>
                    <span class="text-slate-400"> / {{ item.safety_stock }} {{ item.product?.unit }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-[#2563EB] dark:text-blue-400 whitespace-nowrap">
                    {{ item.suggested_quantity }} {{ item.product?.unit }}
                  </td>
                  <td class="px-3 py-2.5 text-right tabular-nums font-bold text-slate-900 dark:text-white whitespace-nowrap">
                    {{ item.requested_quantity }} {{ item.product?.unit }}
                  </td>
                  <td class="px-3 py-2.5 text-right tabular-nums text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    {{ formatRupiah(item.estimated_unit_price) }}
                  </td>
                  <td class="px-3 py-2.5 text-right tabular-nums font-bold text-slate-900 dark:text-white whitespace-nowrap">
                    {{ formatRupiah(item.subtotal) }}
                  </td>
                  <td class="px-3 py-2.5 text-slate-400 text-[11px] max-w-[150px] truncate" :title="item.notes || '-'">
                    {{ item.notes || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Card Actions Footer -->
          <div class="p-4 bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
            <span class="text-xs text-slate-400">
              Total {{ req.details?.length || 0 }} SKU barang dalam usulan ini.
            </span>

            <div class="flex items-center gap-2.5">
              <button
                type="button"
                @click="openModal(req, 'reject')"
                class="h-8 px-3 rounded-lg border border-rose-300 text-rose-600 dark:border-rose-800 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950 text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5 transition-colors"
              >
                <XCircle class="w-3.5 h-3.5" />
                <span>Tolak Berkas</span>
              </button>
              <button
                type="button"
                @click="openModal(req, 'approve')"
                class="h-8 px-4 rounded-lg bg-[#10B981] hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer inline-flex items-center gap-1.5 transition-colors shadow-2xs"
              >
                <CheckCircle2 class="w-3.5 h-3.5" />
                <span>Setujui & Terbitkan Inbound</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Confirmation Modal -->
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
            v-if="selectedRequest && actionType"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="closeModal"
          >
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div
                    class="h-9 w-9 rounded-xl flex items-center justify-center"
                    :class="actionType === 'approve'
                      ? 'bg-emerald-50 text-[#10B981] dark:bg-emerald-950/60'
                      : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60'"
                  >
                    <CheckCircle2 v-if="actionType === 'approve'" class="w-5 h-5" />
                    <XCircle v-else class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                      {{ actionType === 'approve' ? 'Setujui Pengadaan Restock' : 'Tolak Pengadaan Restock' }}
                    </h3>
                    <p class="text-xs text-slate-400">Berkas: {{ selectedRequest.request_no }}</p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="closeModal"
                  class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 cursor-pointer"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <div v-if="actionType === 'approve'" class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/80 text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed">
                Persetujuan ini akan otomatis mengubah status berkas menjadi <strong class="font-bold">APPROVED</strong> dan menerbitkan draf mutasi masuk (<strong class="font-bold">Inbound</strong>) di sistem inventaris gudang.
              </div>

              <div v-else class="p-3.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/80 dark:border-rose-800/80 text-xs text-rose-800 dark:text-rose-300 leading-relaxed">
                Penolakan ini bersifat final (Anti-Bypass). Mohon berikan catatan alasan penolakan secara jelas untuk evaluasi Manajer Operasional.
              </div>

              <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Catatan Evaluasi / Arahan Komisaris
                  <span v-if="actionType === 'reject'" class="text-rose-500 font-bold">* (Wajib)</span>
                </label>
                <textarea
                  v-model="reviewNotes"
                  rows="3"
                  :placeholder="actionType === 'approve' ? 'Catatan opsional persetujuan...' : 'Tuliskan alasan penolakan / instruksi perbaikan kuantitas...'"
                  class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white focus:border-[#4F46E5] focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/15"
                ></textarea>
              </div>

              <div class="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  @click="closeModal"
                  :disabled="isSubmitting"
                  class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="button"
                  @click="submitAction"
                  :disabled="isSubmitting"
                  class="h-9 px-4 rounded-xl text-white text-xs font-bold transition-all shadow-2xs cursor-pointer inline-flex items-center gap-1.5"
                  :class="actionType === 'approve' ? 'bg-[#10B981] hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                >
                  <CheckCircle2 v-if="actionType === 'approve'" class="w-4 h-4" />
                  <XCircle v-else class="w-4 h-4" />
                  <span>{{ isSubmitting ? 'Memproses...' : (actionType === 'approve' ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan') }}</span>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AuthenticatedLayout>
</template>
