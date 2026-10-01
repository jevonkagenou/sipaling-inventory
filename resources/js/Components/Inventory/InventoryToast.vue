<script setup lang="ts">
import { CheckCircle2, X } from 'lucide-vue-next'

export interface ToastState {
  id: number
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
  <Teleport to="body">
    <Transition
      enter-active-class="transform transition duration-300 ease-out"
      enter-from-class="translate-y-4 opacity-0 scale-95"
      enter-to-class="translate-y-0 opacity-100 scale-100"
      leave-active-class="transform transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100 scale-100"
      leave-to-class="translate-y-4 opacity-0 scale-95"
    >
      <div
        v-if="toast"
        class="fixed bottom-6 right-6 z-[100] flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 p-3.5 shadow-2xl backdrop-blur-md max-w-sm select-none"
      >
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] border border-emerald-200/60 dark:border-emerald-800/40">
          <CheckCircle2 class="h-4 w-4" />
        </div>
        <div class="flex-1 pr-2">
          <p class="text-xs font-semibold text-slate-900 dark:text-slate-100">
            {{ toast.title }}
          </p>
          <p v-if="toast.description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
            {{ toast.description }}
          </p>
        </div>
        <button
          type="button"
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
          @click="emit('close')"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>
