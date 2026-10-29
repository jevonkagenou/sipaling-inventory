<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import {
  ArrowLeft,
  Plus,
  Trash2,
  Boxes,
  Calendar,
  Building2,
  FileText,
  AlertCircle,
  AlertTriangle,
  TrendingUp,
  CheckCircle2,
  Search,
  ArrowRight,
  PackagePlus,
  Coins,
  FileSpreadsheet,
  Upload,
  Download,
  ShieldAlert,
  Loader2,
  ClipboardCheck,
} from 'lucide-vue-next'

export interface Category {
  id: string
  name: string
}

export interface ProductItem {
  id: string
  category_id?: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
  current_stock: number
  minimum_stock: number
  category?: Category
}

interface InboundItemForm {
  product_id: string
  quantity: number
  unit_price: number | string
  notes: string
  searchQuery?: string
  isDropdownOpen?: boolean
}

export interface ApprovedRestockDetail {
  product_id: string
  quantity: number
  unit_price: number
  notes?: string
  sku: string
  name: string
  unit: string
  current_stock: number
}

export interface ApprovedRestock {
  id: string
  request_no: string
  created_at: string
  total_estimated_cost: number
  review_notes?: string
  requester_name?: string
  items: ApprovedRestockDetail[]
}

const props = defineProps<{
  products: ProductItem[]
  categories: Category[]
  generatedRef: string
  approvedRestocks?: ApprovedRestock[]
  preselectedRestockId?: string
}>()

const selectedRestockId = ref<string>(props.preselectedRestockId || '')

const activeRestock = computed(() => {
  if (!selectedRestockId.value || !props.approvedRestocks) return null
  return props.approvedRestocks.find((r) => r.id === selectedRestockId.value) || null
})

function applyRestockRequest(restockId: string) {
  selectedRestockId.value = restockId
  if (!restockId) {
    clearRestockSelection()
    return
  }

  const restock = props.approvedRestocks?.find((r) => r.id === restockId)
  if (!restock) return

  // Set catatan bukti penerimaan otomatis
  form.notes = `Penerimaan pengadaan restock berkas ${restock.request_no} (Disetujui Komisaris).`

  // Populasi seluruh item produk yang disetujui
  if (restock.items && restock.items.length > 0) {
    form.items = restock.items.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      notes: item.notes || `Ref: ${restock.request_no}`,
      searchQuery: `${item.sku} - ${item.name}`,
      isDropdownOpen: false,
    }))
  }
}

function clearRestockSelection() {
  selectedRestockId.value = ''
  form.notes = ''
  form.items = [
    {
      product_id: '',
      quantity: 1,
      unit_price: 0,
      notes: '',
      searchQuery: '',
      isDropdownOpen: false,
    },
  ]
}

const form = useForm({
  reference_no: props.generatedRef,
  transaction_date: new Date().toISOString().slice(0, 16), // YYYY-MM-DDTHH:mm
  party_name: '',
  notes: '',
  items: [
    {
      product_id: '',
      quantity: 1,
      unit_price: 0,
      notes: '',
      searchQuery: '',
      isDropdownOpen: false,
    },
  ] as InboundItemForm[],
})

function formatRupiah(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num || 0)
}

function addItem() {
  form.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0,
    notes: '',
    searchQuery: '',
    isDropdownOpen: false,
  })
}

function removeItem(index: number) {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  }
}

function selectProduct(index: number, product: ProductItem) {
  form.items[index].product_id = product.id
  form.items[index].unit_price = product.unit_price
  form.items[index].searchQuery = `${product.sku} - ${product.name}`
  form.items[index].isDropdownOpen = false
}

function getSelectedProduct(productId: string): ProductItem | undefined {
  return props.products.find((p) => p.id === productId)
}

function filteredProducts(query?: string) {
  if (!query) return props.products.slice(0, 50)
  const q = query.toLowerCase()
  return props.products
    .filter((p) => p.sku.toLowerCase().includes(q) || p.name.toLowerCase().includes(q))
    .slice(0, 50)
}

function handleDocumentClick(e: MouseEvent) {
  const target = e.target as HTMLElement
  if (!target.closest('.product-search-container')) {
    form.items.forEach((item) => {
      item.isDropdownOpen = false
    })
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  if (props.preselectedRestockId) {
    applyRestockRequest(props.preselectedRestockId)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
})

const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0)
})

const totalValue = computed(() => {
  return form.items.reduce((sum, item) => {
    const qty = Number(item.quantity) || 0
    const price = Number(item.unit_price) || 0
    return sum + qty * price
  }, 0)
})

const isSubmitting = ref(false)
const isConfirmModalOpen = ref(false)
const validationError = ref<string | null>(null)

function promptSubmit() {
  validationError.value = null
  if (!form.reference_no.trim()) {
    validationError.value = 'Nomor referensi / bukti surat jalan wajib diisi.'
    return
  }
  if (!form.transaction_date) {
    validationError.value = 'Tanggal transaksi penerimaan barang wajib diisi.'
    return
  }
  for (let i = 0; i < form.items.length; i++) {
    const item = form.items[i]
    if (!item.product_id) {
      validationError.value = `Baris produk #${i + 1}: Silakan pilih SKU produk dari katalog.`
      return
    }
    if (!item.quantity || Number(item.quantity) <= 0) {
      validationError.value = `Baris produk #${i + 1}: Kuantitas barang masuk minimal bernilai 1 unit.`
      return
    }
  }
  isConfirmModalOpen.value = true
}

function handleConfirmSubmit() {
  isConfirmModalOpen.value = false
  submit()
}

function submit() {
  isSubmitting.value = true
  form.post(route('transactions.inbound.store'), {
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

// Fitur Impor CSV Inbound
const isCsvModalOpen = ref(false)
const csvInputRef = ref<HTMLInputElement | null>(null)
const csvFile = ref<File | null>(null)
const csvErrorMessage = ref<string | null>(null)
const csvParsedRows = ref<Array<{ product: ProductItem; quantity: number; unit_price: number; notes: string }>>([])
const isParsingCsv = ref(false)

function openCsvModal() {
  csvFile.value = null
  csvErrorMessage.value = null
  csvParsedRows.value = []
  if (csvInputRef.value) {
    csvInputRef.value.value = ''
  }
  isCsvModalOpen.value = true
}

function downloadCsvTemplate() {
  const sample1 = props.products[0] || { sku: 'PRD-001', unit_price: 25000 }
  const sample2 = props.products[1] || { sku: 'PRD-002', unit_price: 50000 }
  const csvContent = `sku,quantity,unit_price,notes\n${sample1.sku},10,${sample1.unit_price},Penerimaan supplier batch 1\n${sample2.sku},25,${sample2.unit_price},Restock gudang utama\n`
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', 'template_inbound_sipaling.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

function handleCsvFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    parseCsv(target.files[0])
  }
}

function handleCsvDrop(e: DragEvent) {
  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    parseCsv(e.dataTransfer.files[0])
  }
}

function parseCsv(file: File) {
  csvFile.value = file
  csvErrorMessage.value = null
  csvParsedRows.value = []
  isParsingCsv.value = true

  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const text = e.target?.result as string
      if (!text) {
        csvErrorMessage.value = 'Berkas CSV kosong.'
        isParsingCsv.value = false
        return
      }

      const lines = text.split(/\r?\n/).map((l) => l.trim()).filter((l) => l.length > 0)
      if (lines.length < 2) {
        csvErrorMessage.value = 'Berkas CSV harus memiliki baris header dan minimal 1 baris data.'
        isParsingCsv.value = false
        return
      }

      const headerLine = lines[0]
      const sep = headerLine.includes(';') ? ';' : ','
      const rawHeaders = headerLine.split(sep).map((h) => h.trim().toLowerCase().replace(/^["']|["']$/g, ''))

      const skuIdx = rawHeaders.findIndex((h) => h === 'sku' || h === 'kode' || h === 'kode_barang')
      const qtyIdx = rawHeaders.findIndex((h) => h === 'quantity' || h === 'qty' || h === 'jumlah' || h === 'kuantitas')
      const priceIdx = rawHeaders.findIndex((h) => h === 'unit_price' || h === 'price' || h === 'harga' || h === 'harga_satuan')
      const notesIdx = rawHeaders.findIndex((h) => h === 'notes' || h === 'catatan' || h === 'keterangan')

      if (skuIdx === -1 || qtyIdx === -1) {
        csvErrorMessage.value = 'Header CSV harus memiliki kolom "sku" dan "quantity" (atau "qty").'
        isParsingCsv.value = false
        return
      }

      const parsed: Array<{ product: ProductItem; quantity: number; unit_price: number; notes: string }> = []
      const errors: string[] = []

      for (let i = 1; i < lines.length; i++) {
        const row = lines[i].split(sep).map((col) => col.trim().replace(/^["']|["']$/g, ''))
        const skuVal = row[skuIdx]?.trim()
        if (!skuVal) continue

        const product = props.products.find((p) => p.sku.toLowerCase() === skuVal.toLowerCase())
        if (!product) {
          errors.push(`Baris ${i + 1}: SKU "${skuVal}" tidak terdaftar di katalog produk.`)
          continue
        }

        const qtyVal = parseInt(row[qtyIdx], 10)
        if (isNaN(qtyVal) || qtyVal <= 0) {
          errors.push(`Baris ${i + 1}: Kuantitas untuk SKU "${skuVal}" tidak valid (harus angka > 0).`)
          continue
        }

        let priceVal = priceIdx !== -1 && row[priceIdx] ? parseFloat(row[priceIdx]) : Number(product.unit_price) || 0
        if (isNaN(priceVal) || priceVal < 0) {
          priceVal = Number(product.unit_price) || 0
        }

        const notesVal = notesIdx !== -1 && row[notesIdx] ? row[notesIdx] : ''

        parsed.push({
          product,
          quantity: qtyVal,
          unit_price: priceVal,
          notes: notesVal,
        })
      }

      if (errors.length > 0) {
        csvErrorMessage.value = errors.slice(0, 3).join(' ') + (errors.length > 3 ? ` ...dan ${errors.length - 3} kesalahan lainnya.` : '')
      }

      if (parsed.length === 0 && !csvErrorMessage.value) {
        csvErrorMessage.value = 'Tidak ada baris data produk valid yang ditemukan dalam berkas.'
      }

      csvParsedRows.value = parsed
    } catch (err: any) {
      csvErrorMessage.value = 'Gagal membaca berkas CSV: ' + (err?.message || 'Format tidak valid')
    } finally {
      isParsingCsv.value = false
    }
  }
  reader.readAsText(file)
}

function applyCsvToForm() {
  if (csvParsedRows.value.length === 0) return

  form.items = csvParsedRows.value.map((item) => ({
    product_id: item.product.id,
    quantity: item.quantity,
    unit_price: item.unit_price,
    notes: item.notes,
    searchQuery: `${item.product.sku} - ${item.product.name}`,
    isDropdownOpen: false,
  }))

  isCsvModalOpen.value = false
}
</script>

<template>
  <Head title="Pencatatan Barang Masuk (Inbound)" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Operasional Gudang</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Catat Barang Masuk
        </h1>
      </div>
    </template>

    <div class="mx-auto max-w-6xl space-y-6 pb-16">
      <!-- Header Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
          <Link
            :href="route('transactions.index')"
            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-all hover:bg-slate-50 hover:text-slate-900 shadow-2xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
            title="Kembali ke Riwayat Mutasi"
          >
            <ArrowLeft class="h-4 w-4" />
          </Link>
          <div>
            <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
              Catat Barang Masuk
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Penerimaan stok dari supplier, hasil restock, atau pengembalian barang ke gudang.
            </p>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <form @submit.prevent="promptSubmit" class="space-y-6">
        <!-- Error Banner jika ada error validasi -->
        <div
          v-if="validationError || Object.keys(form.errors).length > 0"
          class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300 flex items-start gap-3"
        >
          <AlertCircle class="h-5 w-5 shrink-0 text-rose-500 mt-0.5" />
          <div class="text-xs sm:text-sm space-y-1">
            <p class="font-bold">Terdapat kesalahan pada formulir transaksi:</p>
            <p v-if="validationError" class="font-medium text-rose-600 dark:text-rose-400">{{ validationError }}</p>
            <ul v-if="Object.keys(form.errors).length > 0" class="list-disc pl-5 space-y-0.5">
              <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
            </ul>
          </div>
        </div>

        <!-- Section 1: Informasi Dokumen & Pengirim -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
              <FileText class="h-4 w-4 text-[#2563EB]" />
              1. Informasi Bukti Penerimaan
            </h3>
            <span class="text-xs text-slate-400">Pastikan nomor surat jalan atau referensi fisik tercatat</span>
          </div>

          <!-- Opsi Tarik dari Berkas Pengadaan Disetujui (Restock Integration) -->
          <div
            v-if="approvedRestocks && approvedRestocks.length > 0"
            class="rounded-xl border border-blue-200/70 bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-white dark:from-slate-900 dark:via-blue-950/20 dark:to-slate-900 p-3.5 dark:border-blue-900/60 transition-all"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#2563EB]/10 text-[#2563EB] dark:bg-blue-500/20 dark:text-blue-400 mt-0.5">
                  <ClipboardCheck class="h-4 w-4" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">
                      Tarik dari Berkas Pengadaan Disetujui (Restock)
                    </h4>
                    <span class="rounded-full bg-blue-100 dark:bg-blue-950/80 px-2 py-0.5 text-[10px] font-semibold text-[#2563EB] dark:text-blue-300">
                      {{ approvedRestocks.length }} Berkas Siap
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Pilih nomor pengadaan untuk mengisi otomatis seluruh item dan kuantitas hasil persetujuan Komisaris.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                  :value="selectedRestockId"
                  @change="applyRestockRequest(($event.target as HTMLSelectElement).value)"
                  class="h-9 w-full sm:w-72 rounded-xl border border-blue-200 bg-white px-3 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 dark:border-blue-900 dark:bg-slate-800 dark:text-slate-200 cursor-pointer"
                >
                  <option value="">-- Input Manual (Bukan dari Restock) --</option>
                  <option
                    v-for="r in approvedRestocks"
                    :key="r.id"
                    :value="r.id"
                  >
                    {{ r.request_no }} ({{ r.items.length }} SKU) • {{ r.requester_name || 'Manajer' }}
                  </option>
                </select>

                <button
                  v-if="selectedRestockId"
                  type="button"
                  @click="clearRestockSelection"
                  class="h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shrink-0 shadow-2xs cursor-pointer"
                  title="Batalkan pilihan berkas dan reset form"
                >
                  Reset
                </button>
              </div>
            </div>

            <!-- Detail berkas aktif yang dimuat -->
            <div
              v-if="activeRestock"
              class="mt-3 pt-2.5 border-t border-blue-100 dark:border-blue-900/50 flex flex-wrap items-center justify-between gap-2 text-xs"
            >
              <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-medium text-[11px]">
                <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-[#10B981]" />
                <span>
                  Memuat <strong>{{ activeRestock.items.length }} SKU</strong> dari berkas <strong>{{ activeRestock.request_no }}</strong> disetujui Komisaris.
                </span>
              </div>
              <div v-if="activeRestock.review_notes" class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                &ldquo;{{ activeRestock.review_notes }}&rdquo;
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- No Referensi -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                No. Referensi / Surat Jalan <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.reference_no"
                  type="text"
                  required
                  placeholder="Misal: TRX-IN-20261003-001"
                  class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-1.5 text-xs sm:text-sm font-mono font-medium text-slate-900 focus:border-[#2563EB] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800/60 dark:text-white dark:focus:bg-slate-800"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Nomor unik transaksi bukti mutasi.</span>
            </div>

            <!-- Tanggal Transaksi -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                Tanggal & Waktu Masuk <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.transaction_date"
                  type="datetime-local"
                  required
                  class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Waktu aktual penerimaan barang.</span>
            </div>

            <!-- Nama Pemasok / Vendor -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                Pemasok / Vendor / Sumber Barang
              </label>
              <div class="relative">
                <input
                  v-model="form.party_name"
                  type="text"
                  placeholder="Misal: PT Sumber Makmur Abadi"
                  class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Opsional - asal muatan barang.</span>
            </div>
          </div>

          <!-- Catatan Tambahan -->
          <div class="mt-4">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Catatan Operasional / Keterangan
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Catatan kondisi penerimaan barang, no kontainer, atau keterangan batch..."
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs sm:text-sm text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Section 2: Daftar Item Barang Masuk -->
        <div class="relative z-20 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <Boxes class="h-4 w-4 text-[#10B981]" />
                2. Rincian Barang Masuk
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pilih SKU produk atau unggah berkas CSV untuk mencatat penerimaan barang.
              </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button
                type="button"
                @click="openCsvModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 px-3 py-2 text-xs font-semibold text-slate-700 transition-colors border border-slate-200/80 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700/80 cursor-pointer shadow-2xs"
              >
                <FileSpreadsheet class="h-3.5 w-3.5 text-[#2563EB]" />
                Impor dari CSV
              </button>
              <button
                type="button"
                @click="addItem"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-[#2563EB] hover:bg-blue-100 transition-colors border border-blue-200/60 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800/60 cursor-pointer shadow-2xs"
              >
                <Plus class="h-3.5 w-3.5" />
                Tambah Baris Produk
              </button>
            </div>
          </div>

          <!-- Items Row -->
          <div class="space-y-3 pt-2">
            <div
              v-for="(item, index) in form.items"
              :key="index"
              class="rounded-2xl border border-slate-200/90 bg-slate-50/50 p-4 transition-all dark:border-slate-800 dark:bg-slate-900/40"
              :class="item.isDropdownOpen ? 'relative z-30 ring-2 ring-[#2563EB]/20 shadow-md' : 'relative z-10'"
            >
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 items-start">
                <!-- Dropdown Search Produk (Col 5) -->
                <div class="sm:col-span-5 relative product-search-container">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Produk / SKU #{{ index + 1 }} <span class="text-rose-500">*</span>
                  </label>
                  
                  <div class="relative">
                    <input
                      type="text"
                      v-model="item.searchQuery"
                      @focus="item.isDropdownOpen = true"
                      @click="item.isDropdownOpen = true"
                      placeholder="Cari SKU atau Nama Produk..."
                      class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                    />
                    <Search class="absolute right-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
                  </div>

                  <!-- Dropdown Search Result Popup -->
                  <div
                    v-if="item.isDropdownOpen"
                    class="absolute left-0 right-0 top-full z-50 mt-1 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-800 dark:bg-slate-800 custom-scrollbar ring-1 ring-black/5 dark:ring-white/10"
                  >
                    <div
                      v-if="filteredProducts(item.searchQuery).length === 0"
                      class="p-3 text-center text-xs text-slate-400"
                    >
                      Produk tidak ditemukan.
                    </div>
                    <button
                      v-for="prod in filteredProducts(item.searchQuery)"
                      :key="prod.id"
                      type="button"
                      @click="selectProduct(index, prod)"
                      class="w-full text-left px-3 py-2 rounded-lg text-xs hover:bg-blue-50 dark:hover:bg-slate-700/80 flex items-center justify-between transition-colors cursor-pointer"
                    >
                      <div class="min-w-0 pr-2">
                        <p class="font-bold text-slate-900 dark:text-white truncate">
                          {{ prod.name }}
                        </p>
                        <p class="text-[11px] font-mono text-slate-400">
                          SKU: {{ prod.sku }} • {{ prod.category?.name || 'Umum' }}
                        </p>
                      </div>
                      <div class="text-right shrink-0">
                        <span class="inline-block px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                          Stok: {{ prod.current_stock }} {{ prod.unit }}
                        </span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Jumlah Masuk (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Jumlah Masuk <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="1"
                    required
                    class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-bold text-[#10B981] focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-emerald-400"
                  />
                </div>

                <!-- Estimasi Harga Satuan (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Harga Beli / Unit
                  </label>
                  <input
                    v-model.number="item.unit_price"
                    type="number"
                    step="any"
                    min="0"
                    placeholder="Rp"
                    class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <!-- Catatan Item (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Keterangan Item
                  </label>
                  <input
                    v-model="item.notes"
                    type="text"
                    placeholder="Batch / Kadaluarsa"
                    class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <!-- Tombol Hapus Row (Col 1) -->
                <div class="sm:col-span-1 flex justify-end sm:pt-6">
                  <button
                    type="button"
                    :disabled="form.items.length <= 1"
                    @click="removeItem(index)"
                    :class="[
                      'h-9 w-9 rounded-xl border flex items-center justify-center transition-colors cursor-pointer',
                      form.items.length <= 1
                        ? 'opacity-40 cursor-not-allowed border-slate-200 text-slate-400 dark:border-slate-800'
                        : 'border-rose-200 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:border-rose-900/50 dark:hover:bg-rose-950/40',
                    ]"
                    title="Hapus baris"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- Realtime Stock Impact Preview Banner -->
              <div
                v-if="getSelectedProduct(item.product_id)"
                class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/80 dark:bg-slate-800/80 px-3 py-2 border border-slate-200/60 dark:border-slate-700/60 text-xs"
              >
                <div class="flex items-center gap-2">
                  <span class="font-medium text-slate-500 dark:text-slate-400">Dampak Stok:</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-300">
                    Stok Awal: <strong class="text-slate-900 dark:text-white">{{ getSelectedProduct(item.product_id)?.current_stock }}</strong>
                  </span>
                  <span class="text-emerald-500 font-bold">+ {{ item.quantity || 0 }}</span>
                  <ArrowRight class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span class="font-semibold text-[#10B981]">
                    Estimasi Stok Baru: {{ (getSelectedProduct(item.product_id)?.current_stock || 0) + (Number(item.quantity) || 0) }} {{ getSelectedProduct(item.product_id)?.unit }}
                  </span>
                </div>
                <div class="text-right text-[11px] text-slate-400">
                  Subtotal: <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ formatRupiah((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: Ringkasan & Aksi Submit -->
        <div class="relative z-10 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <!-- Summary Stats Mini Cards (Harmonious & Professional) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full sm:w-auto">
              <!-- Item 1: Total SKU -->
              <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                <div class="h-8 w-8 rounded-lg bg-slate-200/70 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 flex items-center justify-center shrink-0">
                  <Boxes class="h-4 w-4" />
                </div>
                <div>
                  <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Item SKU</span>
                  <span class="text-sm font-bold text-slate-900 dark:text-white tabular-nums">
                    {{ form.items.length }} <span class="text-xs font-normal text-slate-500">Barang</span>
                  </span>
                </div>
              </div>

              <!-- Item 2: Kuantitas Masuk -->
              <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-emerald-200/60 dark:border-emerald-900/40 bg-emerald-50/50 dark:bg-emerald-950/20">
                <div class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-[#10B981] flex items-center justify-center shrink-0">
                  <PackagePlus class="h-4 w-4" />
                </div>
                <div>
                  <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-700/80 dark:text-emerald-400/80">Kuantitas Masuk</span>
                  <span class="text-sm font-bold text-[#10B981] tabular-nums">
                    +{{ totalQuantity }} <span class="text-xs font-normal opacity-80">Unit</span>
                  </span>
                </div>
              </div>

              <!-- Item 3: Estimasi Nilai Total -->
              <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                <div class="h-8 w-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shrink-0">
                  <Coins class="h-4 w-4" />
                </div>
                <div>
                  <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Estimasi Nilai Total</span>
                  <span class="text-sm font-bold text-slate-900 dark:text-white tabular-nums font-mono whitespace-nowrap">
                    {{ formatRupiah(totalValue) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 self-end sm:self-auto">
              <Link
                :href="route('transactions.index')"
                class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-all inline-flex items-center justify-center cursor-pointer"
              >
                Batal
              </Link>
              <button
                type="submit"
                :disabled="form.processing || isSubmitting"
                class="h-9 px-4 rounded-xl bg-[#10B981] hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 disabled:opacity-50"
              >
                <CheckCircle2 class="h-4 w-4" />
                {{ form.processing ? 'Menyimpan & Update Stok...' : 'Simpan Barang Masuk' }}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal Konfirmasi Mutasi Permanen -->
    <Dialog :open="isConfirmModalOpen" @update:open="isConfirmModalOpen = $event">
      <DialogContent class="sm:max-w-lg bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden shadow-2xl">
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-2xs">
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
                Konfirmasi Barang Masuk
              </DialogTitle>
              <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pastikan data yang diinput telah sesuai dengan fisik barang yang diterima.
              </DialogDescription>
            </div>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <!-- Critical Immutability Warning Alert Banner -->
          <div class="p-4 rounded-xl border border-amber-300/80 bg-amber-50/80 dark:border-amber-900/60 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 text-xs space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-amber-800 dark:text-amber-300">
              <ShieldAlert class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
              <span>PERINGATAN INTEGRITAS AUDIT LOG</span>
            </div>
            <p class="leading-relaxed">
              Transaksi mutasi stok ini bersifat <strong>permanen dan TIDAK BISA DIUBAH ATAU DIHAPUS</strong> setelah disimpan. Seluruh kuantitas barang masuk akan langsung menambah stok fisik gudang dan dicatat dalam riwayat audit forensik.
            </p>
          </div>

          <!-- Transaction Summary Card -->
          <div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 p-3.5 space-y-2 text-xs">
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">No. Referensi:</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ form.reference_no }}</span>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">Pengirim / Supplier:</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ form.party_name || '-' }}</span>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">Waktu Transaksi:</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ form.transaction_date }}</span>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">Total Item SKU:</span>
              <span class="font-bold text-slate-900 dark:text-white">{{ form.items.length }} Barang</span>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">Total Kuantitas Masuk:</span>
              <span class="font-bold text-[#10B981]">+{{ totalQuantity }} Unit</span>
            </div>
            <div class="flex justify-between items-center pt-1">
              <span class="text-slate-500 dark:text-slate-400">Estimasi Nilai Total:</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ formatRupiah(totalValue) }}</span>
            </div>
          </div>

          <p class="text-center text-xs text-slate-500 dark:text-slate-400">
            Apakah Anda yakin ingin memproses dan membukukan mutasi masuk ini sekarang?
          </p>
        </div>

        <DialogFooter class="px-6 py-3.5 bg-slate-50/80 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 flex flex-row items-center justify-end gap-2.5">
          <Button
            type="button"
            variant="outline"
            class="h-9 px-4 rounded-xl text-xs font-semibold border-slate-200 dark:border-slate-800"
            @click="isConfirmModalOpen = false"
          >
            Batal / Periksa Kembali
          </Button>
          <Button
            type="button"
            class="h-9 px-4 rounded-xl text-xs font-semibold bg-[#10B981] hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1.5"
            :disabled="form.processing || isSubmitting"
            @click="handleConfirmSubmit"
          >
            <CheckCircle2 class="h-3.5 w-3.5" />
            {{ form.processing ? 'Menyimpan...' : 'Ya, Konfirmasi & Simpan' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Modal Impor CSV Inbound -->
    <Dialog :open="isCsvModalOpen" @update:open="isCsvModalOpen = $event">
      <DialogContent class="sm:max-w-xl bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden shadow-2xl">
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shrink-0 shadow-2xs">
              <FileSpreadsheet class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
                Impor Rincian Barang Masuk dari CSV
              </DialogTitle>
              <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Unggah berkas CSV untuk mengisi daftar produk masuk secara otomatis.
              </DialogDescription>
            </div>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <!-- Alert Peringatan Tidak Bisa Diedit/Dihapus -->
          <div class="p-4 rounded-xl border border-amber-300/80 bg-amber-50/80 dark:border-amber-900/60 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 text-xs space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-amber-800 dark:text-amber-300">
              <AlertTriangle class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
              <span>PERINGATAN IMPOR TRANSAKSI</span>
            </div>
            <p class="leading-relaxed">
              Data dari CSV akan dimasukkan ke dalam draf transaksi barang masuk. Perhatikan bahwa mutasi stok yang disimpan bersifat <strong>permanen dan TIDAK DAPAT DIHAPUS atau DIUBAH</strong>. Pastikan data SKU dan kuantitas fisik sudah akurat sebelum disimpan.
            </p>
          </div>

          <!-- Download Template Banner -->
          <div class="flex items-center justify-between p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-950/20">
            <div class="flex items-center gap-2.5 min-w-0">
              <FileText class="h-4 w-4 text-[#2563EB] shrink-0" />
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                  Format Berkas CSV Inbound
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                  Kolom: <code>sku,quantity,unit_price,notes</code>
                </p>
              </div>
            </div>
            <button
              type="button"
              @click="downloadCsvTemplate"
              class="h-8 px-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-900 text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors shadow-2xs inline-flex items-center gap-1.5 shrink-0 cursor-pointer"
            >
              <Download class="h-3.5 w-3.5" />
              Unduh Template
            </button>
          </div>

          <!-- Upload Dropzone -->
          <div
            class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30"
            @click="csvInputRef?.click()"
            @dragover.prevent
            @drop.prevent="handleCsvDrop"
          >
            <input
              ref="csvInputRef"
              type="file"
              accept=".csv,text/csv"
              class="hidden"
              @change="handleCsvFileSelect"
            />
            <div class="flex flex-col items-center justify-center gap-2">
              <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                <Upload class="h-5 w-5" />
              </div>
              <div>
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                  <span v-if="csvFile">{{ csvFile.name }} ({{ (csvFile.size / 1024).toFixed(1) }} KB)</span>
                  <span v-else>Klik atau seret berkas CSV ke area ini</span>
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Mendukung berkas CSV bertanda koma atau titik koma</p>
              </div>
            </div>
          </div>

          <!-- Error Alert jika parse gagal -->
          <div v-if="csvErrorMessage" class="flex items-start gap-2.5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs">
            <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-rose-600" />
            <p class="leading-relaxed">{{ csvErrorMessage }}</p>
          </div>

          <!-- Preview Parsed Items -->
          <div v-if="csvParsedRows.length > 0" class="space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-slate-700 dark:text-slate-300">
                Preview Data Terbaca ({{ csvParsedRows.length }} Item):
              </span>
              <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                Total Kuantitas: +{{ csvParsedRows.reduce((sum, r) => sum + r.quantity, 0) }} Unit
              </span>
            </div>
            <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              <div
                v-for="(row, idx) in csvParsedRows.slice(0, 10)"
                :key="idx"
                class="p-2.5 flex items-center justify-between bg-white dark:bg-slate-900"
              >
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ row.product.name }}</p>
                  <p class="text-[11px] font-mono text-slate-400">SKU: {{ row.product.sku }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-emerald-600 dark:text-emerald-400">+{{ row.quantity }} {{ row.product.unit }}</p>
                  <p class="text-[11px] text-slate-400">{{ formatRupiah(row.unit_price) }}</p>
                </div>
              </div>
              <div v-if="csvParsedRows.length > 10" class="p-2 text-center text-[11px] text-slate-400 bg-slate-50 dark:bg-slate-900/80">
                ...dan {{ csvParsedRows.length - 10 }} item lainnya
              </div>
            </div>
          </div>
        </div>

        <DialogFooter class="px-6 py-3.5 bg-slate-50/80 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 flex flex-row items-center justify-end gap-2.5">
          <Button
            type="button"
            variant="outline"
            class="h-9 px-4 rounded-xl text-xs font-semibold border-slate-200 dark:border-slate-800"
            @click="isCsvModalOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="h-9 px-4 rounded-xl text-xs font-semibold bg-[#2563EB] hover:bg-blue-700 text-white shadow-2xs inline-flex items-center gap-1.5"
            :disabled="csvParsedRows.length === 0"
            @click="applyCsvToForm"
          >
            <CheckCircle2 class="h-3.5 w-3.5" />
            Terapkan ke Formulir ({{ csvParsedRows.length }} Baris)
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AuthenticatedLayout>
</template>
