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
import {
  Upload,
  FileSpreadsheet,
  Download,
  AlertCircle,
  AlertTriangle,
  CheckCircle2,
  X,
  FileText,
  Loader2,
  Users,
} from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'imported'): void
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const isDragging = ref(false)
const isUploading = ref(false)
const errorMessage = ref<string | null>(null)

function handleClose(val: boolean) {
  if (isUploading.value) return
  resetForm()
  emit('update:open', val)
}

function resetForm() {
  selectedFile.value = null
  errorMessage.value = null
  isDragging.value = false
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function onFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    processFile(target.files[0])
  }
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  if (e.dataTransfer && e.dataTransfer.files.length > 0) {
    processFile(e.dataTransfer.files[0])
  }
}

function processFile(file: File) {
  errorMessage.value = null
  const extension = file.name.split('.').pop()?.toLowerCase()
  const validExtensions = ['csv', 'txt', 'xlsx', 'xls']
  if (!validExtensions.includes(extension || '')) {
    errorMessage.value = 'Berkas harus memiliki format .xlsx, .xls, .csv, atau .txt'
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    errorMessage.value = 'Ukuran berkas melebihi batas maksimum 5 MB.'
    return
  }
  selectedFile.value = file
}

function removeFile() {
  selectedFile.value = null
  errorMessage.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function formatBytes(bytes: number) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function submitImport() {
  if (!selectedFile.value) {
    errorMessage.value = 'Silakan pilih berkas CSV terlebih dahulu.'
    return
  }

  isUploading.value = true
  errorMessage.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  router.post(route('users.import'), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isUploading.value = false
      resetForm()
      emit('update:open', false)
      emit('imported')
    },
    onError: (errors: any) => {
      isUploading.value = false
      errorMessage.value = errors.file || errors.error || 'Terjadi kesalahan saat memproses berkas CSV.'
    },
    onFinish: () => {
      isUploading.value = false
    },
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-xl max-h-[90vh] flex flex-col p-0 overflow-hidden bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 shadow-2xl">
      <!-- Header -->
      <div class="p-5 sm:p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 shrink-0 pr-12">
        <div class="flex items-center gap-3.5">
          <div class="h-10 w-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shrink-0 shadow-2xs">
            <Users class="h-5 w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <DialogTitle class="text-base font-bold text-slate-900 dark:text-white">
              Impor Data Pengguna (Excel &amp; CSV)
            </DialogTitle>
            <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
              Daftarkan akun pengguna baru secara massal menggunakan berkas spreadsheet Excel (.xlsx) atau CSV.
            </DialogDescription>
          </div>
        </div>
      </div>

      <!-- Body Content (Scrollable) -->
      <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4 custom-scrollbar">
        <!-- Peringatan Impor Pengguna -->
        <div class="p-3.5 rounded-xl border border-amber-300/80 bg-amber-50/80 dark:border-amber-900/60 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 text-xs space-y-1">
          <div class="flex items-center gap-1.5 font-bold text-amber-800 dark:text-amber-300">
            <AlertTriangle class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
            <span>Pemberitahuan Akun Pengguna</span>
          </div>
          <p class="text-[11px] leading-relaxed text-amber-700 dark:text-amber-300/90">
            Akun yang diimpor akan langsung berstatus aktif dan terverifikasi. Pastikan alamat email unik serta peran pengguna sesuai hak akses yang diizinkan sistem.
          </p>
        </div>

        <!-- Download Template Banner -->
        <div class="p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-950/20 space-y-2.5">
          <div class="flex items-center gap-2">
            <FileText class="h-4 w-4 text-[#2563EB] shrink-0" />
            <p class="text-xs font-semibold text-slate-900 dark:text-white">
              Belum memiliki template berkas? Unduh template resmi:
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a
              :href="route('users.import-template', { format: 'xlsx' })"
              class="h-8 px-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-900 text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors shadow-2xs inline-flex items-center gap-1.5"
            >
              <Download class="h-3.5 w-3.5" />
              Template Excel (.xlsx)
            </a>
            <a
              :href="route('users.import-template', { format: 'csv' })"
              class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs inline-flex items-center gap-1.5"
            >
              <Download class="h-3.5 w-3.5" />
              Template CSV
            </a>
          </div>
        </div>

        <!-- Upload Dropzone -->
        <div
          class="border-2 border-dashed rounded-2xl p-5 sm:p-6 text-center transition-all cursor-pointer"
          :class="[
            isDragging
              ? 'border-[#2563EB] bg-blue-50/50 dark:bg-blue-950/30'
              : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30',
            selectedFile ? 'border-emerald-500/50 bg-emerald-50/20 dark:bg-emerald-950/10' : ''
          ]"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="onDrop"
          @click="!selectedFile && fileInputRef?.click()"
        >
          <input
            ref="fileInputRef"
            type="file"
            accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/plain"
            class="hidden"
            @change="onFileSelect"
          />

          <!-- State: No File Selected -->
          <div v-if="!selectedFile" class="space-y-2">
            <div class="mx-auto h-11 w-11 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400 flex items-center justify-center shadow-2xs">
              <Upload class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Tarik &amp; lepas berkas Excel / CSV ke sini, atau <span class="text-[#2563EB] underline underline-offset-2">pilih berkas</span>
              </p>
              <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                Format .xlsx, .xls, .csv, atau .txt (Maksimal 5 MB)
              </p>
            </div>
          </div>

          <!-- State: File Selected -->
          <div v-else class="flex items-center justify-between p-2">
            <div class="flex items-center gap-3 text-left min-w-0">
              <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] flex items-center justify-center shrink-0">
                <CheckCircle2 class="h-5 w-5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                  {{ selectedFile.name }}
                </p>
                <p class="text-[11px] text-slate-400">
                  {{ formatBytes(selectedFile.size) }} &bull; {{ selectedFile.name.endsWith('.xlsx') || selectedFile.name.endsWith('.xls') ? 'Berkas Excel Terpilih' : 'Berkas CSV Terpilih' }}
                </p>
              </div>
            </div>
            <button
              type="button"
              class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
              title="Hapus berkas"
              @click.stop="removeFile"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Error Message Banner -->
        <div v-if="errorMessage" class="flex items-start gap-2.5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs">
          <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-rose-600" />
          <p class="leading-relaxed">{{ errorMessage }}</p>
        </div>

        <!-- Column Specifications Guide -->
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 space-y-1.5">
          <p class="font-semibold text-slate-700 dark:text-slate-300">Struktur Format Kolom:</p>
          <p class="font-mono text-[10px] text-slate-600 dark:text-slate-400">
            name, email, phone, role, password
          </p>
          <div class="text-[10px] space-y-0.5 text-slate-400 dark:text-slate-500">
            <p>&bull; <strong>Kolom Wajib:</strong> <code class="text-slate-600 dark:text-slate-300">name</code>, <code class="text-slate-600 dark:text-slate-300">email</code>, <code class="text-slate-600 dark:text-slate-300">role</code>.</p>
            <p>&bull; <strong>Peran yang Didukung:</strong> <code class="text-slate-600 dark:text-slate-300">staf-gudang</code>, <code class="text-slate-600 dark:text-slate-300">manajer-operasional</code>, <code class="text-slate-600 dark:text-slate-300">komisaris</code>, <code class="text-slate-600 dark:text-slate-300">auditor-internal</code>.</p>
            <p>&bull; <strong>Kata Sandi Default:</strong> Jika kosong, kata sandi otomatis diatur ke <code class="text-slate-600 dark:text-slate-300">Password123!</code>.</p>
            <p>&bull; <strong>Pencegahan Duplikat:</strong> Email yang sudah terdaftar akan otomatis dilewati.</p>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <DialogFooter class="p-4 sm:px-6 bg-slate-50/80 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 flex flex-row items-center justify-end gap-2.5 shrink-0">
        <Button
          type="button"
          variant="outline"
          class="h-9 px-4 rounded-xl text-xs font-semibold border-slate-200 dark:border-slate-800 cursor-pointer"
          :disabled="isUploading"
          @click="handleClose(false)"
        >
          Batal
        </Button>
        <Button
          type="button"
          class="h-9 px-4 rounded-xl text-xs font-semibold bg-[#2563EB] hover:bg-blue-700 text-white shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
          :disabled="!selectedFile || isUploading"
          @click="submitImport"
        >
          <Loader2 v-if="isUploading" class="h-3.5 w-3.5 animate-spin" />
          <Upload v-else class="h-3.5 w-3.5" />
          {{ isUploading ? 'Mengimpor Pengguna...' : 'Mulai Impor Pengguna' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
