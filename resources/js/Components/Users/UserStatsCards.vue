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
      class="group relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0F172A] p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all duration-200 cursor-pointer"
      :class="[
        activeStatusFilter === 'all'
          ? 'ring-2 ring-[#2563EB]/80 border-[#2563EB]/40 bg-blue-50/20 dark:bg-blue-950/20'
          : 'hover:border-slate-300 dark:hover:border-slate-700',
      ]"
      @click="emit('filter-status', 'all')"
    >
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            Total Pengguna
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.total_users }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Akun Terdaftar</span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-blue-50 dark:bg-blue-950/70 border border-blue-200/60 dark:border-blue-800/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shadow-xs">
          <Users class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <span>Akses Terverifikasi</span>
        <span class="font-semibold text-blue-600 dark:text-blue-400">4 Peran Spatie</span>
      </div>
    </div>

    <!-- Card 2: Pengguna Aktif (Stitch AI Emerald) -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0F172A] p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all duration-200 cursor-pointer"
      :class="[
        activeStatusFilter === '1'
          ? 'ring-2 ring-[#10B981]/80 border-[#10B981]/40 bg-emerald-50/20 dark:bg-emerald-950/20'
          : 'hover:border-slate-300 dark:hover:border-slate-700',
      ]"
      @click="emit('filter-status', '1')"
    >
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
            Akun Aktif
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.active_users }}
            </h3>
            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
              {{ activeRate }}%
            </span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200/60 dark:border-emerald-800/60 text-[#10B981] dark:text-emerald-400 flex items-center justify-center shadow-xs">
          <UserCheck class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse" />
          Dapat login ke sistem
        </span>
        <span class="font-medium text-slate-700 dark:text-slate-300">Siap Operasi</span>
      </div>
    </div>

    <!-- Card 3: Pengguna Nonaktif / Ditangguhkan -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0F172A] p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all duration-200 cursor-pointer"
      :class="[
        activeStatusFilter === '0'
          ? 'ring-2 ring-rose-500/80 border-rose-500/40 bg-rose-50/20 dark:bg-rose-950/20'
          : 'hover:border-slate-300 dark:hover:border-slate-700',
      ]"
      @click="emit('filter-status', '0')"
    >
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">
            Akun Dinonaktifkan
          </p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
              {{ stats.inactive_users }}
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Ditangguhkan</span>
          </div>
        </div>
        <div class="h-11 w-11 rounded-xl bg-rose-50 dark:bg-rose-950/70 border border-rose-200/60 dark:border-rose-800/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs">
          <UserX class="h-5 w-5" />
        </div>
      </div>
      <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <span>Akses Ditolak</span>
        <span class="font-semibold text-rose-600 dark:text-rose-400">
          {{ stats.inactive_users > 0 ? 'Perlu Ditinjau' : 'Nol Masalah' }}
        </span>
      </div>
    </div>
  </div>
</template>
