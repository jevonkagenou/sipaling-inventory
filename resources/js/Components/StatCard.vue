<script setup lang="ts">
import { computed, type Component } from 'vue'
import { Link } from '@inertiajs/vue3'

export type StatCardColor = 'slate' | 'blue' | 'emerald' | 'amber' | 'indigo' | 'purple' | 'rose'

const props = withDefaults(
  defineProps<{
    title: string
    value?: string | number
    unit?: string
    description?: string
    color?: StatCardColor
    icon?: Component
    active?: boolean
    clickable?: boolean
    valueClass?: string
    href?: string
  }>(),
  {
    value: '',
    unit: '',
    description: '',
    color: 'slate',
    icon: undefined,
    active: false,
    clickable: true,
    valueClass: '',
    href: undefined,
  }
)

const emit = defineEmits<{
  (e: 'click'): void
}>()

// Pemetaan utility classes tema warna soft / pastel Tailwind
const colorStyles = computed(() => {
  switch (props.color) {
    case 'emerald':
      return {
        card: props.active
          ? 'bg-emerald-100/80 border-emerald-500 ring-2 ring-[#10B981]/30 dark:bg-emerald-950/50 dark:border-emerald-500'
          : 'bg-emerald-50/70 border-emerald-200/80 hover:bg-emerald-100/60 hover:border-emerald-400/80 dark:bg-emerald-950/20 dark:border-emerald-900/50 dark:hover:border-emerald-700/60',
        iconBox: 'bg-white text-[#10B981] border border-emerald-200/80 shadow-2xs dark:bg-emerald-950/80 dark:text-emerald-400 dark:border-emerald-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-emerald-700/90 dark:text-emerald-400/90',
      }
    case 'blue':
      return {
        card: props.active
          ? 'bg-blue-100/80 border-[#2563EB] ring-2 ring-[#2563EB]/30 dark:bg-blue-950/50 dark:border-blue-500'
          : 'bg-blue-50/70 border-blue-200/80 hover:bg-blue-100/60 hover:border-blue-400/80 dark:bg-blue-950/20 dark:border-blue-900/50 dark:hover:border-blue-700/60',
        iconBox: 'bg-white text-[#2563EB] border border-blue-200/80 shadow-2xs dark:bg-blue-950/80 dark:text-blue-400 dark:border-blue-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-blue-700/90 dark:text-blue-400/90',
      }
    case 'amber':
      return {
        card: props.active
          ? 'bg-amber-100/80 border-amber-500 ring-2 ring-amber-500/30 dark:bg-amber-950/50 dark:border-amber-500'
          : 'bg-amber-50/70 border-amber-200/80 hover:bg-amber-100/60 hover:border-amber-400/80 dark:bg-amber-950/20 dark:border-amber-900/50 dark:hover:border-amber-700/60',
        iconBox: 'bg-white text-amber-600 border border-amber-200/80 shadow-2xs dark:bg-amber-950/80 dark:text-amber-400 dark:border-amber-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-amber-700/90 dark:text-amber-400/90',
      }
    case 'indigo':
      return {
        card: props.active
          ? 'bg-indigo-100/80 border-[#4F46E5] ring-2 ring-indigo-500/30 dark:bg-indigo-950/50 dark:border-indigo-500'
          : 'bg-indigo-50/70 border-indigo-200/80 hover:bg-indigo-100/60 hover:border-indigo-400/80 dark:bg-indigo-950/20 dark:border-indigo-900/50 dark:hover:border-indigo-700/60',
        iconBox: 'bg-white text-[#4F46E5] border border-indigo-200/80 shadow-2xs dark:bg-indigo-950/80 dark:text-indigo-400 dark:border-indigo-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-indigo-700/90 dark:text-indigo-400/90',
      }
    case 'purple':
      return {
        card: props.active
          ? 'bg-purple-100/80 border-purple-500 ring-2 ring-purple-500/30 dark:bg-purple-950/50 dark:border-purple-500'
          : 'bg-purple-50/70 border-purple-200/80 hover:bg-purple-100/60 hover:border-purple-400/80 dark:bg-purple-950/20 dark:border-purple-900/50 dark:hover:border-purple-700/60',
        iconBox: 'bg-white text-purple-600 border border-purple-200/80 shadow-2xs dark:bg-purple-950/80 dark:text-purple-400 dark:border-purple-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-purple-700/90 dark:text-purple-400/90',
      }
    case 'rose':
      return {
        card: props.active
          ? 'bg-rose-100/80 border-rose-500 ring-2 ring-rose-500/30 dark:bg-rose-950/50 dark:border-rose-500'
          : 'bg-rose-50/70 border-rose-200/80 hover:bg-rose-100/60 hover:border-rose-400/80 dark:bg-rose-950/20 dark:border-rose-900/50 dark:hover:border-rose-700/60',
        iconBox: 'bg-white text-rose-600 border border-rose-200/80 shadow-2xs dark:bg-rose-950/80 dark:text-rose-400 dark:border-rose-800/80',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-rose-700/90 dark:text-rose-400/90',
      }
    case 'slate':
    default:
      return {
        card: props.active
          ? 'bg-slate-100 border-slate-400 ring-2 ring-slate-400/30 dark:bg-slate-800 dark:border-slate-500'
          : 'bg-slate-50/80 border-slate-200/90 hover:bg-slate-100/80 hover:border-slate-300 dark:bg-slate-900/50 dark:border-slate-800 dark:hover:border-slate-700',
        iconBox: 'bg-white text-slate-700 border border-slate-200 shadow-2xs dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        title: 'text-slate-800 dark:text-slate-200',
        value: 'text-slate-900 dark:text-white',
        desc: 'text-slate-500 dark:text-slate-400',
      }
  }
})
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    @click="clickable && !href ? emit('click') : undefined"
    class="rounded-2xl border p-5 shadow-2xs transition-all duration-200 select-none group block text-left"
    :class="[
      colorStyles.card,
      clickable ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-md active:scale-[0.99]' : '',
    ]"
  >
    <!-- Baris Atas: Label Judul & Icon Badge -->
    <div class="flex items-center justify-between text-xs font-semibold gap-2">
      <span class="truncate" :class="colorStyles.title">
        <slot name="title">{{ title }}</slot>
      </span>
      <div
        class="h-8 w-8 rounded-xl flex items-center justify-center transition-transform duration-200 shrink-0"
        :class="[
          colorStyles.iconBox,
          clickable ? 'group-hover:scale-110' : '',
        ]"
      >
        <slot name="icon">
          <component :is="icon" v-if="icon" class="h-4 w-4" />
        </slot>
      </div>
    </div>

    <!-- Baris Nilai Angka & Satuan -->
    <div class="mt-2.5 flex items-baseline gap-1.5">
      <span
        class="text-2xl font-bold tracking-tight tabular-nums"
        :class="[colorStyles.value, valueClass]"
      >
        <slot name="value">{{ value }}</slot>
      </span>
      <span v-if="unit || $slots.unit" class="text-xs font-normal text-slate-500 dark:text-slate-400">
        <slot name="unit">{{ unit }}</slot>
      </span>
    </div>

    <!-- Deskripsi / Subteks Bawah -->
    <p
      v-if="description || $slots.description"
      class="text-[11px] mt-1 truncate font-medium"
      :class="colorStyles.desc"
    >
      <slot name="description">{{ description }}</slot>
    </p>
  </component>
</template>
