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
  CheckCircle2,
  Search,
  ShieldAlert,
  ArrowRight,
  PackageMinus,
  Coins,
  FileSpreadsheet,
  Upload,
  Download,
  Loader2,
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

interface OutboundItemForm {
  product_id: string
  quantity: number
  unit_price: number | string
  notes: string
  searchQuery?: string
  isDropdownOpen?: boolean
}

const props = defineProps<{
  products: ProductItem[]
  categories: Category[]
  generatedRef: string
}>()

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
  ] as OutboundItemForm[],
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
  if (form.items[index].quantity > product.current_stock) {
    form.items[index].quantity = Math.max(1, product.current_stock)
  }
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
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
})

// Validasi apakah ada item yang kuantitasnya melebihi stok yang ada
const hasStockExceededError = computed(() => {
  return form.items.some((item) => {
    if (!item.product_id) return false
    const prod = getSelectedProduct(item.product_id)
    if (!prod) return false
    return Number(item.quantity) > prod.current_stock || prod.current_stock <= 0
  })
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
    validationError.value = 'Nomor referensi / bukti pengeluaran barang wajib diisi.'
    return
  }
  if (!form.transaction_date) {
    validationError.value = 'Tanggal transaksi pengeluaran barang wajib diisi.'
    return
  }
  if (hasStockExceededError.value) {
    validationError.value = 'Mohon perbaiki kuantitas pengeluaran barang. Kuantitas tidak boleh melebihi stok fisik yang tersedia.'
    return
  }
  for (let i = 0; i < form.items.length; i++) {
    const item = form.items[i]
    if (!item.product_id) {
      validationError.value = `Baris produk #${i + 1}: Silakan pilih SKU produk dari katalog.`
      return
    }
    if (!item.quantity || Number(item.quantity) <= 0) {
      validationError.value = `Baris produk #${i + 1}: Kuantitas barang keluar minimal bernilai 1 unit.`
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
  if (hasStockExceededError.value) {
    validationError.value = 'Mohon perbaiki kuantitas pengeluaran barang. Kuantitas tidak boleh melebihi stok fisik yang tersedia.'
    return
  }

  isSubmitting.value = true
  form.post(route('transactions.outbound.store'), {
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

// Fitur Impor CSV Outbound
const isCsvModalOpen = ref(false)
const csvInputRef = ref<HTMLInputElement | null>(null)
const csvFile = ref<File | null>(null)
const csvErrorMessage = ref<string | null>(null)
const csvParsedRows = ref<Array<{ product: ProductItem; quantity: number; unit_price: number; notes: string; isExceeded: boolean }>>([])
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
  const sample1 = props.products[0] || { sku: 'PRD-001', current_stock: 50 }
  const sample2 = props.products[1] || { sku: 'PRD-002', current_stock: 30 }
  const csvContent = `sku,quantity,notes\n${sample1.sku},5,Pengeluaran ke Toko Cabang\n${sample2.sku},10,Distribusi pesanan pelanggan\n`
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', 'template_outbound_sipaling.csv')
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
      const notesIdx = rawHeaders.findIndex((h) => h === 'notes' || h === 'catatan' || h === 'keterangan')

      if (skuIdx === -1 || qtyIdx === -1) {
        csvErrorMessage.value = 'Header CSV harus memiliki kolom "sku" dan "quantity" (atau "qty").'
        isParsingCsv.value = false
        return
      }

      const parsed: Array<{ product: ProductItem; quantity: number; unit_price: number; notes: string; isExceeded: boolean }> = []
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

        const isExceeded = qtyVal > product.current_stock
        if (isExceeded) {
          errors.push(`Baris ${i + 1}: SKU "${skuVal}" minta ${qtyVal} unit, namun stok gudang hanya ${product.current_stock} unit.`)
        }

        const notesVal = notesIdx !== -1 && row[notesIdx] ? row[notesIdx] : ''

        parsed.push({
          product,
          quantity: qtyVal,
          unit_price: Number(product.unit_price) || 0,
          notes: notesVal,
          isExceeded,
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
  <Head title="Pencatatan Barang Keluar (Outbound)" />

  <AuthenticatedLayout>
    <!-- Header Slot (Navbar Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Operasional Gudang</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Catat Barang Keluar
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
              Catat Barang Keluar
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Pengeluaran inventaris untuk distribusi, penjualan, atau pemindahan barang dari gudang.
            </p>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <form @submit.prevent="promptSubmit" class="space-y-6">
        <!-- Error Banner jika ada error validasi backend -->
        <div
          v-if="validationError || Object.keys(form.errors).length > 0"
          class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300 flex items-start gap-3"
        >
          <AlertCircle class="h-5 w-5 shrink-0 text-rose-500 mt-0.5" />
          <div class="text-xs sm:text-sm space-y-1">
            <p class="font-bold">Terdapat kesalahan pada transaksi:</p>
            <p v-if="validationError" class="font-medium text-rose-600 dark:text-rose-400">{{ validationError }}</p>
            <ul v-if="Object.keys(form.errors).length > 0" class="list-disc pl-5 space-y-0.5">
              <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
            </ul>
          </div>
        </div>

        <!-- Warning Banner jika ada stok terlampaui -->
        <div
          v-if="hasStockExceededError"
          class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200 flex items-center gap-3 shadow-xs"
        >
          <ShieldAlert class="h-5 w-5 text-rose-600 shrink-0" />
          <p class="text-xs sm:text-sm font-semibold">
            Peringatan Validasi Stok: Ada item dengan jumlah keluar melebihi stok yang tersedia di gudang! Harap sesuaikan kuantitas.
          </p>
        </div>

        <!-- Section 1: Informasi Dokumen & Tujuan Pengeluaran -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
            <FileText class="h-4 w-4 text-[#2563EB]" />
            1. Informasi Bukti Pengeluaran
          </h3>

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
                  placeholder="Misal: TRX-OUT-20261003-001"
                  class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-1.5 text-xs sm:text-sm font-mono font-medium text-slate-900 focus:border-[#2563EB] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800/60 dark:text-white dark:focus:bg-slate-800"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Nomor unik transaksi bukti mutasi.</span>
            </div>

            <!-- Tanggal Transaksi -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                Tanggal & Waktu Keluar <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.transaction_date"
                  type="datetime-local"
                  required
                  class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Waktu aktual pengeluaran barang.</span>
            </div>

            <!-- Penerima / Tujuan -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                Penerima / Tujuan Pengiriman
              </label>
              <div class="relative">
                <input
                  v-model="form.party_name"
                  type="text"
                  placeholder="Misal: Toko Cabang Surabaya / Pelanggan"
                  class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                />
              </div>
              <span class="text-[11px] text-slate-400 mt-1 block">Tujuan alokasi barang.</span>
            </div>
          </div>

          <!-- Catatan / Alasan Pengeluaran -->
          <div class="mt-4">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Alasan Pengeluaran / Catatan
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Keterangan keperluan distribusi, no invoice, atau alasan penarikan..."
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs sm:text-sm text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Section 2: Daftar Item Barang Keluar -->
        <div class="relative z-20 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <Boxes class="h-4 w-4 text-amber-500" />
                2. Rincian Barang Keluar
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Kuantitas pengeluaran akan otomatis divalidasi terhadap stok gudang saat ini.
              </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button
                type="button"
                @click="openCsvModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 px-3 py-2 text-xs font-semibold text-slate-700 transition-colors border border-slate-200/80 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700/80 cursor-pointer shadow-2xs"
              >
                <FileSpreadsheet class="h-3.5 w-3.5 text-amber-600" />
                Impor dari CSV
              </button>
              <button
                type="button"
                @click="addItem"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors border border-amber-200/60 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/60 cursor-pointer shadow-2xs"
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
              :class="[
                'rounded-2xl border p-4 transition-all',
                item.isDropdownOpen ? 'relative z-30 ring-2 ring-amber-500/20 shadow-md' : 'relative z-10',
                getSelectedProduct(item.product_id) && Number(item.quantity) > (getSelectedProduct(item.product_id)?.current_stock || 0)
                  ? 'border-rose-300 bg-rose-50/40 dark:border-rose-800 dark:bg-rose-950/30'
                  : 'border-slate-200/90 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40',
              ]"
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
                      class="w-full text-left px-3 py-2 rounded-lg text-xs hover:bg-amber-50 dark:hover:bg-slate-700/80 flex items-center justify-between transition-colors cursor-pointer"
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
                        <span
                          :class="[
                            'inline-block px-1.5 py-0.5 rounded-md text-[10px] font-semibold',
                            prod.current_stock <= 0
                              ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                              : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
                          ]"
                        >
                          Stok: {{ prod.current_stock }} {{ prod.unit }}
                        </span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Jumlah Keluar (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Jumlah Keluar <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="1"
                    :max="getSelectedProduct(item.product_id)?.current_stock || 99999"
                    required
                    :class="[
                      'h-9 w-full rounded-xl border bg-white px-3 py-1.5 text-xs sm:text-sm font-bold focus:outline-none focus:ring-2 dark:bg-slate-800',
                      getSelectedProduct(item.product_id) && Number(item.quantity) > (getSelectedProduct(item.product_id)?.current_stock || 0)
                        ? 'border-rose-500 text-rose-600 focus:ring-rose-500/20 dark:text-rose-400'
                        : 'border-slate-200 text-amber-600 focus:border-amber-500 focus:ring-amber-500/20 dark:border-slate-700 dark:text-amber-400',
                    ]"
                  />
                </div>

                <!-- Estimasi Harga Satuan (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Harga Jual / Unit
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

                <!-- Keterangan Item (Col 2) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Keterangan Item
                  </label>
                  <input
                    v-model="item.notes"
                    type="text"
                    placeholder="Alasan / Permintaan"
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
                :class="[
                  'mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl px-3 py-2 border text-xs',
                  Number(item.quantity) > (getSelectedProduct(item.product_id)?.current_stock || 0)
                    ? 'bg-rose-100/70 border-rose-300 text-rose-800 dark:bg-rose-950/80 dark:border-rose-800 dark:text-rose-200'
                    : 'bg-white/80 dark:bg-slate-800/80 border-slate-200/60 dark:border-slate-700/60',
                ]"
              >
                <div class="flex items-center gap-2">
                  <span class="font-medium text-slate-500 dark:text-slate-400">Dampak Stok:</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-300">
                    Stok Awal: <strong class="text-slate-900 dark:text-white">{{ getSelectedProduct(item.product_id)?.current_stock }}</strong>
                  </span>
                  <span class="text-rose-500 font-bold">- {{ item.quantity || 0 }}</span>
                  <ArrowRight class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  
                  <template v-if="Number(item.quantity) > (getSelectedProduct(item.product_id)?.current_stock || 0)">
                    <span class="font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                      <AlertTriangle class="h-3.5 w-3.5" />
                      Defisit / Stok Tidak Mencukupi! (Sisa: {{ (getSelectedProduct(item.product_id)?.current_stock || 0) - (Number(item.quantity) || 0) }})
                    </span>
                  </template>
                  <template v-else>
                    <span class="font-semibold text-amber-600 dark:text-amber-400">
                      Sisa Stok: {{ (getSelectedProduct(item.product_id)?.current_stock || 0) - (Number(item.quantity) || 0) }} {{ getSelectedProduct(item.product_id)?.unit }}
                    </span>
                  </template>
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

              <!-- Item 2: Kuantitas Keluar -->
              <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-amber-200/60 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20">
                <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                  <PackageMinus class="h-4 w-4" />
                </div>
                <div>
                  <span class="block text-[10px] font-bold uppercase tracking-wider text-amber-700/80 dark:text-amber-400/80">Kuantitas Keluar</span>
                  <span class="text-sm font-bold text-amber-700 dark:text-amber-400 tabular-nums">
                    -{{ totalQuantity }} <span class="text-xs font-normal opacity-80">Unit</span>
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
                  <span class="text-sm font-bold text-slate-900 dark:text-white tabular-nums font-mono">
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
                :disabled="form.processing || isSubmitting || hasStockExceededError"
                class="h-9 px-4 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5 disabled:opacity-50"
              >
                <CheckCircle2 class="h-4 w-4" />
                {{ form.processing ? 'Menyimpan Transaksi...' : 'Simpan Barang Keluar' }}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal Konfirmasi Mutasi Permanen Outbound -->
    <Dialog :open="isConfirmModalOpen" @update:open="isConfirmModalOpen = $event">
      <DialogContent class="sm:max-w-lg bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden shadow-2xl">
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-2xs">
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
                Konfirmasi Barang Keluar
              </DialogTitle>
              <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pastikan data pengeluaran stok telah sesuai dengan otorisasi pengeluaran barang.
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
              Transaksi mutasi stok ini bersifat <strong>permanen dan TIDAK BISA DIUBAH ATAU DIHAPUS</strong> setelah disimpan. Seluruh kuantitas barang keluar akan langsung memotong sisa stok fisik gudang dan dicatat dalam audit trail forensik.
            </p>
          </div>

          <!-- Transaction Summary Card -->
          <div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 p-3.5 space-y-2 text-xs">
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">No. Referensi:</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ form.reference_no }}</span>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-800/60">
              <span class="text-slate-500 dark:text-slate-400">Penerima / Alokasi:</span>
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
              <span class="text-slate-500 dark:text-slate-400">Total Kuantitas Keluar:</span>
              <span class="font-bold text-amber-600 dark:text-amber-400">-{{ totalQuantity }} Unit</span>
            </div>
            <div class="flex justify-between items-center pt-1">
              <span class="text-slate-500 dark:text-slate-400">Estimasi Nilai Total:</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ formatRupiah(totalValue) }}</span>
            </div>
          </div>

          <p class="text-center text-xs text-slate-500 dark:text-slate-400">
            Apakah Anda yakin ingin membukukan transaksi pengeluaran stok ini sekarang?
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
            class="h-9 px-4 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white shadow-2xs inline-flex items-center gap-1.5"
            :disabled="form.processing || isSubmitting || hasStockExceededError"
            @click="handleConfirmSubmit"
          >
            <CheckCircle2 class="h-3.5 w-3.5" />
            {{ form.processing ? 'Menyimpan...' : 'Ya, Konfirmasi & Simpan' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Modal Impor CSV Outbound -->
    <Dialog :open="isCsvModalOpen" @update:open="isCsvModalOpen = $event">
      <DialogContent class="sm:max-w-xl bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden shadow-2xl">
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-2xs">
              <FileSpreadsheet class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
                Impor Rincian Barang Keluar dari CSV
              </DialogTitle>
              <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Unggah berkas CSV untuk mengisi daftar produk keluar secara otomatis.
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
              Data dari CSV akan dimasukkan ke dalam draf transaksi barang keluar. Perhatikan bahwa mutasi stok yang disimpan bersifat <strong>permanen dan TIDAK DAPAT DIHAPUS atau DIUBAH</strong>. Pastikan kuantitas tidak melebihi stok fisik gudang yang tersedia.
            </p>
          </div>

          <!-- Download Template Banner -->
          <div class="flex items-center justify-between p-3.5 rounded-xl border border-amber-100 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20">
            <div class="flex items-center gap-2.5 min-w-0">
              <FileText class="h-4 w-4 text-amber-600 shrink-0" />
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                  Format Berkas CSV Outbound
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                  Kolom: <code>sku,quantity,notes</code>
                </p>
              </div>
            </div>
            <button
              type="button"
              @click="downloadCsvTemplate"
              class="h-8 px-3 rounded-lg border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-xs font-semibold text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition-colors shadow-2xs inline-flex items-center gap-1.5 shrink-0 cursor-pointer"
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

          <!-- Error Alert jika parse gagal atau ada defisit -->
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
              <span class="text-amber-600 dark:text-amber-400 font-semibold">
                Total Kuantitas: -{{ csvParsedRows.reduce((sum, r) => sum + r.quantity, 0) }} Unit
              </span>
            </div>
            <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              <div
                v-for="(row, idx) in csvParsedRows.slice(0, 10)"
                :key="idx"
                class="p-2.5 flex items-center justify-between bg-white dark:bg-slate-900"
              >
                <div>
                  <div class="flex items-center gap-1.5">
                    <p class="font-bold text-slate-900 dark:text-white">{{ row.product.name }}</p>
                    <span v-if="row.isExceeded" class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-700 text-[10px] font-bold">Stok Kurang</span>
                  </div>
                  <p class="text-[11px] font-mono text-slate-400">SKU: {{ row.product.sku }} (Tersedia: {{ row.product.current_stock }})</p>
                </div>
                <div class="text-right">
                  <p :class="row.isExceeded ? 'font-bold text-rose-600' : 'font-bold text-amber-600 dark:text-amber-400'">
                    -{{ row.quantity }} {{ row.product.unit }}
                  </p>
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
            class="h-9 px-4 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white shadow-2xs inline-flex items-center gap-1.5"
            :disabled="csvParsedRows.length === 0 || csvParsedRows.some(r => r.isExceeded)"
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
