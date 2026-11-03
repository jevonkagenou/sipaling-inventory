
<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface AuditLog {
  id: number
  description: string
  event: string
  created_at: string
  causer?: {
    name: string
    email?: string
  } | null
  properties?: Record<string, any> | null
  attribute_changes?: Record<string, any> | null
}

const props = defineProps<{
  open: boolean
  log: AuditLog | null
}>()

const emit = defineEmits<{
  (event: 'update:open', value: boolean): void
}>()

const showRawData = ref(false)

watch(
  () => props.open,
  (value) => {
    if (value) showRawData.value = false
  },
)

const properties = computed(() => {
  return props.log?.properties ?? {}
})

const oldValues = computed<Record<string, any>>(() => {
  return properties.value.old ?? {}
})

const newValues = computed<Record<string, any>>(() => {
  return properties.value.attributes ?? {}
})

// Hanya atribut yang benar-benar tercatat berubah yang ditampilkan.
const changes = computed(() => {
  const oldData = oldValues.value
  const newData = newValues.value

  const allKeys = new Set([
    ...Object.keys(oldData),
    ...Object.keys(newData),
  ])

  return Array.from(allKeys)
    .map((key) => {
      const oldExists = Object.prototype.hasOwnProperty.call(oldData, key)
      const newExists = Object.prototype.hasOwnProperty.call(newData, key)

      let status: 'changed' | 'added' | 'removed'

      if (!oldExists && newExists) {
        status = 'added'
      } else if (oldExists && !newExists) {
        status = 'removed'
      } else {
        status = 'changed'
      }

      return {
        key,
        oldValue: oldData[key],
        newValue: newData[key],
        status,
      }
    })
    .filter((item) => {
      if (item.status !== 'changed') return true

      return JSON.stringify(item.oldValue) !==
        JSON.stringify(item.newValue)
    })
})

const hasDiff = computed(() => {
  return changes.value.length > 0
})

const metadata = computed(() => ({
  ip: properties.value.ip,
  userAgent: properties.value.user_agent,
}))

function formatLabel(value: string) {
  return value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatValue(value: any): string {
  if (value === null || value === undefined) return 'Tidak ada'

  if (typeof value === 'object') {
    return JSON.stringify(value, null, 2)
  }

  if (value === '') return '(Kosong)'

  return String(value)
}

function formatDate(value?: string) {
  if (!value) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(new Date(value))
}

function closeDialog() {
  emit('update:open', false)
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && log"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6"
      @click.self="closeDialog"
      @keydown.esc="closeDialog"
    >
      <section
        class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="audit-dialog-title"
      >
        <!-- Header -->
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-700 sm:px-6">
          <div>
            <div class="mb-1 flex items-center gap-2">
              <span class="rounded-md bg-violet-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                Audit Detail
              </span>
              <span class="text-xs text-slate-400">#{{ log.id }}</span>
            </div>

            <h2
              id="audit-dialog-title"
              class="text-lg font-bold text-slate-900 dark:text-white"
            >
              Perbandingan Perubahan Atribut
            </h2>

            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              Detail perubahan data pada rekam jejak aktivitas.
            </p>
          </div>

          <button
            type="button"
            aria-label="Tutup dialog"
            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white"
            @click="closeDialog"
          >
            <span class="text-xl leading-none">&times;</span>
          </button>
        </header>

        <!-- Content -->
        <div class="space-y-5 overflow-y-auto p-5 sm:p-6">
          <!-- Informasi aktivitas -->
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                Aktor
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ log.causer?.name || 'Sistem' }}
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                Waktu Aktivitas
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ formatDate(log.created_at) }}
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                Jenis Aktivitas
              </p>
              <p class="mt-1 text-sm font-semibold capitalize text-slate-800 dark:text-slate-100">
                {{ log.event || '-' }}
              </p>
            </div>
          </div>

          <!-- Deskripsi -->
          <div>
            <p class="mb-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
              Deskripsi Aktivitas
            </p>
            <p class="text-sm text-slate-800 dark:text-slate-200">
              {{ log.description }}
            </p>
          </div>

          <!-- Diff -->
          <div>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                  Perbandingan Nilai Atribut
                </h3>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                  Nilai sebelum dan sesudah perubahan.
                </p>
              </div>

              <span
                v-if="hasDiff"
                class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"
              >
                {{ changes.length }} atribut berubah
              </span>
            </div>

            <div
              v-if="hasDiff"
              class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
            >
              <!-- Column heading -->
              <div class="grid grid-cols-2 border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                <div class="border-r border-slate-200 px-4 py-3 dark:border-slate-700">
                  <span class="flex items-center gap-2 text-xs font-bold text-red-600 dark:text-red-400">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Nilai Sebelumnya
                  </span>
                </div>

                <div class="px-4 py-3">
                  <span class="flex items-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Nilai Terbaru
                  </span>
                </div>
              </div>

              <!-- Visual Diff Attributes -->
<div
  v-for="item in changes"
  :key="item.key"
  class="border-b border-slate-100 last:border-b-0 dark:border-slate-800"
>
  <!-- Nama Atribut dan Status -->
  <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">
      {{ formatLabel(item.key) }}
    </span>

    <span
      v-if="item.status === 'changed'"
      class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"
    >
      CHANGED
    </span>

    <span
      v-else-if="item.status === 'added'"
      class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
    >
      ADDED
    </span>

    <span
      v-else
      class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold text-red-700 dark:bg-red-500/10 dark:text-red-300"
    >
      REMOVED
    </span>
  </div>

  <!-- Changed: Nilai Lama dan Baru -->
  <div
    v-if="item.status === 'changed'"
    class="grid grid-cols-1 sm:grid-cols-2"
  >
    <div class="border-b border-slate-200 bg-red-50/70 p-3 dark:border-slate-700 dark:bg-red-500/5 sm:border-b-0 sm:border-r">
      <p class="mb-1 text-[10px] font-semibold uppercase text-red-600 dark:text-red-400">
        − Nilai Sebelumnya
      </p>
      <pre class="whitespace-pre-wrap break-words font-mono text-xs text-red-800 dark:text-red-300">{{ formatValue(item.oldValue) }}</pre>
    </div>

    <div class="bg-emerald-50/70 p-3 dark:bg-emerald-500/5">
      <p class="mb-1 text-[10px] font-semibold uppercase text-emerald-600 dark:text-emerald-400">
        + Nilai Terbaru
      </p>
      <pre class="whitespace-pre-wrap break-words font-mono text-xs text-emerald-800 dark:text-emerald-300">{{ formatValue(item.newValue) }}</pre>
    </div>
  </div>

  <!-- Added: Hanya Nilai Baru -->
  <div
    v-else-if="item.status === 'added'"
    class="bg-emerald-50/70 p-3 dark:bg-emerald-500/5"
  >
    <p class="mb-1 text-[10px] font-semibold uppercase text-emerald-600 dark:text-emerald-400">
      + Atribut Baru
    </p>
    <pre class="whitespace-pre-wrap break-words font-mono text-xs text-emerald-800 dark:text-emerald-300">{{ formatValue(item.newValue) }}</pre>
  </div>

  <!-- Removed: Hanya Nilai Lama -->
  <div
    v-else
    class="bg-red-50/70 p-3 dark:bg-red-500/5"
  >
    <p class="mb-1 text-[10px] font-semibold uppercase text-red-600 dark:text-red-400">
      − Atribut Dihapus
    </p>
    <pre class="whitespace-pre-wrap break-words font-mono text-xs text-red-800 dark:text-red-300">{{ formatValue(item.oldValue) }}</pre>
                   </div>
        </div>
      </div>
    </div>
        <div
      v-if="!hasDiff"
      class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center dark:border-slate-700 dark:bg-slate-800/40"
    >
      <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
        Tidak ada perubahan atribut
      </p>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        Aktivitas ini tidak memiliki data perubahan nilai sebelum dan sesudah.
      </p>
    </div>

        <!-- Metadata -->
          <div
            v-if="metadata.ip || metadata.userAgent"
            class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"
          >
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Informasi Forensik
            </h3>

            <div v-if="metadata.ip" class="mb-3">
              <p class="text-[10px] font-semibold text-slate-400">IP Address</p>
              <p class="mt-1 break-all font-mono text-xs text-slate-700 dark:text-slate-200">
                {{ metadata.ip }}
              </p>
            </div>

            <div v-if="metadata.userAgent">
              <p class="text-[10px] font-semibold text-slate-400">User Agent</p>
              <p class="mt-1 break-all text-xs text-slate-700 dark:text-slate-200">
                {{ metadata.userAgent }}
              </p>
            </div>
          </div>

          <!-- Raw properties -->
          <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
            <button
              type="button"
              class="text-xs font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400"
              @click="showRawData = !showRawData"
            >
              {{ showRawData ? 'Sembunyikan' : 'Lihat' }} Detail Properties JSON
            </button>

            <pre
              v-if="showRawData"
              class="mt-3 max-h-56 overflow-auto rounded-xl bg-slate-950 p-4 text-[11px] text-emerald-300"
            >{{ JSON.stringify(properties, null, 2) }}</pre>
          </div>
        </div>

        <!-- Footer -->
        <footer class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50 sm:px-6">
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-violet-600 dark:hover:bg-violet-500"
            @click="closeDialog"
          >
            Tutup
          </button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>
