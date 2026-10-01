<script setup lang="ts">
import { h, ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
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
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'

// Modular Inventory Components
import InventoryStatsCards, { type CardKey } from '@/Components/Inventory/InventoryStatsCards.vue'
import InventoryToolbar from '@/Components/Inventory/InventoryToolbar.vue'
import InventoryToast, { type ToastState } from '@/Components/Inventory/InventoryToast.vue'
import ProductFormDialog from '@/Components/Inventory/ProductFormDialog.vue'
import ProductForecastDialog from '@/Components/Inventory/ProductForecastDialog.vue'

// Lucide Icons
import {
  ArrowUpDown,
  MoreHorizontal,
  Boxes,
  Pencil,
  History,
  Copy,
  Check,
  TrendingUp,
  Inbox,
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
  categories: { id: string; name: string }[]
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

// Toast Notification State
const toast = ref<ToastState | null>(null)
let toastTimer: any = null

function showToast(title: string, description?: string) {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { id: Date.now(), title, description }
  toastTimer = setTimeout(() => {
    toast.value = null
  }, 3200)
}

const copiedId = ref<string | null>(null)
function copyUuid(product: ProductItem) {
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(product.id)
    copiedId.value = product.id
    showToast(
      'Kode UUID Berhasil Disalin!',
      `ID untuk "${product.name}" (${product.sku}) telah tersimpan di clipboard.`
    )
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

const columnWidthClasses: Record<string, string> = {
  sku: 'w-[75px]',
  name: 'min-w-[150px] max-w-[170px] xl:max-w-[210px] 2xl:max-w-[280px]',
  category: 'w-[125px]',
  unit_price: 'w-[75px]',
  current_stock: 'w-[75px]',
  minimum_stock: 'w-[60px]',
  transaction_details_count: 'w-[80px]',
  status: 'w-[85px]',
  actions: 'w-[75px]',
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
            '-ml-2 h-7 px-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['SKU', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
      ),
    cell: ({ row }) =>
      h('div', { class: 'font-mono text-xs font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap' }, [
        h(
          'span',
          {
            class:
              'px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono tracking-wider text-[11px]',
          },
          row.getValue('sku'),
        ),
      ]),
  }),

  // 2. Nama Barang (Responsive Truncate with Native Tooltip)
  columnHelper.accessor('name', {
    header: ({ column }) =>
      h(
        Button,
        {
          variant: 'ghost',
          class:
            '-ml-2 h-7 px-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['Nama Barang', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
      ),
    cell: ({ row }) => {
      const product = row.original
      return h('div', { class: 'flex flex-col min-w-0 max-w-[150px] lg:max-w-[180px] xl:max-w-[260px]' }, [
        h(
          'span',
          {
            class: 'font-semibold text-slate-900 dark:text-slate-100 text-xs truncate',
            title: product.name,
          },
          product.name,
        ),
        h(
          'span',
          { class: 'text-[10px] text-slate-400 dark:text-slate-500 font-mono truncate' },
          `ID: ${product.id.slice(0, 8)}...`,
        ),
      ])
    },
  }),

  // 3. Kategori
  columnHelper.accessor((row) => row.category?.name ?? 'Tanpa Kategori', {
    id: 'category',
    header: () =>
      h(
        'div',
        { class: 'text-left font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Kategori',
      ),
    cell: ({ row }) => {
      const catName = row.getValue('category') as string
      return h(
        'span',
        {
          class:
            'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 dark:bg-blue-950/40 text-[#2563EB] dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/40 whitespace-nowrap',
        },
        catName,
      )
    },
  }),

  // 4. Harga Satuan
  columnHelper.accessor('unit_price', {
    header: ({ column }) =>
      h(
        'div',
        { class: 'text-right' },
        h(
          Button,
          {
            variant: 'ghost',
            class:
              'h-7 px-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Harga', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
        ),
      ),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right font-medium text-slate-700 dark:text-slate-300 text-xs tabular-nums whitespace-nowrap' },
        formatCurrency(row.getValue('unit_price')),
      ),
  }),

  // 5. Stok Saat Ini
  columnHelper.accessor('current_stock', {
    header: ({ column }) =>
      h(
        'div',
        { class: 'text-right' },
        h(
          Button,
          {
            variant: 'ghost',
            class:
              'h-7 px-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Stok', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
        ),
      ),
    cell: ({ row }) => {
      const stock = row.getValue('current_stock') as number
      const min = row.original.minimum_stock
      const isLow = stock <= min
      return h('div', { class: 'text-right flex items-center justify-end gap-1 whitespace-nowrap' }, [
        h(
          'span',
          {
            class: cn(
              'font-semibold text-xs tabular-nums',
              isLow ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-slate-100',
            ),
          },
          stock,
        ),
        h('span', { class: 'text-[10px] text-slate-400' }, row.original.unit || 'pcs'),
      ])
    },
  }),

  // 6. Batas Minimum (Safety Stock)
  columnHelper.accessor('minimum_stock', {
    header: () =>
      h(
        'div',
        { class: 'text-right font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Min',
      ),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right text-xs text-slate-500 dark:text-slate-400 tabular-nums whitespace-nowrap' },
        `${row.getValue('minimum_stock')} ${row.original.unit || 'pcs'}`,
      ),
  }),

  // 7. Total Mutasi Transaksi
  columnHelper.accessor('transaction_details_count', {
    header: ({ column }) =>
      h('div', { class: 'text-center' }, [
        h(
          Button,
          {
            variant: 'ghost',
            class:
              'h-7 px-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
            onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
          },
          () => ['Mutasi', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
        ),
      ]),
    cell: ({ row }) =>
      h('div', { class: 'text-center whitespace-nowrap' }, [
        h(
          'span',
          {
            class:
              'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80',
          },
          [h(History, { class: 'w-3 h-3 text-slate-400' }), `${row.getValue('transaction_details_count') || 0}x`],
        ),
      ]),
  }),

  // 8. Status Stok (Stitch AI Compliant: Emerald for Aman, Amber for Reorder)
  columnHelper.accessor('status', {
    header: () =>
      h(
        'div',
        { class: 'text-center font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Status',
      ),
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const isAman = status === 'Aman'
      return h('div', { class: 'text-center whitespace-nowrap' }, [
        h(
          'span',
          {
            class: cn(
              'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border',
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
    header: () =>
      h(
        'div',
        { class: 'text-right font-semibold text-slate-700 dark:text-slate-300 text-xs pr-1' },
        'Aksi',
      ),
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

// Status Filter Tabs + Kartu Statistik Interaktif
const activeStatusFilter = ref<'all' | 'Aman' | 'Reorder'>('all')
const activeCard = ref<CardKey | null>(null)
const tableSection = ref<HTMLElement | null>(null)

function setStatusFilter(status: 'all' | 'Aman' | 'Reorder') {
  activeStatusFilter.value = status
  activeCard.value = null
  if (status === 'all') {
    table.getColumn('status')?.setFilterValue(undefined)
  } else {
    table.getColumn('status')?.setFilterValue(status)
  }
}

function onCardClick(key: CardKey) {
  if (activeCard.value === key) {
    setStatusFilter('all')
    table.resetSorting(true)
    return
  }
  table.resetSorting(true)
  setStatusFilter(key === 'aman' ? 'Aman' : key === 'reorder' ? 'Reorder' : 'all')
  if (key === 'mutasi') {
    table.getColumn('transaction_details_count')?.toggleSorting(true)
  }
  activeCard.value = key
  tableSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

// Dialog States
const isDialogOpen = ref(false)
const dialogMode = ref<'create' | 'edit'>('create')
const selectedProduct = ref<ProductItem | null>(null)

function openCreateDialog() {
  dialogMode.value = 'create'
  selectedProduct.value = null
  isDialogOpen.value = true
}

function openEditDialog(product: ProductItem) {
  dialogMode.value = 'edit'
  selectedProduct.value = product
  isDialogOpen.value = true
}

const isForecastDialogOpen = ref(false)
const forecastProduct = ref<ProductItem | null>(null)

function openForecastDialog(product: ProductItem) {
  forecastProduct.value = product
  isForecastDialogOpen.value = true
}

function onSaved() {
  router.reload({ only: ['products', 'stats'] })
  showToast('Katalog Diperbarui', 'Data barang berhasil disimpan ke database.')
}
</script>

<template>
  <Head title="Katalog Inventaris - SIPALING" />

  <!-- Action Dropdown Template Definition -->
  <DefineTemplate v-slot="{ product }">
    <div class="flex items-center justify-end gap-1">
      <!-- Quick Action: Cek Prediksi DES (Direct 1-Click for Instant Visibility) -->
      <Button
        variant="ghost"
        size="icon"
        class="h-7 w-7 rounded-md text-[#2563EB] hover:text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/60 cursor-pointer transition-colors"
        title="Cek Prediksi Holt DES"
        @click.stop="openForecastDialog(product)"
      >
        <span class="sr-only">Cek Prediksi DES</span>
        <TrendingUp class="h-3.5 w-3.5" />
      </Button>

      <!-- More Actions Dropdown -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            class="h-7 w-7 p-0 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
            title="Menu Tindakan Lainnya"
          >
            <span class="sr-only">Buka menu aksi</span>
            <MoreHorizontal class="h-3.5 w-3.5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
          <DropdownMenuLabel class="text-xs text-slate-400 font-normal">Aksi Data</DropdownMenuLabel>
          <DropdownMenuItem
            class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs"
            @click="copyUuid(product)"
          >
            <Check v-if="copiedId === product.id" class="mr-2 h-3.5 w-3.5 text-emerald-500" />
            <Copy v-else class="mr-2 h-3.5 w-3.5 text-slate-400" />
            <span>{{ copiedId === product.id ? 'Tersalin' : 'Salin Kode UUID' }}</span>
          </DropdownMenuItem>
          <DropdownMenuItem @click="openEditDialog(product)" class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs">
            <Pencil class="mr-2 h-3.5 w-3.5 text-slate-400" /> Edit Barang
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </DefineTemplate>

  <AuthenticatedLayout>
    <!-- Header Slot (Clean Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Inventaris</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Katalog Master Data
        </h1>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Page Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Katalog Master Inventaris
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Kelola master data produk, pantau stok fisik aktual, dan riwayat mutasi barang.
          </p>
        </div>
      </div>
      <!-- 1. Modular Interactive KPI Cards Component -->
      <InventoryStatsCards
        :stats="stats"
        :active-card="activeCard"
        @select-card="onCardClick"
      />

      <!-- 2. Table Section & Modular Toolbar Component -->
      <div ref="tableSection" class="space-y-3">
        <InventoryToolbar
          :search-query="(table.getColumn('name')?.getFilterValue() as string) ?? ''"
          :active-status-filter="activeStatusFilter"
          :total-count="products.length"
          :aman-count="stats.aman_count"
          :reorder-count="stats.reorder_count"
          :table="table"
          :column-names="columnNames"
          @update:search-query="table.getColumn('name')?.setFilterValue($event)"
          @update:status-filter="setStatusFilter"
          @create="openCreateDialog"
        />

        <!-- 3. TanStack Data Table View -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
          <Table>
            <TableHeader class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800">
              <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="hover:bg-transparent">
                <TableHead
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  :class="cn(
                    'h-10 px-2 sm:px-2.5 text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap',
                    columnWidthClasses[header.column.id],
                    header.column.id === 'name' && 'max-w-[170px] xl:max-w-[210px] 2xl:max-w-[280px]',
                    header.column.id === 'actions' && 'sticky right-0 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-xs z-20 text-right pr-3 shadow-[-4px_0_8px_rgba(0,0,0,0.03)] border-l border-slate-200/80 dark:border-slate-800'
                  )"
                >
                  <FlexRender
                    v-if="!header.isPlaceholder"
                    :render="header.column.columnDef.header"
                    :props="header.getContext()"
                  />
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="table.getRowModel().rows.length">
                <TableRow
                  v-for="row in table.getRowModel().rows"
                  :key="row.id"
                  class="group border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                >
                  <TableCell
                    v-for="cell in row.getVisibleCells()"
                    :key="cell.id"
                    :class="cn(
                      'px-2 sm:px-2.5 py-2.5 text-xs',
                      columnWidthClasses[cell.column.id],
                      cell.column.id === 'name' && 'max-w-[170px] xl:max-w-[210px] 2xl:max-w-[280px] overflow-hidden truncate',
                      cell.column.id === 'actions' && 'sticky right-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/80 transition-colors z-10 text-right pr-3 shadow-[-4px_0_8px_rgba(0,0,0,0.03)] border-l border-slate-100 dark:border-slate-800/80'
                    )"
                  >
                    <FlexRender
                      :render="cell.column.columnDef.cell"
                      :props="cell.getContext()"
                    />
                  </TableCell>
                </TableRow>
              </template>
              <template v-else>
                <TableRow>
                  <TableCell :colspan="columns.length" class="h-40 text-center">
                    <div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 py-6">
                      <Inbox class="w-10 h-10 mb-2 stroke-[1.5]" />
                      <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Tidak ada barang yang cocok</p>
                      <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau bersihkan filter.</p>
                      <Button
                        variant="outline"
                        size="sm"
                        @click="setStatusFilter('all'); table.getColumn('name')?.setFilterValue('')"
                        class="mt-3 text-xs border-slate-200 dark:border-slate-800 cursor-pointer"
                      >
                        Reset Filter
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </template>
            </TableBody>
          </Table>

          <!-- Table Pagination & Total Counter -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30">
            <div class="text-xs text-slate-500 dark:text-slate-400">
              Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ table.getFilteredRowModel().rows.length }}</span> dari {{ products.length }} barang terdaftar
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

    <!-- Modals -->
    <ProductFormDialog
      v-model:open="isDialogOpen"
      :mode="dialogMode"
      :product="selectedProduct"
      :categories="categories"
      @saved="onSaved"
    />

    <ProductForecastDialog
      v-model:open="isForecastDialogOpen"
      :product="forecastProduct"
    />

    <!-- Modular Toast Notification Component -->
    <InventoryToast :toast="toast" @close="toast = null" />
  </AuthenticatedLayout>
</template>