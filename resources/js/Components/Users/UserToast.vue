<script setup lang="ts">
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next'

export interface ToastState {
  id: number
  type?: 'success' | 'error' | 'info'
  title: string
  description?: string
}

defineProps<{
  toast: ToastState | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()
</script>

<template>
  <Transition
    enter-active-class="transform transition ease-out duration-300"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="toast"
      class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white dark:bg-[#0F172A] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-4 flex items-start gap-3 backdrop-blur-md"
    >
      <div
        :class="[
          'h-8 w-8 rounded-xl flex items-center justify-center shrink-0',
          toast.type === 'error'
            ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60'
            : 'bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60',
        ]"
      >
        <AlertCircle v-if="toast.type === 'error'" class="w-4 h-4" />
        <CheckCircle2 v-else class="w-4 h-4" />
      </div>

      <div class="flex-1 min-w-0">
        <h4 class="text-xs font-bold text-slate-900 dark:text-white">
          {{ toast.title }}
        </h4>
        <p v-if="toast.description" class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-normal">
          {{ toast.description }}
        </p>
      </div>

      <button
        type="button"
        @click="emit('close')"
        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>
  </Transition>
</template>
