<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogFooter,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Trash2, AlertTriangle, ShieldAlert } from 'lucide-vue-next'

export interface ProductItem {
  id: string
  sku: string
  name: string
  unit: string
  current_stock: number
  minimum_stock: number
  transaction_details_count?: number
  category?: { id: string; name: string }
}

const props = defineProps<{
  open: boolean
  product: ProductItem | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'deleted'): void
  (e: 'error', message: string): void
}>()

const hasTransactions = computed(() => {
  return (props.product?.transaction_details_count ?? 0) > 0
})

function handleClose(val: boolean) {
  emit('update:open', val)
}

function confirmDelete() {
  if (!props.product || hasTransactions.value) return

  router.delete(route('inventory.destroy', props.product.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:open', false)
      emit('deleted')
    },
    onError: (errors) => {
      emit('error', (errors.error as string) || 'Terjadi kesalahan saat menghapus barang.')
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
              Hapus Barang Inventaris
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Pengecekan aturan integritas data inventaris
            </p>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div v-if="product" class="p-5 space-y-3.5 text-xs text-slate-600 dark:text-slate-300">
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/70 border border-slate-100 dark:border-slate-800 space-y-1.5">
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Kode SKU:</span>
            <span class="font-mono font-semibold px-1.5 py-0.5 rounded bg-slate-200/60 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[11px]">{{ product.sku }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Nama Barang:</span>
            <span class="font-bold text-slate-900 dark:text-white truncate max-w-[220px]" :title="product.name">{{ product.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Stok Saat Ini:</span>
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ product.current_stock }} {{ product.unit || 'pcs' }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400 font-medium">Riwayat Mutasi:</span>
            <span class="font-semibold" :class="hasTransactions ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500'">
              {{ product.transaction_details_count ?? 0 }}x transaksi
            </span>
          </div>
        </div>

        <!-- Warning jika memiliki riwayat mutasi (Safety Guard Restrict Delete) -->
        <div v-if="hasTransactions" class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80 text-amber-900 dark:text-amber-200 flex items-start gap-2.5">
          <ShieldAlert class="w-5 h-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
          <div class="space-y-1">
            <p class="font-bold text-xs">Penghapusan Dibatasi (Restrict Delete)</p>
            <p class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
              Barang ini memiliki <strong>{{ product.transaction_details_count }}</strong> data riwayat mutasi stok. Untuk menjaga integritas pembukuan dan audit trail, barang yang pernah bertransaksi tidak dapat dihapus.
            </p>
          </div>
        </div>

        <p v-else class="text-slate-600 dark:text-slate-400 leading-relaxed">
          Apakah Anda yakin ingin menghapus produk <strong class="text-slate-900 dark:text-white">{{ product.name }}</strong> (SKU: {{ product.sku }})? Tindakan ini bersifat permanen dan akan dicatat di log audit sistem.
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
          Tutup
        </Button>
        <Button
          v-if="!hasTransactions"
          type="button"
          @click="confirmDelete"
          class="text-xs rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-md shadow-rose-600/20 cursor-pointer transition-all active:scale-95"
        >
          Hapus Barang
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
