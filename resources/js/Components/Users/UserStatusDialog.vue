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
import { Power, ShieldAlert, CheckCircle2, UserX, AlertTriangle } from 'lucide-vue-next'

export interface UserItem {
  id: string
  name: string
  email: string
  primary_role: string
  is_active: boolean
}

const props = defineProps<{
  open: boolean
  user: UserItem | null
  currentAuthUserId?: string
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'confirmed'): void
}>()

const isTargetActive = computed(() => props.user?.is_active ?? true)
const isSelf = computed(() => props.user?.id === props.currentAuthUserId)

const nextAction = computed(() => (isTargetActive.value ? 'nonaktifkan' : 'aktifkan'))
const actionTitle = computed(() =>
  isTargetActive.value ? 'Konfirmasi Penonaktifan Akun' : 'Konfirmasi Pengaktifan Akun'
)

function handleClose(val: boolean) {
  emit('update:open', val)
}

function confirmToggle() {
  if (!props.user || isSelf.value) return

  router.patch(
    route('users.toggle-status', props.user.id),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('confirmed')
      },
    }
  )
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-md bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden">
      <!-- Header -->
      <div
        :class="[
          'p-5 border-b',
          isTargetActive
            ? 'bg-rose-50/70 dark:bg-rose-950/40 border-rose-100 dark:border-rose-900/60'
            : 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-100 dark:border-emerald-900/60',
        ]"
      >
        <div class="flex items-center gap-3">
          <div
            :class="[
              'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs',
              isTargetActive
                ? 'bg-rose-100 dark:bg-rose-900/80 text-rose-600 dark:text-rose-300'
                : 'bg-emerald-100 dark:bg-emerald-900/80 text-[#10B981] dark:text-emerald-300',
            ]"
          >
            <UserX v-if="isTargetActive" class="h-5 w-5" />
            <Power v-else class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ actionTitle }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Pembaruan status operasional akun pengguna
            </p>
          </div>
        </div>
      </div>

      <!-- Body Details -->
      <div v-if="user" class="p-5 space-y-3.5 text-xs text-slate-600 dark:text-slate-300">
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/70 border border-slate-100 dark:border-slate-800 space-y-1">
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Nama Pengguna:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ user.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Alamat Email:</span>
            <span class="font-mono text-slate-700 dark:text-slate-300">{{ user.email }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Peran Sistem:</span>
            <span class="capitalize font-semibold text-blue-600 dark:text-blue-400">{{ user.primary_role.replace('-', ' ') }}</span>
          </div>
        </div>

        <div v-if="isSelf" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 flex items-start gap-2">
          <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
          <div>
            <p class="font-semibold">Tindakan Ditolak</p>
            <p class="text-[11px] mt-0.5">Anda tidak dapat menonaktifkan akun sendiri yang sedang digunakan saat ini.</p>
          </div>
        </div>

        <p v-else class="text-slate-600 dark:text-slate-400 leading-relaxed">
          Apakah Anda yakin ingin <strong :class="isTargetActive ? 'text-rose-600' : 'text-emerald-600'">{{ nextAction }}</strong> akun ini?
          <template v-if="isTargetActive">
            Pengguna tidak akan dapat mengakses modul sistem SIPALING sampai akun diaktifkan kembali.
          </template>
          <template v-else>
            Pengguna akan segera dapat login dan menjalankan tugas sesuai peran yang ditetapkan.
          </template>
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
          @click="confirmToggle"
          :class="[
            'text-xs rounded-xl text-white shadow-md cursor-pointer transition-all active:scale-95',
            isTargetActive
              ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20'
              : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20',
          ]"
        >
          <span class="capitalize">Ya, {{ nextAction }} Akun</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
