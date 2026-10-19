<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { ChevronDown, Plus, Search, FolderTree, Upload, RotateCcw } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    searchQuery: string
    activeStatusFilter: 'all' | 'Aman' | 'Reorder'
    totalCount: number
    amanCount: number
    reorderCount: number
    table: any
    columnNames: Record<string, string>
    categories?: { id: string; name: string }[]
    selectedCategoryId?: string
    canManage?: boolean
  }>(),
  {
    canManage: false,
  }
)

const emit = defineEmits<{
  (e: 'update:searchQuery', val: string): void
  (e: 'update:statusFilter', status: 'all' | 'Aman' | 'Reorder'): void
  (e: 'update:selectedCategoryId', categoryId: string): void
  (e: 'create'): void
  (e: 'import'): void
  (e: 'manageCategories'): void
}>()

const hideableColumns = computed(() => {
  if (!props.table) return []
  return props.table.getAllColumns().filter((c: any) => c.getCanHide())
})

const hasActiveFilters = computed(() => {
  return (
    props.activeStatusFilter !== 'all' ||
    (props.selectedCategoryId && props.selectedCategoryId !== 'all') ||
    !!props.searchQuery
  )
})
</script>

<template>
  <div class="space-y-3 pt-1">
    <!-- Baris 1: Pencarian, Seleksi Kategori & Kelompok Tombol Aksi -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
      <!-- Sisi Kiri: Search Input & Dropdown Kategori -->
      <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Input Pencarian -->
        <div class="relative w-full sm:w-64 lg:w-72">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
          <Input
            class="w-full pl-9 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs shadow-2xs focus-visible:ring-2 focus-visible:ring-[#2563EB]/15 focus-visible:border-[#2563EB] h-9 rounded-xl"
            placeholder="Cari SKU atau nama barang..."
            :model-value="searchQuery"
            @update:model-value="emit('update:searchQuery', String($event))"
          />
        </div>

        <!-- Filter Kategori -->
        <div v-if="categories && categories.length > 0" class="shrink-0">
          <select
            :value="selectedCategoryId || 'all'"
            @change="emit('update:selectedCategoryId', ($event.target as HTMLSelectElement).value)"
            class="w-full sm:w-auto h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 font-medium shadow-2xs hover:border-[#2563EB]/40 focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20 cursor-pointer max-w-[170px] truncate"
            title="Saring berdasarkan kategori produk"
          >
            <option value="all">Semua Kategori</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Sisi Kanan: Aksi (Impor, Kategori, Kolom, Tambah Barang) -->
      <div class="flex flex-wrap items-center gap-2 shrink-0 self-end sm:self-auto">
        <!-- Tombol Impor CSV (Khusus Pengelola) -->
        <Button
          v-if="canManage"
          variant="outline"
          @click="emit('import')"
          class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium shadow-2xs transition-all text-xs h-9 px-3 cursor-pointer"
          title="Impor katalog produk massal dari berkas CSV"
        >
          <Upload class="w-3.5 h-3.5 mr-1.5 text-slate-500" /> Impor CSV
        </Button>

        <!-- Tombol Kelola Kategori (Khusus Pengelola) -->
        <Button
          v-if="canManage"
          variant="outline"
          @click="emit('manageCategories')"
          class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:text-[#2563EB] dark:hover:text-blue-400 font-medium shadow-2xs transition-all text-xs h-9 px-3 cursor-pointer"
          title="Buka panel kelola master kategori"
        >
          <FolderTree class="w-3.5 h-3.5 mr-1.5 text-slate-500" /> Kelola Kategori
        </Button>

        <!-- Dropdown Opsi Kolom (Untuk Semua Role) -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="outline"
              class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 shadow-2xs hover:border-[#2563EB]/40 cursor-pointer h-9 px-3"
            >
              Kolom <ChevronDown class="ml-1.5 h-3.5 w-3.5 text-slate-400" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-44 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
            <DropdownMenuLabel class="text-xs text-slate-400 font-normal">Tampilkan Kolom</DropdownMenuLabel>
            <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />
            <DropdownMenuCheckboxItem
              v-for="column in hideableColumns"
              :key="column.id"
              class="capitalize text-xs cursor-pointer text-slate-700 dark:text-slate-300"
              :model-value="column.getIsVisible()"
              @update:model-value="(val: boolean) => column.toggleVisibility(!!val)"
            >
              {{ columnNames[column.id] || column.id }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Tombol Tambah Barang (Khusus Pengelola) -->
        <Button
          v-if="canManage"
          @click="emit('create')"
          class="bg-[#2563EB] hover:bg-blue-700 text-white font-medium shadow-sm transition-all text-xs h-9 px-3.5 cursor-pointer"
        >
          <Plus class="w-4 h-4 mr-1.5" /> Tambah Barang
        </Button>
      </div>
    </div>

    <!-- Baris 2: Segmented Control Status Filter (Lega, Rapi, Bebas Scrollbar) -->
    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800/80">
      <div class="inline-flex items-center p-1 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-xs">
        <!-- Tab Semua -->
        <button
          type="button"
          @click="emit('update:statusFilter', 'all')"
          :class="activeStatusFilter === 'all'
            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold shadow-2xs'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
          class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          <span>Semua Barang</span>
          <span
            :class="activeStatusFilter === 'all'
              ? 'bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 font-semibold'
              : 'bg-slate-200/80 dark:bg-slate-700/80 text-slate-600 dark:text-slate-400'"
            class="px-1.5 py-0.2 rounded-md text-[10px]"
          >{{ totalCount }}</span>
        </button>

        <!-- Tab Aman -->
        <button
          type="button"
          @click="emit('update:statusFilter', 'Aman')"
          :class="activeStatusFilter === 'Aman'
            ? 'bg-[#10B981] text-white font-bold shadow-2xs'
            : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 font-medium'"
          class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="activeStatusFilter === 'Aman' ? 'bg-white' : 'bg-[#10B981]'"
          ></span>
          <span>Stok Aman</span>
          <span
            :class="activeStatusFilter === 'Aman'
              ? 'bg-white/20 text-white font-semibold'
              : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'"
            class="px-1.5 py-0.2 rounded-md text-[10px]"
          >{{ amanCount }}</span>
        </button>

        <!-- Tab Perlu Restock -->
        <button
          type="button"
          @click="emit('update:statusFilter', 'Reorder')"
          :class="activeStatusFilter === 'Reorder'
            ? 'bg-amber-500 text-white font-bold shadow-2xs'
            : 'text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 font-medium'"
          class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="activeStatusFilter === 'Reorder' ? 'bg-white' : 'bg-amber-500'"
          ></span>
          <span>Perlu Restock</span>
          <span
            :class="activeStatusFilter === 'Reorder'
              ? 'bg-white/20 text-white font-semibold'
              : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'"
            class="px-1.5 py-0.2 rounded-md text-[10px]"
          >{{ reorderCount }}</span>
        </button>
      </div>

      <!-- Filter Aktif Indicator & Reset -->
      <div v-if="hasActiveFilters" class="flex items-center gap-2 px-1">
        <span class="text-[11px] text-slate-400 hidden sm:inline">Filter aktif:</span>
        <button
          type="button"
          @click="emit('update:statusFilter', 'all'); emit('update:searchQuery', ''); emit('update:selectedCategoryId', 'all')"
          class="text-xs text-[#2563EB] hover:text-blue-700 dark:text-blue-400 hover:underline cursor-pointer font-medium inline-flex items-center gap-1"
          title="Bersihkan seluruh kriteria filter"
        >
          <RotateCcw class="w-3 h-3" /> Reset Filter
        </button>
      </div>
    </div>
  </div>
</template>
