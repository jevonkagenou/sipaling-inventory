<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { FlaskConical, ArrowLeftRight, Undo2, Check } from 'lucide-vue-next'

const page = usePage()

const impersonation = computed(() => page.props.impersonation || {})
const isImpersonating = computed(() => impersonation.value.is_impersonating)
const originalUser = computed(() => impersonation.value.original_user)
const currentRoles = computed(() => page.props.auth?.roles || [])

const roles = [
  { key: 'komisaris', label: 'Komisaris', desc: 'Hak Approval & Monitoring Tertinggi', color: 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200' },
  { key: 'manajer-operasional', label: 'Manajer Operasional', desc: 'Pengadaan Restock, Master Data, & CRUD', color: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 border-blue-200' },
  { key: 'staf-gudang', label: 'Staf Gudang', desc: 'Pencatatan Mutasi Inbound & Outbound', color: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 border-amber-200' },
  { key: 'auditor-internal', label: 'Auditor Internal', desc: 'Investigasi Jejak Audit & Forensik', color: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200' },
]

function switchRole(roleKey) {
  router.post(route('impersonate.take'), { role: roleKey }, {
    preserveScroll: true,
  })
}

function leaveImpersonate() {
  router.post(route('impersonate.leave'), {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <div v-if="impersonation.can_impersonate" class="flex items-center">
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <button
          type="button"
          :class="[
            'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer shadow-2xs select-none',
            isImpersonating
              ? 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 animate-pulse'
              : 'bg-indigo-50/70 text-indigo-700 border-indigo-200 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800'
          ]"
          :title="isImpersonating ? 'Sedang dalam mode impersonate pengujian' : 'Beralih peran pengujian instan'"
        >
          <FlaskConical class="h-3.5 w-3.5" />
          <span class="hidden md:inline">
            {{ isImpersonating ? 'Menyamar' : 'Uji Peran' }}
          </span>
          <ArrowLeftRight class="h-3 w-3 opacity-60" />
        </button>
      </DropdownMenuTrigger>

      <DropdownMenuContent
        align="end"
        class="w-72 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-2xl rounded-2xl p-1.5 z-50 font-sans"
      >
        <DropdownMenuLabel class="px-3 py-2 text-xs font-bold text-slate-800 dark:text-slate-200">
          🧪 Pengalihan Peran Cepat (Testing)
          <p class="text-[10px] font-normal text-slate-400 dark:text-slate-500 mt-0.5">
            Ganti identitas akun untuk verifikasi RBAC tanpa login ulang.
          </p>
        </DropdownMenuLabel>

        <div v-if="isImpersonating && originalUser" class="px-2 pb-1.5">
          <button
            type="button"
            @click="leaveImpersonate"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
          >
            <span class="flex items-center gap-2">
              <Undo2 class="h-3.5 w-3.5" />
              Kembali ke Akun Asli
            </span>
            <span class="text-[10px] opacity-90 truncate max-w-[100px]">{{ originalUser.name }}</span>
          </button>
        </div>

        <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />

        <div class="py-1 space-y-1">
          <DropdownMenuItem
            v-for="r in roles"
            :key="r.key"
            @click="switchRole(r.key)"
            class="flex items-start justify-between px-3 py-2 rounded-xl text-xs cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
          >
            <div class="space-y-0.5">
              <div class="flex items-center gap-1.5">
                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ r.label }}</span>
                <span v-if="currentRoles.includes(r.key)" class="text-[10px] text-blue-600 dark:text-blue-400 font-bold flex items-center gap-0.5">
                  <Check class="h-3 w-3" /> Aktif
                </span>
              </div>
              <p class="text-[10.5px] text-slate-400 dark:text-slate-500 line-clamp-1">{{ r.desc }}</p>
            </div>
          </DropdownMenuItem>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
