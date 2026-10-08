<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import {
  FolderTree,
  Plus,
  Pencil,
  Trash2,
  Boxes,
  CheckCircle2,
  AlertCircle,
  X,
  Layers,
} from 'lucide-vue-next'

export interface CategoryItem {
  id: string
  name: string
  slug: string
  description?: string | null
  products_count?: number
}

const props = defineProps<{
  open: boolean
  categories: CategoryItem[]
}>()

const emit = defineEmits<{
  (e: 'update:open', val: boolean): void
  (e: 'saved'): void
  (e: 'toast', title: string, description?: string): void
}>()

const editingCategory = ref<CategoryItem | null>(null)
const deletingCategory = ref<CategoryItem | null>(null)

const form = useForm({
  name: '',
  description: '',
})

function resetForm() {
  editingCategory.value = null
  deletingCategory.value = null
  form.reset()
  form.clearErrors()
}

watch(
  () => props.open,
  (val) => {
    if (!val) resetForm()
  }
)

function startEdit(cat: CategoryItem) {
  editingCategory.value = cat
  deletingCategory.value = null
  form.name = cat.name
  form.description = cat.description ?? ''
  form.clearErrors()
}

function cancelEdit() {
  editingCategory.value = null
  form.reset()
  form.clearErrors()
}

function submitForm() {
  if (editingCategory.value) {
    form.put(route('categories.update', editingCategory.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        emit('toast', 'Kategori Diperbarui', `Kategori "${form.name}" berhasil diperbarui.`)
        cancelEdit()
        emit('saved')
      },
    })
  } else {
    form.post(route('categories.store'), {
      preserveScroll: true,
      onSuccess: () => {
        emit('toast', 'Kategori Ditambahkan', `Kategori "${form.name}" berhasil dibuat.`)
        form.reset()
        emit('saved')
      },
    })
  }
}

function promptDelete(cat: CategoryItem) {
  if ((cat.products_count ?? 0) > 0) {
    emit(
      'toast',
      'Tidak Dapat Menghapus',
      `Kategori "${cat.name}" masih digunakan oleh ${cat.products_count} produk master.`
    )
    return
  }
  deletingCategory.value = cat
}

function cancelDelete() {
  deletingCategory.value = null
}

function executeDelete(cat: CategoryItem) {
  router.delete(route('categories.destroy', cat.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('toast', 'Kategori Dihapus', `Kategori "${cat.name}" telah dihapus.`)
      deletingCategory.value = null
      emit('saved')
    },
    onError: (errors) => {
      emit(
        'toast',
        'Gagal Menghapus',
        (errors.category_error as string) || (errors.error as string) || 'Terjadi kesalahan sistem.'
      )
    },
  })
}

function handleClose(val: boolean) {
  if (!form.processing) {
    emit('update:open', val)
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-2xl bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 p-0 overflow-hidden">
      <!-- Header -->
      <div class="p-5 border-b bg-slate-50/70 dark:bg-slate-900/60 border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/60">
            <FolderTree class="h-5 w-5" />
          </div>
          <div>
            <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
              Kelola Master Kategori
            </DialogTitle>
            <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Tambah divisi klasifikasi barang, ubah detail, dan pantau keterikatan produk.
            </DialogDescription>
          </div>
        </div>
      </div>

      <!-- Main Body: Two Columns (Form + List) -->
      <div class="p-5 grid grid-cols-1 md:grid-cols-12 gap-5 max-h-[75vh] overflow-y-auto">
        <!-- Form Section (5 Cols) -->
        <div class="md:col-span-5 space-y-4 rounded-xl p-4 bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800 self-start">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
              <span v-if="editingCategory" class="text-blue-600 dark:text-blue-400">Edit Kategori</span>
              <span v-else>Tambah Kategori Baru</span>
            </h4>
            <button
              v-if="editingCategory"
              type="button"
              @click="cancelEdit"
              class="text-[11px] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center gap-1"
            >
              <X class="w-3 h-3" /> Batal
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-3">
            <div class="space-y-1">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Nama Kategori <span class="text-rose-500">*</span>
              </label>
              <Input
                v-model="form.name"
                placeholder="Contoh: Elektronik & IT"
                class="text-xs h-9 rounded-xl"
                :disabled="form.processing"
              />
              <p v-if="form.errors.name" class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">
                {{ form.errors.name }}
              </p>
            </div>

            <div class="space-y-1">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Deskripsi
              </label>
              <Input
                v-model="form.description"
                placeholder="Penjelasan ringkas kategori..."
                class="text-xs h-9 rounded-xl"
                :disabled="form.processing"
              />
              <p v-if="form.errors.description" class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">
                {{ form.errors.description }}
              </p>
            </div>

            <Button
              type="submit"
              :disabled="form.processing || !form.name.trim()"
              class="w-full text-xs h-9 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-medium transition-all mt-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="form.processing">Menyimpan...</span>
              <span v-else-if="editingCategory">Simpan Perubahan</span>
              <span v-else class="flex items-center gap-1.5"><Plus class="w-3.5 h-3.5" /> Tambah Kategori</span>
            </Button>
          </form>
        </div>

        <!-- List Section (7 Cols) -->
        <div class="md:col-span-7 space-y-3">
          <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Daftar Kategori Terdaftar ({{ categories.length }})
            </span>
            <span class="text-[10px] text-slate-400">Total Kategori</span>
          </div>

          <div class="space-y-2">
            <div
              v-for="cat in categories"
              :key="cat.id"
              class="p-3 rounded-xl border transition-all"
              :class="editingCategory?.id === cat.id
                ? 'bg-blue-50/50 dark:bg-blue-950/30 border-blue-300 dark:border-blue-800/80 shadow-xs'
                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="space-y-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-xs text-slate-900 dark:text-white truncate">
                      {{ cat.name }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-500">
                      {{ cat.slug }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                    {{ cat.description || 'Tidak ada deskripsi.' }}
                  </p>
                  <div class="flex items-center gap-1.5 pt-0.5">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border"
                      :class="(cat.products_count ?? 0) > 0
                        ? 'bg-emerald-50 dark:bg-emerald-950/40 text-[#10B981] dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-800/40'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'"
                    >
                      <Boxes class="w-3 h-3" />
                      {{ cat.products_count ?? 0 }} produk terkait
                    </span>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1 shrink-0">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/60 cursor-pointer"
                    title="Edit Kategori"
                    @click="startEdit(cat)"
                  >
                    <Pencil class="h-3.5 w-3.5" />
                  </Button>

                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                    :title="(cat.products_count ?? 0) > 0 ? 'Kategori tidak dapat dihapus karena masih digunakan produk' : 'Hapus Kategori'"
                    :disabled="(cat.products_count ?? 0) > 0"
                    @click="promptDelete(cat)"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                  </Button>
                </div>
              </div>

              <!-- Inline Delete Confirmation Box -->
              <div
                v-if="deletingCategory?.id === cat.id"
                class="mt-2.5 pt-2 border-t border-rose-100 dark:border-rose-900/60 flex items-center justify-between gap-2 bg-rose-50/70 dark:bg-rose-950/40 p-2 rounded-lg"
              >
                <div class="flex items-center gap-1.5 text-[11px] text-rose-700 dark:text-rose-300 font-medium">
                  <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                  <span>Yakin hapus kategori ini?</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <Button
                    size="sm"
                    variant="ghost"
                    class="h-6 px-2 text-[10px] text-slate-600 dark:text-slate-300"
                    @click="cancelDelete"
                  >
                    Batal
                  </Button>
                  <Button
                    size="sm"
                    class="h-6 px-2 text-[10px] bg-rose-600 hover:bg-rose-700 text-white font-semibold"
                    @click="executeDelete(cat)"
                  >
                    Ya, Hapus
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
