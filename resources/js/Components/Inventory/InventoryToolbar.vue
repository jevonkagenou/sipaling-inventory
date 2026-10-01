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
import { ChevronDown, Plus, Search } from 'lucide-vue-next'

const props = defineProps<{
  searchQuery: string
  activeStatusFilter: 'all' | 'Aman' | 'Reorder'
  totalCount: number
  amanCount: number
  reorderCount: number
  table: any
  columnNames: Record<string, string>
}>()

const emit = defineEmits<{
  (e: 'update:searchQuery', val: string): void
  (e: 'update:statusFilter', status: 'all' | 'Aman' | 'Reorder'): void
  (e: 'create'): void
}>()

const hideableColumns = computed(() => {
  if (!props.table) return []
  return props.table.getAllColumns().filter((c: any) => c.getCanHide())
})
</script>

<template>
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
    <!-- Search & Status Filter Tabs -->
    <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
        <Input
          class="w-full pl-9 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs shadow-2xs focus-visible:ring-1 focus-visible:ring-[#2563EB] h-9 rounded-lg"
          placeholder="Cari kode SKU atau nama barang..."
          :model-value="searchQuery"
          @update:model-value="emit('update:searchQuery', String($event))"
        />
      </div>

      <!-- Tab Status Filter -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
        <button
          type="button"
          @click="emit('update:statusFilter', 'all')"
          :class="activeStatusFilter === 'all'
            ? 'bg-[#2563EB] text-white font-semibold shadow-2xs'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer shrink-0"
        >
          Semua ({{ totalCount }})
        </button>
        <button
          type="button"
          @click="emit('update:statusFilter', 'Aman')"
          :class="activeStatusFilter === 'Aman'
            ? 'bg-[#10B981] text-white font-semibold shadow-2xs'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer shrink-0"
        >
          Aman ({{ amanCount }})
        </button>
        <button
          type="button"
          @click="emit('update:statusFilter', 'Reorder')"
          :class="activeStatusFilter === 'Reorder'
            ? 'bg-amber-500 text-white font-semibold shadow-2xs'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          class="px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer shrink-0"
        >
          Perlu Restock ({{ reorderCount }})
        </button>
      </div>
    </div>

    <!-- Actions: Column Selector & Add Product -->
    <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-auto">
      <!-- Dropdown Opsi Kolom -->
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

      <!-- Tambah Barang Button -->
      <Button
        @click="emit('create')"
        class="bg-[#2563EB] hover:bg-blue-700 text-white font-medium shadow-sm transition-all text-xs h-9 px-3.5 cursor-pointer"
      >
        <Plus class="w-4 h-4 mr-1.5" /> Tambah Barang
      </Button>
    </div>
  </div>
</template>
