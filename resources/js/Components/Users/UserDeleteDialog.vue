<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogFooter,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Trash2, AlertTriangle } from 'lucide-vue-next'

export interface UserItem {
  id: string
  name: string
  email: string
  primary_role: string
}

const props = defineProps<{
  open: boolean
  user: UserItem | null
  currentAuthUserId?: string
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'deleted'): void
}>()

const isSelf = computed(() => props.user?.id === props.currentAuthUserId)

function handleClose(val: boolean) {
  emit('update:open', val)
}

function confirmDelete() {
  if (!props.user || isSelf.value) return

  router.delete(route('users.destroy', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:open', false)
      emit('deleted')
    },
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-md bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden">
      <!-- Header -->
      <div class="p-5 border-b bg-rose-50/70 dark:bg-rose-950/40 border-rose-100 dark:border-rose-900/60">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-xl bg-rose-100 dark:bg-rose-900/80 text-rose-600 dark:text-rose-300 flex items-center justify-center shrink-0 shadow-xs">
            <Trash2 class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              Hapus Akun Pengguna
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Tindakan ini tidak dapat dibatalkan
            </p>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div v-if="user" class="p-5 space-y-3.5 text-xs text-slate-600 dark:text-slate-300">
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/70 border border-slate-100 dark:border-slate-800 space-y-1">
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Nama:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ user.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Email:</span>
            <span class="font-mono text-slate-700 dark:text-slate-300">{{ user.email }}</span>
          </div>
        </div>

        <div v-if="isSelf" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 flex items-start gap-2">
          <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
          <div>
            <p class="font-semibold">Tindakan Dilarang</p>
            <p class="text-[11px] mt-0.5">Anda tidak dapat menghapus akun Anda sendiri.</p>
          </div>
        </div>

        <p v-else class="text-slate-600 dark:text-slate-400 leading-relaxed">
          Apakah Anda yakin ingin menghapus data pengguna <strong class="text-slate-900 dark:text-white">{{ user.name }}</strong> secara permanen? Seluruh riwayat hak akses terkait akan dicabut.
        </p>
      </div>

      <!-- Footer -->
      <DialogFooter class="p-4 bg-slate-50/70 dark:bg-slate-900/70 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          @click="handleClose(false)"
          class="text-xs rounded-xl border-slate-200 dark:border-slate-800 cursor-pointer"
        >
          Batal
        </Button>
        <Button
          v-if="!isSelf"
          type="button"
          @click="confirmDelete"
          class="text-xs rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-md shadow-rose-600/20 cursor-pointer transition-all active:scale-95"
        >
          Hapus Pengguna
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
