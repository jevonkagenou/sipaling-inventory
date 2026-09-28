<script setup lang="ts">
import { h, ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  columnFilteringFeature,
  columnVisibilityFeature,
  createColumnHelper,
  createExpandedRowModel,
  createFilteredRowModel,
  createPaginatedRowModel,
  createSortedRowModel,
  filterFn_includesString,
  FlexRender,
  rowExpandingFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  sortFn_alphanumeric,
  sortFn_text,
  tableFeatures,
  useTable,
} from '@tanstack/vue-table'
import { createReusableTemplate } from '@vueuse/core'
import { cn } from '@/lib/utils'

// Shadcn UI Components
import { Button } from '@/Components/ui/button'
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Input } from '@/Components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'

// Lucide Icons
import {
  ArrowUpDown,
  ChevronDown,
  MoreHorizontal,
  Boxes,
  Plus,
  ArrowLeft,
  CheckCircle2,
  AlertTriangle,
  Database,
  Layers,
  Pencil,
  Trash2,
  History,
  Sun,
  Moon,
  Copy,
  Check,
} from 'lucide-vue-next'

export interface Category {
  id: string
  name: string
  slug: string
}

export interface ProductItem {
  id: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
  current_stock: number
  minimum_stock: number
  transaction_details_count: number
  status: 'Aman' | 'Reorder'
  category?: Category
}

const props = defineProps<{
  products: ProductItem[]
  filters: { search?: string }
  stats: {
    total_products: number
    total_categories: number
    total_transactions: number
    total_details: number
    reorder_count: number
    aman_count: number
  }
}>()

// Single Theme Toggle (Synchronized with localStorage & HTML root, defaults to Light)
const isDark = ref(false)

function toggleTheme() {
  isDark.value = !isDark.value
  if (typeof window !== 'undefined') {
    localStorage.setItem('sipaling-theme', isDark.value ? 'dark' : 'light')
    if (isDark.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }
}

onMounted(() => {
  const savedTheme = localStorage.getItem('sipaling-theme')
  if (savedTheme) {
    isDark.value = savedTheme === 'dark'
  } else {
    isDark.value = false
  }
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
})

// Currency Formatter
const formatCurrency = (val: number | string) => {
  return new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
    minimumFractionDigits: 2,
  }).format(Number(val) || 0)
}

// Reusable Template for Row Actions
const [DefineTemplate, ReuseTemplate] = createReusableTemplate<{
  product: ProductItem
}>()

const copiedId = ref<string | null>(null)
function copyUuid(id: string) {
  if (navigator?.clipboard) {
    navigator.clipboard.writeText(id)
    copiedId.value = id
    setTimeout(() => {
      copiedId.value = null
    }, 2000)
  }
}

// TanStack Table Features & Setup
const features = tableFeatures({
  columnFilteringFeature,
  columnVisibilityFeature,
  rowExpandingFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  expandedRowModel: createExpandedRowModel(),
  filteredRowModel: createFilteredRowModel(),
  paginatedRowModel: createPaginatedRowModel(),
  sortedRowModel: createSortedRowModel(),
  filterFns: { includesString: filterFn_includesString },
  sortFns: { alphanumeric: sortFn_alphanumeric, text: sortFn_text },
})

const columnHelper = createColumnHelper<typeof features, ProductItem>()

const columnNames: Record<string, string> = {
  sku: 'Kode SKU',
  name: 'Nama Barang',
  category: 'Kategori',
  unit_price: 'Harga Satuan',
  current_stock: 'Stok Saat Ini',
  minimum_stock: 'Batas Minimum',
  transaction_details_count: 'Total Mutasi',
  status: 'Status Stok',
  actions: 'Aksi',
}

const columns = columnHelper.columns([
  // 1. SKU / Kode
  columnHelper.accessor('sku', {
    header: ({ column }) =>
      h(
        Button,
        {
          variant: 'ghost',
          class:
            '-ml-3 h-8 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['Kode SKU', h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5 text-slate-400' })],
      ),
    cell: ({ row }) =>
      h(
        'span',
        { class: 'font-mono text-xs font-semibold text-[#2563EB] dark:text-blue-400' },
        row.getValue('sku'),
      ),
  }),

  // 2. Nama Barang
  columnHelper.accessor('name', {
    header: ({ column }) =>
      h(
        Button,
        {
          variant: 'ghost',
          class:
            '-ml-3 h-8 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['Nama Barang', h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5 text-slate-400' })],
      ),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'font-medium text-slate-900 dark:text-slate-100 leading-snug' },
        row.getValue('name'),
      ),
  }),

  // 3. Kategori
  columnHelper.accessor((row) => row.category?.name || 'Umum', {
    id: 'category',
    header: 'Kategori',
    cell: ({ row }) =>
      h(
        'span',
        {
          class:
            'inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80',
        },
        [h(Layers, { class: 'w-3 h-3 text-slate-400' }), row.getValue('category')],
      ),
  }),

  // 4. Harga Satuan
  columnHelper.accessor('unit_price', {
    header: ({ column }) =>
      h('div', { class: 'text-right' }, [
        h(
          Button,
          {
            variant: 'ghost',
            class:
              '-mr-3 h-8 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Harga Satuan', h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5 text-slate-400' })],
        ),
      ]),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right font-medium text-sm text-slate-800 dark:text-slate-200' },
        formatCurrency(row.getValue('unit_price')),
      ),
  }),

  // 5. Stok Saat Ini
  columnHelper.accessor('current_stock', {
    header: ({ column }) =>
      h('div', { class: 'text-right' }, [
        h(
          Button,
          {
            variant: 'ghost',
            class:
              '-mr-3 h-8 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Stok Saat Ini', h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5 text-slate-400' })],
        ),
      ]),
    cell: ({ row }) => {
      const product = row.original
      return h('div', { class: 'text-right' }, [
        h('span', { class: 'font-semibold text-slate-900 dark:text-white text-sm' }, product.current_stock),
        h('span', { class: 'text-xs text-slate-400 ml-1 font-normal' }, product.unit || 'pcs'),
      ])
    },
  }),

  // 6. Batas Minimum (ROP)
  columnHelper.accessor('minimum_stock', {
    header: () =>
      h(
        'div',
        { class: 'text-right font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Batas Minimum',
      ),
    cell: ({ row }) => {
      const product = row.original
      return h(
        'div',
        { class: 'text-right text-xs text-slate-500 dark:text-slate-400' },
        `${product.minimum_stock} ${product.unit || 'pcs'}`,
      )
    },
  }),

  // 7. Total Mutasi
  columnHelper.accessor('transaction_details_count', {
    header: ({ column }) =>
      h('div', { class: 'text-center' }, [
        h(
          Button,
          {
            variant: 'ghost',
            class:
              'h-8 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Total Mutasi', h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5 text-slate-400' })],
        ),
      ]),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, [
        h(
          'span',
          {
            class:
              'inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80',
          },
          [h(History, { class: 'w-3 h-3 text-slate-400' }), `${row.getValue('transaction_details_count') || 0} mutasi`],
        ),
      ]),
  }),

  // 8. Status Stok (Stitch AI Compliant: Emerald for Aman, Amber for Reorder)
  columnHelper.accessor('status', {
    header: () =>
      h(
        'div',
        { class: 'text-center font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Status Stok',
      ),
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const isAman = status === 'Aman'
      return h('div', { class: 'text-center' }, [
        h(
          'span',
          {
            class: cn(
              'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border',
              isAman
                ? 'bg-emerald-50 dark:bg-emerald-950/50 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'
                : 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/60',
            ),
          },
          [
            h('span', {
              class: cn('w-1.5 h-1.5 rounded-full', isAman ? 'bg-[#10B981]' : 'bg-amber-500'),
            }),
            status,
          ],
        ),
      ])
    },
  }),

  // 9. Aksi
  columnHelper.display({
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
      return h(ReuseTemplate, {
        product: row.original,
      })
    },
  }),
])

const table = useTable({
  features,
  data: computed(() => props.products),
  columns,
})

// Status Filter Tabs
const activeStatusFilter = ref<'all' | 'Aman' | 'Reorder'>('all')

function setStatusFilter(status: 'all' | 'Aman' | 'Reorder') {
  activeStatusFilter.value = status
  if (status === 'all') {
    table.getColumn('status')?.setFilterValue(undefined)
  } else {
    table.getColumn('status')?.setFilterValue(status)
  }
}
</script>

<template>
  <Head title="Inventaris Barang - SIPALING" />

  <!-- Dropdown Action Template -->
  <DefineTemplate v-slot="{ product }">
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" class="h-8 w-8 p-0 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 cursor-pointer">
          <span class="sr-only">Buka menu aksi</span>
          <MoreHorizontal class="h-4 w-4" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="w-48 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
        <DropdownMenuLabel class="text-xs text-slate-400 font-normal">Aksi Data</DropdownMenuLabel>
        <DropdownMenuItem
          class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs"
          @click="copyUuid(product.id)"
        >
          <Check v-if="copiedId === product.id" class="mr-2 h-3.5 w-3.5 text-emerald-500" />
          <Copy v-else class="mr-2 h-3.5 w-3.5 text-slate-400" />
          <span>{{ copiedId === product.id ? 'Tersalin' : 'Salin Kode UUID' }}</span>
        </DropdownMenuItem>
        <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />
        <DropdownMenuItem class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs">
          <Pencil class="mr-2 h-3.5 w-3.5 text-slate-400" /> Edit Batas Minimum
        </DropdownMenuItem>
        <DropdownMenuItem class="cursor-pointer text-red-600 dark:text-red-400 focus:bg-red-50 dark:focus:bg-red-950/50 text-xs">
          <Trash2 class="mr-2 h-3.5 w-3.5" /> Hapus Barang
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </DefineTemplate>

  <div class="min-h-screen bg-slate-50 dark:bg-[#0F172A] p-4 sm:p-6 lg:p-10 font-sans text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <div class="mx-auto max-w-7xl space-y-6">

      <!-- Header Navigasi -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
          <Link
            href="/"
            class="p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-[#2563EB] dark:hover:text-[#4F46E5] hover:border-[#2563EB]/40 shadow-xs transition-colors"
            title="Kembali ke Beranda"
          >
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Daftar Inventaris Barang
              </h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
              Kelola master data produk, pantau stok aktual, dan riwayat mutasi transaksi.
            </p>
          </div>
        </div>

        <!-- Tombol Aksi & Mode Tema -->
        <div class="flex items-center gap-2.5">
          <Button
            variant="outline"
            @click="toggleTheme"
            class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 shadow-xs hover:border-[#2563EB]/40 cursor-pointer h-9 px-3"
          >
            <Sun v-if="isDark" class="h-4 w-4 mr-1.5 text-amber-400" />
            <Moon v-else class="h-4 w-4 mr-1.5 text-slate-400" />
            <span class="text-xs font-medium">{{ isDark ? 'Terang' : 'Gelap' }}</span>
          </Button>

          <Button class="bg-[#2563EB] hover:bg-blue-700 text-white font-medium shadow-sm transition-all text-xs h-9 px-3.5 cursor-pointer">
            <Plus class="w-4 h-4 mr-1.5" /> Tambah Barang
          </Button>
        </div>
      </div>

      <!-- Ringkasan Statistik Inventaris (Card Shadcn Rapi dengan CardAction) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Total Barang -->
        <Card class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <CardHeader class="pb-1.5">
            <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
              Total Barang
            </CardTitle>
            <CardAction>
              <div class="p-2 rounded-lg bg-[#2563EB]/10 text-[#2563EB] dark:text-blue-400">
                <Boxes class="w-4 h-4" />
              </div>
            </CardAction>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ stats.total_products }}
              </span>
              <span class="text-xs text-slate-500">item</span>
            </div>
            <CardDescription class="mt-1 text-xs text-slate-400">
              Dari {{ stats.total_categories }} kategori barang
            </CardDescription>
          </CardContent>
        </Card>

        <!-- Total Mutasi Transaksi -->
        <Card class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <CardHeader class="pb-1.5">
            <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
              Riwayat Transaksi
            </CardTitle>
            <CardAction>
              <div class="p-2 rounded-lg bg-[#4F46E5]/10 text-[#4F46E5] dark:text-indigo-400">
                <Database class="w-4 h-4" />
              </div>
            </CardAction>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ stats.total_details.toLocaleString() }}
              </span>
              <span class="text-xs text-slate-500">mutasi</span>
            </div>
            <CardDescription class="mt-1 text-xs text-slate-400">
              Tercatat dalam {{ stats.total_transactions.toLocaleString() }} faktur
            </CardDescription>
          </CardContent>
        </Card>

        <!-- Stok Aman -->
        <Card class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <CardHeader class="pb-1.5">
            <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
              Stok Aman
            </CardTitle>
            <CardAction>
              <div class="p-2 rounded-lg bg-[#10B981]/10 text-[#10B981] dark:text-emerald-400">
                <CheckCircle2 class="w-4 h-4" />
              </div>
            </CardAction>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400">
                {{ stats.aman_count }}
              </span>
              <span class="text-xs text-slate-500">barang</span>
            </div>
            <CardDescription class="mt-1 text-xs text-slate-400">
              Kuantitas di atas batas minimum
            </CardDescription>
          </CardContent>
        </Card>

        <!-- Perlu Restock -->
        <Card class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
          <CardHeader class="pb-1.5">
            <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
              Perlu Pengadaan
            </CardTitle>
            <CardAction>
              <div class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <AlertTriangle class="w-4 h-4" />
              </div>
            </CardAction>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                {{ stats.reorder_count }}
              </span>
              <span class="text-xs text-slate-500">barang</span>
            </div>
            <CardDescription class="mt-1 text-xs text-slate-400">
              Mendekati atau di bawah batas minimum
            </CardDescription>
          </CardContent>
        </Card>
      </div>

      <!-- Bilah Pencarian & Opsi Kolom -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
        <div class="flex items-center gap-2">
          <!-- Input Pencarian -->
          <Input
            class="w-full sm:w-80 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs shadow-xs focus-visible:ring-1 focus-visible:ring-[#2563EB] h-9"
            placeholder="Cari nama barang..."
            :model-value="(table.getColumn('name')?.getFilterValue() as string) ?? ''"
            @update:model-value="table.getColumn('name')?.setFilterValue($event)"
          />

          <!-- Tab Status -->
          <div class="hidden sm:flex items-center gap-1 ml-2">
            <button
              @click="setStatusFilter('all')"
              :class="activeStatusFilter === 'all'
                ? 'bg-[#2563EB] text-white font-medium shadow-xs'
                : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
              class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
            >
              Semua ({{ props.products.length }})
            </button>
            <button
              @click="setStatusFilter('Aman')"
              :class="activeStatusFilter === 'Aman'
                ? 'bg-[#10B981] text-white font-medium shadow-xs'
                : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
              class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
            >
              Aman ({{ stats.aman_count }})
            </button>
            <button
              @click="setStatusFilter('Reorder')"
              :class="activeStatusFilter === 'Reorder'
                ? 'bg-amber-600 text-white font-medium shadow-xs'
                : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
              class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
            >
              Perlu Restock ({{ stats.reorder_count }})
            </button>
          </div>
        </div>

        <!-- Dropdown Pilih Kolom -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline" class="ml-auto border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-xs shadow-xs hover:border-[#2563EB]/40 cursor-pointer h-9">
              Pilih Kolom <ChevronDown class="ml-1.5 h-3.5 w-3.5" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
            <DropdownMenuLabel class="text-xs text-slate-400 font-normal">Kolom Ditampilkan</DropdownMenuLabel>
            <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />
            <DropdownMenuCheckboxItem
              v-for="column in table.getAllColumns().filter((col) => col.getCanHide())"
              :key="column.id"
              class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer"
              :model-value="column.getIsVisible()"
              @update:model-value="(val) => column.toggleVisibility(!!val)"
            >
              {{ columnNames[column.id] || column.id }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <!-- Tabel Inventaris Shadcn -->
      <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow
              v-for="headerGroup in table.getHeaderGroups()"
              :key="headerGroup.id"
              class="bg-slate-50/80 dark:bg-slate-900/60 border-b-slate-200 dark:border-slate-800"
            >
              <TableHead
                v-for="header in headerGroup.headers"
                :key="header.id"
                class="font-semibold text-slate-700 dark:text-slate-300 text-xs py-3.5"
              >
                <FlexRender v-if="!header.isPlaceholder" :header="header" />
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="table.getRowModel().rows?.length">
              <TableRow
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800/60"
              >
                <TableCell
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  class="py-3"
                >
                  <FlexRender :cell="cell" />
                </TableCell>
              </TableRow>
            </template>

            <TableRow v-else>
              <TableCell :colspan="columns.length" class="h-32 text-center text-slate-500 dark:text-slate-400">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <Boxes class="w-6 h-6 text-slate-300 dark:text-slate-600" />
                  <span class="text-xs">Tidak ada data barang yang cocok dengan pencarian.</span>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Paginasi & Total Data -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Total {{ table.getFilteredRowModel().rows.length }} barang terdaftar
          </div>
          <div class="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="!table.getCanPreviousPage()"
              @click="table.previousPage()"
              class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-40 h-8 px-3"
            >
              Sebelumnya
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="!table.getCanNextPage()"
              @click="table.nextPage()"
              class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-40 h-8 px-3"
            >
              Berikutnya
            </Button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
