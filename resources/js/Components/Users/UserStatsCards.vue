<script setup lang="ts">
import { computed } from 'vue'
import {
  Users,
  UserCheck,
  UserX,
} from 'lucide-vue-next'

export type StatusFilterKey = 'all' | '1' | '0'

const props = defineProps<{
  stats: {
    total_users: number
    active_users: number
    inactive_users: number
  }
  activeStatusFilter: StatusFilterKey
  activeRoleFilter?: string
}>()

const emit = defineEmits<{
  (e: 'filter-status', status: StatusFilterKey): void
  (e: 'filter-role', role: string): void
}>()

const activeRate = computed(() => {
  if (!props.stats.total_users) return 100
  return Math.round((props.stats.active_users / props.stats.total_users) * 100)
})
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
    <!-- Card 1: Total Pengguna Terdaftar -->
    <div
      class="group relative overflow-hidden rounded-2xl border p-4 sm:p-5 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer select-none"
      :class="[
        activeStatusFilter === 'all'
          ? 'ring-2 ring-[#2563EB] border-[#2563EB] bg-blue-100/80 dark:bg-blue-950/50 dark:border-blue-500'
          : 'bg-blue-50/70 border-blue-200/80 hover:bg-blue-100/60 hover:border-blue-400/80 dark:bg-blue-950/20 dark:border-blue-900/50 dark:hover:border-blue-700/60',
      ]"
      @click="emit('filter-status', 'all')"
    >
      <div class="flex items-center justify-between gap-2">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Total Pengguna
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_users }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Akun Terdaftar</span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-white text-[#2563EB] border border-blue-200/80 shadow-2xs dark:bg-blue-950/80 dark:text-blue-400 dark:border-blue-800/80 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shrink-0">
          <Users class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-blue-200/60 dark:border-blue-900/50 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
        <span>Akses Terverifikasi</span>
        <span class="font-semibold text-blue-700 dark:text-blue-400">4 Peran Terdaftar</span>
      </div>
    </div>

    <!-- Card 2: Pengguna Aktif (Stitch AI Emerald) -->
    <div
      class="group relative overflow-hidden rounded-2xl border p-4 sm:p-5 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer select-none"
      :class="[
        activeStatusFilter === '1'
          ? 'ring-2 ring-[#10B981] border-[#10B981] bg-emerald-100/80 dark:bg-emerald-950/50 dark:border-emerald-500'
          : 'bg-emerald-50/70 border-emerald-200/80 hover:bg-emerald-100/60 hover:border-emerald-400/80 dark:bg-emerald-950/20 dark:border-emerald-900/50 dark:hover:border-emerald-700/60',
      ]"
      @click="emit('filter-status', '1')"
    >
      <div class="flex items-center justify-between gap-2">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">
            Akun Aktif
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.active_users }}
            </h3>
            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-100/80 dark:bg-emerald-950/80 px-1.5 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-800">
              {{ activeRate }}%
            </span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-white text-[#10B981] border border-emerald-200/80 shadow-2xs dark:bg-emerald-950/80 dark:text-emerald-400 dark:border-emerald-800/80 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shrink-0">
          <UserCheck class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-emerald-200/60 dark:border-emerald-900/50 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse" />
          Dapat login ke sistem
        </span>
        <span class="font-medium text-emerald-800 dark:text-emerald-300">Siap Operasi</span>
      </div>
    </div>

    <!-- Card 3: Pengguna Nonaktif / Ditangguhkan -->
    <div
      class="group relative overflow-hidden rounded-2xl border p-4 sm:p-5 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer select-none"
      :class="[
        activeStatusFilter === '0'
          ? 'ring-2 ring-rose-500 border-rose-500 bg-rose-100/80 dark:bg-rose-950/50 dark:border-rose-500'
          : 'bg-rose-50/70 border-rose-200/80 hover:bg-rose-100/60 hover:border-rose-400/80 dark:bg-rose-950/20 dark:border-rose-900/50 dark:hover:border-rose-700/60',
      ]"
      @click="emit('filter-status', '0')"
    >
      <div class="flex items-center justify-between gap-2">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-rose-800 dark:text-rose-300 uppercase tracking-wider">
            Akun Dinonaktifkan
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.inactive_users }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Ditangguhkan</span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-white text-rose-600 border border-rose-200/80 shadow-2xs dark:bg-rose-950/80 dark:text-rose-400 dark:border-rose-800/80 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shrink-0">
          <UserX class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-rose-200/60 dark:border-rose-900/50 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
        <span>Akses Ditolak</span>
        <span class="font-semibold text-rose-700 dark:text-rose-400">
          {{ stats.inactive_users > 0 ? 'Perlu Ditinjau' : 'Semua Aktif' }}
        </span>
      </div>
    </div>
  </div>
</template>
