<script setup lang="ts">
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

interface Category {
  id: string
  name: string
}

interface ProductPayload {
  id?: string
  category_id: string
  sku: string
  name: string
  unit: string
  unit_price: number | string
  current_stock: number
  minimum_stock: number
  description?: string | null
}

const props = defineProps<{
  open: boolean
  mode: 'create' | 'edit'
  product?: ProductPayload | null
  categories: Category[]
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved'): void
}>()

const form = useForm({
  category_id: '',
  sku: '',
  name: '',
  unit: '',
  unit_price: '',
  current_stock: 0,
  minimum_stock: 0,
  description: '',
})

function resetForm() {
  if (props.mode === 'edit' && props.product) {
    form.category_id = props.product.category_id
    form.sku = props.product.sku
    form.name = props.product.name
    form.unit = props.product.unit
    form.unit_price = props.product.unit_price
    form.current_stock = props.product.current_stock
    form.minimum_stock = props.product.minimum_stock
    form.description = props.product.description ?? ''
  } else {
    form.reset()
  }
  form.clearErrors()
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) resetForm()
  },
)

const title = computed(() => (props.mode === 'create' ? 'Tambah Barang' : 'Edit Barang'))
const submitLabel = computed(() => (props.mode === 'create' ? 'Simpan Barang' : 'Simpan Perubahan'))

function handleClose(value: boolean) {
  if (!form.processing) emit('update:open', value)
}

function submit() {
  if (props.mode === 'create') {
    form.post(route('inventory.store'), {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('saved')
      },
    })
  } else if (props.product?.id) {
    form.put(route('inventory.update', props.product.id), {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('saved')
      },
    })
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          Lengkapi data barang di bawah ini. Kolom bertanda (*) wajib diisi.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Kode SKU *</label>
            <Input v-model="form.sku" placeholder="Contoh: ITM-011" class="text-xs" />
            <p v-if="form.errors.sku" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.sku }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Kategori *</label>
            <select
              v-model="form.category_id"
              class="w-full h-9 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3 text-slate-900 dark:text-slate-100"
            >
              <option value="" disabled>Pilih kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <p v-if="form.errors.category_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.category_id }}</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Nama Barang *</label>
          <Input v-model="form.name" placeholder="Contoh: Laptop ThinkPad T14" class="text-xs" />
          <p v-if="form.errors.name" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Satuan *</label>
            <Input v-model="form.unit" placeholder="pcs / box / kg" class="text-xs" />
            <p v-if="form.errors.unit" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.unit }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Harga Satuan *</label>
            <Input v-model="form.unit_price" type="number" step="0.01" min="0" class="text-xs" />
            <p v-if="form.errors.unit_price" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.unit_price }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Stok Saat Ini *</label>
            <Input v-model.number="form.current_stock" type="number" min="0" class="text-xs" />
            <p v-if="form.errors.current_stock" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.current_stock }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Batas Minimum *</label>
            <Input v-model.number="form.minimum_stock" type="number" min="0" class="text-xs" />
            <p v-if="form.errors.minimum_stock" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.minimum_stock }}</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3 py-2 text-slate-900 dark:text-slate-100"
          />
        </div>

        <DialogFooter class="pt-2">
          <Button type="button" variant="outline" :disabled="form.processing" @click="handleClose(false)">
            Batal
          </Button>
          <Button type="submit" :disabled="form.processing" class="bg-[#2563EB] hover:bg-blue-700 text-white">
            {{ form.processing ? 'Menyimpan...' : submitLabel }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>