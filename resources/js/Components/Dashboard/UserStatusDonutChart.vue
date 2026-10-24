<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Users, UserCheck, UserX, ArrowRight, Shield } from 'lucide-vue-next'

export interface UserStatsProps {
  total: number
  active: number
  blocked: number
  active_percentage: number
  roles?: Array<{ name: string; count: number }>
}

const props = withDefaults(
  defineProps<{
    userStats: UserStatsProps
  }>(),
  {
    userStats: () => ({
      total: 0,
      active: 0,
      blocked: 0,
      active_percentage: 0,
      roles: [],
    }),
  }
)

// Donut geometry
const radius = 64
const strokeWidth = 18
const circumference = 2 * Math.PI * radius

const activeRatio = computed(() => {
  if (props.userStats.total === 0) return 1 // default state
  return props.userStats.active / props.userStats.total
})

const blockedRatio = computed(() => {
  if (props.userStats.total === 0) return 0
  return props.userStats.blocked / props.userStats.total
})

const activeDash = computed(() => `${circumference * activeRatio.value} ${circumference}`)
const blockedDash = computed(() => `${circumference * blockedRatio.value} ${circumference}`)
const blockedOffset = computed(() => -(circumference * activeRatio.value))

function formatRoleName(roleName: string) {
  switch (roleName) {
    case 'komisaris':
      return 'Komisaris'
    case 'manajer-operasional':
      return 'Manajer Ops'
    case 'staf-gudang':
      return 'Staf Gudang'
    case 'auditor-internal':
      return 'Auditor'
    default:
      return roleName
  }
}
</script>

<template>
  <div class="flex flex-col justify-between h-full space-y-4">
    <!-- Visual Donut & Center Stats -->
    <div class="flex items-center justify-center gap-6 py-2">
      <!-- SVG Donut -->
      <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
        <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 160 160">
          <!-- Background track circle -->
          <circle
            cx="80"
            cy="80"
            :r="radius"
            :stroke-width="strokeWidth"
            fill="transparent"
            class="text-slate-100 dark:text-slate-800"
            stroke="currentColor"
          />

          <!-- Active Users Segment (Royal Blue #2563EB) -->
          <circle
            v-if="userStats.active > 0"
            cx="80"
            cy="80"
            :r="radius"
            :stroke-width="strokeWidth"
            fill="transparent"
            stroke="#2563EB"
            stroke-linecap="round"
            :stroke-dasharray="activeDash"
            stroke-dashoffset="0"
            class="transition-all duration-700 ease-out"
          />

          <!-- Blocked Users Segment (Rose #F43F5E) -->
          <circle
            v-if="userStats.blocked > 0"
            cx="80"
            cy="80"
            :r="radius"
            :stroke-width="strokeWidth"
            fill="transparent"
            stroke="#F43F5E"
            stroke-linecap="round"
            :stroke-dasharray="blockedDash"
            :stroke-dashoffset="blockedOffset"
            class="transition-all duration-700 ease-out"
          />
        </svg>

        <!-- Center Information -->
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
          <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
            {{ userStats.active_percentage }}%
          </span>
          <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
            Aktif
          </span>
        </div>
      </div>

      <!-- Quick Legend Cards -->
      <div class="space-y-2.5 flex-1 min-w-[120px]">
        <!-- Aktif -->
        <div class="p-2.5 rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-950/20">
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300 font-medium">
              <UserCheck class="w-3.5 h-3.5 text-[#2563EB]" />
              Aktif
            </span>
            <span class="font-bold text-slate-900 dark:text-white tabular-nums">
              {{ userStats.active }} user
            </span>
          </div>
        </div>

        <!-- Diblokir / Nonaktif -->
        <div class="p-2.5 rounded-xl border border-rose-100 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20">
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300 font-medium">
              <UserX class="w-3.5 h-3.5 text-rose-500" />
              Diblokir
            </span>
            <span class="font-bold text-slate-900 dark:text-white tabular-nums">
              {{ userStats.blocked }} user
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Sebaran Peran / Roles Breakdown -->
    <div v-if="userStats.roles && userStats.roles.length > 0" class="pt-3 border-t border-slate-100 dark:border-slate-800">
      <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-2 flex items-center justify-between">
        <span>Sebaran Peran Sistem</span>
        <span class="text-[10px] text-slate-400">Total {{ userStats.total }} Akun</span>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div
          v-for="role in userStats.roles"
          :key="role.name"
          class="px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between"
        >
          <span class="text-slate-600 dark:text-slate-300 truncate text-[11px]">
            {{ formatRoleName(role.name) }}
          </span>
          <span class="font-bold text-slate-900 dark:text-white text-[11px] tabular-nums">
            {{ role.count }}
          </span>
        </div>
      </div>
    </div>

    <!-- Footer Quick CTA to Users Module -->
    <div class="pt-2">
      <Link
        :href="route('users.index')"
        class="w-full h-8 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-[#2563EB] dark:hover:border-blue-500 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-all inline-flex items-center justify-center gap-1.5"
      >
        <Users class="w-3.5 h-3.5 text-[#2563EB]" />
        Kelola Akun & Hak Akses
        <ArrowRight class="w-3 h-3 text-slate-400" />
      </Link>
    </div>
  </div>
</template>
