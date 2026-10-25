<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogFooter,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'

const props = defineProps({
  open: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:open', 'submitted'])

const form = useForm({ general_notes: '', items: [] })

function buildRows() {
  form.items = props.items.map((it) => ({
    product_id: it.id,
    sku: it.sku,
    name: it.name,
    unit: it.unit,
    current_stock: it.current_stock,
    safety_stock: it.minimum_stock,
    suggested_quantity: it.suggested_quantity,
    requested_quantity: Math.max(it.suggested_quantity || 0, 1),
    estimated_unit_price: Number(it.unit_price) || 0,
    notes: '',
  }))
  form.general_notes = ''
  form.clearErrors()
}

watch(() => props.open, (isOpen) => {
  if (isOpen) buildRows()
})

const formatMoney = (val) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(val) || 0)

const subtotal = (row) =>
  (Number(row.requested_quantity) || 0) * (Number(row.estimated_unit_price) || 0)

const totalCost = computed(() => form.items.reduce((sum, row) => sum + subtotal(row), 0))

const isValid = computed(
  () => form.items.length > 0 && form.items.every((r) => Number(r.requested_quantity) >= 1),
)

const endpointReady = computed(() => {
  try {
    return route().has('restock.store')
  } catch (e) {
    return false
  }
})

function close(value) {
  if (!form.processing) emit('update:open', value)
}

function submit() {
  if (!isValid.value || !endpointReady.value) return
  form
    .transform((data) => ({
      general_notes: data.general_notes,
      items: data.items.map((r) => ({
        product_id: r.product_id,
        current_stock: r.current_stock,
        safety_stock: r.safety_stock,
        suggested_quantity: r.suggested_quantity,
        requested_quantity: Number(r.requested_quantity),
        estimated_unit_price: Number(r.estimated_unit_price),
        notes: r.notes,
      })),
    }))
    .post(route('restock.store'), {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('submitted')
      },
    })
}
</script>

<template>
  <Dialog :open="open" @update:open="close">
    <DialogContent class="sm:max-w-4xl">
      <DialogHeader>
        <DialogTitle>Ajukan Restock ({{ form.items.length }} produk)</DialogTitle>
        <DialogDescription>
          Jumlah diminta terisi otomatis dari rekomendasi DES dan dapat disesuaikan sebelum dikirim ke Komisaris.
        </DialogDescription>
      </DialogHeader>

      <div class="max-h-[45vh] overflow-auto rounded-xl border border-slate-200 dark:border-slate-800">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 font-semibold">
            <tr>
              <th class="py-2.5 px-3 text-left">Produk</th>
              <th class="py-2.5 px-3 text-right">Stok</th>
              <th class="py-2.5 px-3 text-right">Safety</th>
              <th class="py-2.5 px-3 text-right">Saran DES</th>
              <th class="py-2.5 px-3 text-right w-28">Jumlah Diminta</th>
              <th class="py-2.5 px-3 text-right">Harga Satuan</th>
              <th class="py-2.5 px-3 text-right">Subtotal</th>
              <th class="py-2.5 px-3 text-left w-40">Catatan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="(row, i) in form.items" :key="row.product_id" class="align-top">
              <td class="py-2.5 px-3">
                <p class="font-semibold text-slate-900 dark:text-white">{{ row.name }}</p>
                <span class="font-mono text-[10px] text-slate-400">{{ row.sku }}</span>
              </td>
              <td class="py-2.5 px-3 text-right tabular-nums">{{ row.current_stock }} {{ row.unit }}</td>
              <td class="py-2.5 px-3 text-right tabular-nums text-slate-500">{{ row.safety_stock }}</td>
              <td class="py-2.5 px-3 text-right tabular-nums font-semibold text-[#2563EB]">{{ row.suggested_quantity }}</td>
              <td class="py-2.5 px-3">
                <Input v-model.number="row.requested_quantity" type="number" min="1" class="h-8 text-xs text-right" />
                <p v-if="form.errors[`items.${i}.requested_quantity`]" class="mt-1 text-[11px] text-red-600 dark:text-red-400">
                  {{ form.errors[`items.${i}.requested_quantity`] }}
                </p>
              </td>
              <td class="py-2.5 px-3 text-right tabular-nums">{{ formatMoney(row.estimated_unit_price) }}</td>
              <td class="py-2.5 px-3 text-right tabular-nums font-semibold">{{ formatMoney(subtotal(row)) }}</td>
              <td class="py-2.5 px-3">
                <Input v-model="row.notes" type="text" placeholder="Opsional" class="h-8 text-xs" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-1.5">
        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Catatan Justifikasi (umum)</label>
        <textarea
          v-model="form.general_notes"
          rows="2"
          class="w-full rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3 py-2 text-slate-900 dark:text-slate-100"
        />
        <p v-if="form.errors.items" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.items }}</p>
        <p v-if="form.errors.general_notes" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.general_notes }}</p>
      </div>

      <div class="flex items-center justify-between rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-3">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Estimasi Biaya</span>
        <span class="text-base font-bold tabular-nums text-slate-900 dark:text-white">{{ formatMoney(totalCost) }}</span>
      </div>

      <p v-if="!endpointReady" class="text-[11px] text-amber-600 dark:text-amber-400">
        Endpoint pengiriman (restock.store) belum tersedia. Tombol kirim aktif setelah backend selesai.
      </p>

      <DialogFooter>
        <Button type="button" variant="outline" :disabled="form.processing" @click="close(false)">Batal</Button>
        <Button
          type="button"
          :disabled="!isValid || !endpointReady || form.processing"
          class="bg-[#2563EB] hover:bg-blue-700 text-white"
          @click="submit"
        >
          {{ form.processing ? 'Mengirim...' : 'Kirim Usulan Pengadaan' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>