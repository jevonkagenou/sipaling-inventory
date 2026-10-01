<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { IdCard, Mail, Phone, CalendarDays, ShieldCheck, BadgeCheck } from 'lucide-vue-next'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? {})

const initials = computed(() => {
  const name = user.value?.name ?? ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '??'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

function toTitleCase(slug: string) {
  return slug
    .replace(/[-_]/g, ' ')
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
}

const roleName = computed(() => {
  const roles = user.value?.roles
  if (Array.isArray(roles) && roles.length > 0) {
    const raw = typeof roles[0] === 'string' ? roles[0] : roles[0]?.name ?? 'pengguna'
    return toTitleCase(raw)
  }
  return 'Pengguna'
})

const isActive = computed(() => user.value?.is_active ?? true)

const joinedDate = computed(() => {
  const raw = user.value?.created_at
  if (!raw) return null
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(raw))
})

const employeeId = computed(() => {
  const id = user.value?.id ?? ''
  return id ? `ID-${String(id).slice(0, 8).toUpperCase()}` : 'ID-UNKNOWN'
})

const details = computed(() => {
  const list = [
    { icon: IdCard, label: 'ID Pengguna', value: employeeId.value, mono: true },
    { icon: Mail, label: 'Email', value: user.value?.email ?? '-' },
  ]
  if (user.value?.phone) {
    list.push({ icon: Phone, label: 'Telepon', value: user.value.phone })
  }
  if (joinedDate.value) {
    list.push({ icon: CalendarDays, label: 'Bergabung Sejak', value: joinedDate.value })
  }
  return list
})
</script>

<template>
  <div class="group relative rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-shadow duration-300 overflow-hidden">
    <!-- Header Namecard dengan pola dekoratif -->
    <div class="relative bg-gradient-to-br from-[#2563EB] via-[#3B5FE8] to-[#4F46E5] px-5 py-6 sm:px-8 sm:py-7 overflow-hidden">
      <!-- Pola titik dekoratif -->
      <div
        class="absolute inset-0 opacity-[0.15] pointer-events-none"
        style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 18px 18px;"
      />
      <!-- Lingkaran dekoratif besar -->
      <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/10 pointer-events-none" />
      <div class="absolute -right-4 -bottom-16 w-32 h-32 rounded-full bg-white/5 pointer-events-none" />

      <div class="relative flex items-center gap-4 sm:gap-5">
        <div class="relative shrink-0">
          <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-white/15 backdrop-blur-md ring-2 ring-white/50 shadow-lg flex items-center justify-center text-white font-bold text-xl sm:text-2xl tracking-wide">
            {{ initials }}
          </div>
          <div class="absolute -bottom-1 -right-1 h-6 w-6 rounded-full bg-white flex items-center justify-center shadow-sm">
            <BadgeCheck class="w-4 h-4 text-[#2563EB]" />
          </div>
        </div>

        <div class="min-w-0">
          <h3 class="text-white font-bold text-base sm:text-xl leading-tight truncate tracking-tight">
            {{ user?.name ?? 'Pengguna' }}
          </h3>
          <p class="text-white/85 text-xs sm:text-sm mt-0.5 font-medium">{{ roleName }}</p>
          <span
            class="inline-flex items-center gap-1.5 mt-2.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-semibold backdrop-blur-sm"
            :class="isActive
              ? 'bg-emerald-400/25 text-emerald-50 ring-1 ring-emerald-200/40'
              : 'bg-red-400/25 text-red-50 ring-1 ring-red-200/40'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="isActive ? 'bg-emerald-300' : 'bg-red-300'" />
            {{ isActive ? 'Akun Aktif' : 'Akun Non-Aktif' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Detail Kartu Identitas: grid chip -->
    <div class="px-5 py-5 sm:px-8 sm:py-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="item in details"
          :key="item.label"
          class="flex items-start gap-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 px-3.5 py-3"
        >
          <div class="shrink-0 h-8 w-8 rounded-md bg-[#2563EB]/10 dark:bg-blue-500/10 flex items-center justify-center">
            <component :is="item.icon" class="w-4 h-4 text-[#2563EB] dark:text-blue-400" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ item.label }}</p>
            <p
              class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white truncate mt-0.5"
              :class="item.mono && 'font-mono tracking-tight'"
            >
              {{ item.value }}
            </p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
        <ShieldCheck class="w-4 h-4 text-[#2563EB] dark:text-blue-400 shrink-0" />
        <span class="text-xs text-slate-500 dark:text-slate-400">Identitas terverifikasi sistem SIPALING</span>
      </div>
    </div>
  </div>
</template>
