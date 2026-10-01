<script setup lang="ts">
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import {
  Search,
  X,
  UserPlus,
  Filter,
} from 'lucide-vue-next'

export interface RoleOption {
  id: string
  name: string
  label: string
}

const props = defineProps<{
  searchQuery: string
  activeRoleFilter: string
  roles: RoleOption[]
}>()

const emit = defineEmits<{
  (e: 'update:searchQuery', val: string): void
  (e: 'update:activeRoleFilter', val: string): void
  (e: 'create'): void
}>()
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-[#0F172A] p-3 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-2xs">
    <!-- Left: Search Input -->
    <div class="relative flex-1 sm:max-w-md">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
      <Input
        :model-value="searchQuery"
        @update:model-value="emit('update:searchQuery', $event as string)"
        placeholder="Cari nama, email, no. HP..."
        class="pl-8.5 pr-8 h-9 text-xs bg-slate-50/70 dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-xl focus-visible:ring-[#2563EB]"
      />
      <button
        v-if="searchQuery"
        type="button"
        @click="emit('update:searchQuery', '')"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <!-- Right: Role Selector & Tambah Button -->
    <div class="flex items-center gap-2 sm:gap-2.5">
      <!-- Filter Role Select -->
      <div class="relative flex-1 sm:flex-initial">
        <select
          :value="activeRoleFilter"
          @change="emit('update:activeRoleFilter', ($event.target as HTMLSelectElement).value)"
          class="w-full sm:w-auto h-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900 text-xs px-3 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-[#2563EB] cursor-pointer"
        >
          <option value="all">Semua Peran</option>
          <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.label }}</option>
        </select>
      </div>

      <!-- Add User Primary Button -->
      <Button
        type="button"
        @click="emit('create')"
        class="h-9 px-4 rounded-xl bg-gradient-to-r from-[#2563EB] to-[#1D4ED8] hover:from-[#1D4ED8] hover:to-[#1E40AF] text-white text-xs font-semibold shadow-md shadow-blue-500/20 flex items-center justify-center gap-1.5 cursor-pointer shrink-0 transition-all active:scale-95"
      >
        <UserPlus class="w-4 h-4" />
        <span>Tambah Pengguna</span>
      </Button>
    </div>
  </div>
</template>
