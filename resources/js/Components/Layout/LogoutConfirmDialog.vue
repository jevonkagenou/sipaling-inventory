<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { LogOut, AlertTriangle } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const isLoggingOut = ref(false)

function handleClose(val: boolean) {
  if (isLoggingOut.value) return
  emit('update:open', val)
}

function confirmLogout() {
  isLoggingOut.value = true
  router.post(route('logout'), {}, {
    onFinish: () => {
      isLoggingOut.value = false
      emit('update:open', false)
    },
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-md bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden shadow-xl">
      <div class="p-6">
        <div class="flex items-start gap-4">
          <div class="h-11 w-11 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 shadow-2xs">
            <AlertTriangle class="h-5 w-5" />
          </div>
          <div class="space-y-1.5 flex-1 min-w-0">
            <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
              Konfirmasi Keluar Sistem
            </DialogTitle>
            <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Apakah Anda yakin ingin mengakhiri sesi kerja di <span class="font-semibold text-slate-700 dark:text-slate-300">SIPALING</span>? Seluruh data draf yang belum tersimpan mungkin akan hilang.
            </DialogDescription>
          </div>
        </div>
      </div>

      <DialogFooter class="px-6 py-3.5 bg-slate-50/80 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 flex flex-row items-center justify-end gap-2.5">
        <Button
          type="button"
          variant="outline"
          class="h-9 px-4 rounded-xl text-xs font-semibold border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
          :disabled="isLoggingOut"
          @click="emit('update:open', false)"
        >
          Batal
        </Button>
        <Button
          type="button"
          class="h-9 px-4 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white shadow-2xs inline-flex items-center gap-1.5"
          :disabled="isLoggingOut"
          @click="confirmLogout"
        >
          <LogOut class="h-3.5 w-3.5" />
          {{ isLoggingOut ? 'Mengeluarkan...' : 'Ya, Keluar' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
