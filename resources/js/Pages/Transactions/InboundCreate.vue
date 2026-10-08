<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  ArrowLeft,
  Plus,
  Trash2,
  Boxes,
  Calendar,
  Building2,
  FileText,
  AlertCircle,
  TrendingUp,
  CheckCircle2,
  Search,
  ArrowRight,
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

function submit() {
  isSubmitting.value = true
  form.post(route('transactions.inbound.store'), {
    onFinish: () => {
      isSubmitting.value = false
    },
  })
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
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Error Banner jika ada error validasi -->
        <div
          v-if="Object.keys(form.errors).length > 0"
          class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300 flex items-start gap-3"
        >
          <AlertCircle class="h-5 w-5 shrink-0 text-rose-500 mt-0.5" />
          <div class="text-xs sm:text-sm space-y-1">
            <p class="font-bold">Terdapat kesalahan pada formulir transaksi:</p>
            <ul class="list-disc pl-5 space-y-0.5">
              <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
            </ul>
          </div>
        </div>

        <!-- Section 1: Informasi Dokumen & Pengirim -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
            <FileText class="h-4 w-4 text-[#2563EB]" />
            1. Informasi Bukti Penerimaan
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
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <Boxes class="h-4 w-4 text-[#10B981]" />
                2. Rincian Barang Masuk
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pilih SKU produk dan tentukan kuantitas barang yang diterima.
              </p>
            </div>
            <button
              type="button"
              @click="addItem"
              class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-[#2563EB] hover:bg-blue-100 transition-colors border border-blue-200/60 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800/60 cursor-pointer"
            >
              <Plus class="h-3.5 w-3.5" />
              Tambah Baris Produk
            </button>
          </div>

          <!-- Items Row -->
          <div class="space-y-3 pt-2">
            <div
              v-for="(item, index) in form.items"
              :key="index"
              class="relative rounded-2xl border border-slate-200/90 bg-slate-50/50 p-4 transition-all dark:border-slate-800 dark:bg-slate-900/40"
            >
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 items-start">
                <!-- Dropdown Search Produk (Col 5) -->
                <div class="sm:col-span-5 relative">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Produk / SKU #{{ index + 1 }} <span class="text-rose-500">*</span>
                  </label>
                  
                  <div class="relative">
                    <input
                      type="text"
                      v-model="item.searchQuery"
                      @focus="item.isDropdownOpen = true"
                      placeholder="Cari SKU atau Nama Produk..."
                      class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-900 focus:border-[#2563EB] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                    />
                    <Search class="absolute right-3 top-2.5 h-4 w-4 text-slate-400 pointer-events-none" />
                  </div>

                  <!-- Dropdown Search Result Popup -->
                  <div
                    v-if="item.isDropdownOpen"
                    class="absolute left-0 right-0 top-full z-30 mt-1 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-800 dark:bg-slate-800 custom-scrollbar"
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
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800/80 dark:bg-slate-900/80 backdrop-blur-md">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Item SKU</p>
                <p class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                  {{ form.items.length }} Barang
                </p>
              </div>
              <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kuantitas Masuk</p>
                <p class="text-base sm:text-lg font-bold text-[#10B981]">
                  + {{ totalQuantity }} Unit
                </p>
              </div>
              <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Estimasi Nilai Total</p>
                <p class="text-base sm:text-lg font-bold text-[#2563EB] dark:text-blue-400">
                  {{ formatRupiah(totalValue) }}
                </p>
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
  </AuthenticatedLayout>
</template>
