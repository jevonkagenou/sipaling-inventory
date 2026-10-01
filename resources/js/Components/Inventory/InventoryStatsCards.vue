<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Boxes, History, CheckCircle2, AlertTriangle } from 'lucide-vue-next'

export type CardKey = 'total' | 'mutasi' | 'aman' | 'reorder'

export interface InventoryStats {
  total_products: number
  total_categories: number
  total_transactions: number
  total_details: number
  reorder_count: number
  aman_count: number
}

const props = defineProps<{
  stats: InventoryStats
  activeCard: CardKey | null
}>()

const emit = defineEmits<{
  (e: 'select-card', key: CardKey): void
}>()

// Angka statistik beranimasi count-up
const animated = ref({
  total_products: 0,
  total_details: 0,
  aman_count: 0,
  reorder_count: 0,
})

function triggerAnimation() {
  const target = {
    total_products: props.stats.total_products,
    total_details: props.stats.total_details,
    aman_count: props.stats.aman_count,
    reorder_count: props.stats.reorder_count,
  }

  if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    animated.value = target
    return
  }

  const duration = 800
  const start = performance.now()
  const tick = (now: number) => {
    const p = Math.min((now - start) / duration, 1)
    const e = 1 - Math.pow(1 - p, 3) // easeOutCubic
    animated.value = {
      total_products: Math.round(target.total_products * e),
      total_details: Math.round(target.total_details * e),
      aman_count: Math.round(target.aman_count * e),
      reorder_count: Math.round(target.reorder_count * e),
    }
    if (p < 1) requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

onMounted(() => {
  triggerAnimation()
})

watch(() => props.stats, () => {
  triggerAnimation()
}, { deep: true })
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- 1. Total Barang -->
    <Card
      role="button"
      tabindex="0"
      title="Klik untuk memfilter semua barang"
      @click="emit('select-card', 'total')"
      @keydown.enter="emit('select-card', 'total')"
      :class="activeCard === 'total'
        ? 'ring-2 ring-[#2563EB] border-[#2563EB]'
        : 'border-slate-200 dark:border-slate-800 hover:border-[#2563EB]/50'"
      class="group cursor-pointer select-none rounded-xl border bg-white dark:bg-slate-900 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB]"
    >
      <CardHeader class="pb-1.5">
        <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
          Total Barang
        </CardTitle>
        <CardAction>
          <div class="p-2 rounded-lg bg-[#2563EB]/10 text-[#2563EB] dark:text-blue-400 transition-transform duration-200 group-hover:scale-110">
            <Boxes class="w-4 h-4" />
          </div>
        </CardAction>
      </CardHeader>
      <CardContent class="pt-0">
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
            {{ animated.total_products }}
          </span>
          <span class="text-xs text-slate-500">item</span>
        </div>
        <CardDescription class="mt-1 text-xs text-slate-400">
          Dari {{ stats.total_categories }} kategori barang
        </CardDescription>
      </CardContent>
    </Card>

    <!-- 2. Total Mutasi Transaksi -->
    <Card
      role="button"
      tabindex="0"
      title="Klik untuk mengurutkan mutasi terbanyak"
      @click="emit('select-card', 'mutasi')"
      @keydown.enter="emit('select-card', 'mutasi')"
      :class="activeCard === 'mutasi'
        ? 'ring-2 ring-[#4F46E5] border-[#4F46E5]'
        : 'border-slate-200 dark:border-slate-800 hover:border-[#4F46E5]/50'"
      class="group cursor-pointer select-none rounded-xl border bg-white dark:bg-slate-900 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]"
    >
      <CardHeader class="pb-1.5">
        <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
          Total Mutasi
        </CardTitle>
        <CardAction>
          <div class="p-2 rounded-lg bg-[#4F46E5]/10 text-[#4F46E5] dark:text-indigo-400 transition-transform duration-200 group-hover:scale-110">
            <History class="w-4 h-4" />
          </div>
        </CardAction>
      </CardHeader>
      <CardContent class="pt-0">
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
            {{ animated.total_details }}
          </span>
          <span class="text-xs text-slate-500">mutasi</span>
        </div>
        <CardDescription class="mt-1 text-xs text-slate-400">
          {{ stats.total_transactions }} kali transaksi
        </CardDescription>
      </CardContent>
    </Card>

    <!-- 3. Stok Aman -->
    <Card
      role="button"
      tabindex="0"
      title="Klik untuk memfilter barang stok aman"
      @click="emit('select-card', 'aman')"
      @keydown.enter="emit('select-card', 'aman')"
      :class="activeCard === 'aman'
        ? 'ring-2 ring-[#10B981] border-[#10B981]'
        : 'border-slate-200 dark:border-slate-800 hover:border-[#10B981]/50'"
      class="group cursor-pointer select-none rounded-xl border bg-white dark:bg-slate-900 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]"
    >
      <CardHeader class="pb-1.5">
        <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
          Stok Aman
        </CardTitle>
        <CardAction>
          <div class="p-2 rounded-lg bg-[#10B981]/10 text-[#10B981] dark:text-emerald-400 transition-transform duration-200 group-hover:scale-110">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </CardAction>
      </CardHeader>
      <CardContent class="pt-0">
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-bold tracking-tight text-[#10B981] dark:text-emerald-400 tabular-nums">
            {{ animated.aman_count }}
          </span>
          <span class="text-xs text-slate-500">barang</span>
        </div>
        <CardDescription class="mt-1 text-xs text-slate-400">
          Kuantitas di atas batas minimum
        </CardDescription>
      </CardContent>
    </Card>

    <!-- 4. Perlu Pengadaan / Reorder -->
    <Card
      role="button"
      tabindex="0"
      title="Klik untuk memfilter barang yang perlu restock"
      @click="emit('select-card', 'reorder')"
      @keydown.enter="emit('select-card', 'reorder')"
      :class="activeCard === 'reorder'
        ? 'ring-2 ring-amber-500 border-amber-500'
        : 'border-slate-200 dark:border-slate-800 hover:border-amber-500/50'"
      class="group cursor-pointer select-none rounded-xl border bg-white dark:bg-slate-900 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
    >
      <CardHeader class="pb-1.5">
        <CardTitle class="text-xs font-medium text-slate-500 dark:text-slate-400">
          Perlu Pengadaan
        </CardTitle>
        <CardAction>
          <div class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 transition-transform duration-200 group-hover:scale-110">
            <AlertTriangle class="w-4 h-4" />
          </div>
        </CardAction>
      </CardHeader>
      <CardContent class="pt-0">
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
            {{ animated.reorder_count }}
          </span>
          <span class="text-xs text-slate-500">barang</span>
        </div>
        <CardDescription class="mt-1 text-xs text-slate-400">
          Mendekati atau di bawah batas minimum
        </CardDescription>
      </CardContent>
    </Card>
  </div>
</template>
