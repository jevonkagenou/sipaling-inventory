<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { LogOut, AlertTriangle, UserCheck } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const roles = computed<string[]>(() => (page.props.auth as any)?.roles || [])

const roleLabel = computed(() => {
  const r = roles.value[0]
  switch (r) {
    case 'komisaris':
      return 'Komisaris'
    case 'manajer-operasional':
      return 'Manajer Ops'
    case 'staf-gudang':
      return 'Staf Gudang'
    case 'auditor-internal':
      return 'Auditor'
    default:
      return 'Pengguna'
  }
})

const isLoggingOut = ref(false)

function handleClose(val: boolean) {
  if (isLoggingOut.value) return
  emit('update:open', val)
}

function confirmLogout() {
  isLoggingOut.value = true
  router.post(route('logout'), {}, {
    onError: () => {
      window.location.href = route('logout')
    },
    onFinish: () => {
      isLoggingOut.value = false
      emit('update:open', false)
    },
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-[380px] bg-white/95 dark:bg-[#0F172A]/95 backdrop-blur-xl border-slate-200/80 dark:border-slate-800/80 p-6 sm:p-7 rounded-3xl shadow-2xl text-center overflow-hidden">
      <!-- Glowing Circular Warning Icon Badge -->
      <div class="relative mx-auto h-14 w-14 flex items-center justify-center">
        <div class="absolute inset-0 rounded-2xl bg-rose-500/15 dark:bg-rose-500/20 blur-md"></div>
        <div class="relative h-14 w-14 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200/70 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-inner">
          <AlertTriangle class="h-6 w-6 stroke-[2]" />
        </div>
      </div>

      <!-- Dialog Header & Copy -->
      <div class="mt-4 space-y-2">
        <DialogTitle class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white">
          Keluar dari Sistem?
        </DialogTitle>
        <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-[300px] mx-auto">
          Apakah Anda yakin ingin mengakhiri sesi kerja di <span class="font-semibold text-slate-700 dark:text-slate-300">SIPALING</span>? Seluruh data formulir yang belum disimpan akan hilang.
        </DialogDescription>
      </div>

      <!-- Current Session Identity Micro Badge -->
      <div v-if="user" class="mt-3.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100/90 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 text-[11px] font-medium text-slate-600 dark:text-slate-300 mx-auto">
        <UserCheck class="h-3 w-3 text-emerald-500 shrink-0" />
        <span class="truncate max-w-[140px]">{{ user.name }}</span>
        <span class="text-slate-300 dark:text-slate-600">&bull;</span>
        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ roleLabel }}</span>
      </div>

      <!-- Action Buttons Grid -->
      <div class="grid grid-cols-2 gap-2.5 mt-6 pt-2">
        <Button
          type="button"
          variant="outline"
          class="h-10 rounded-xl text-xs font-semibold border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer shadow-2xs transition-colors"
          :disabled="isLoggingOut"
          @click="emit('update:open', false)"
        >
          Batal
        </Button>
        <Button
          type="button"
          class="h-10 rounded-xl text-xs font-semibold bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white shadow-md shadow-rose-600/25 inline-flex items-center justify-center gap-1.5 cursor-pointer transition-all"
          :disabled="isLoggingOut"
          @click="confirmLogout"
        >
          <LogOut class="h-3.5 w-3.5" />
          {{ isLoggingOut ? 'Mengeluarkan...' : 'Ya, Keluar' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
