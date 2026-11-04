<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import StatCard from '@/Components/StatCard.vue'
import { Boxes, History, CheckCircle2, AlertTriangle } from 'lucide-vue-next'

export type CardKey = 'total' | 'mutasi' | 'aman' | 'reorder'

export interface InventoryStats {
  total_products: number
  total_categories: number
  total_transactions: number
  total_details: number
  reorder_count: number
  aman_count: number
  is_filtered?: boolean
  global_total_products?: number
  global_total_details?: number
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
    <StatCard
      title="Total Barang"
      :value="animated.total_products"
      unit="item"
      :description="stats.is_filtered ? `Terfilter dari ${stats.global_total_products ?? stats.total_products} total barang` : `Dari ${stats.total_categories} kategori barang`"
      color="blue"
      :icon="Boxes"
      :active="activeCard === 'total'"
      @click="emit('select-card', 'total')"
    />

    <!-- 2. Total Mutasi Transaksi -->
    <StatCard
      title="Total Mutasi"
      :value="animated.total_details"
      unit="mutasi"
      :description="stats.is_filtered ? 'Akumulasi mutasi barang terfilter' : `${stats.total_transactions} kali transaksi`"
      color="indigo"
      :icon="History"
      :active="activeCard === 'mutasi'"
      @click="emit('select-card', 'mutasi')"
    />

    <!-- 3. Stok Aman -->
    <StatCard
      title="Stok Aman"
      unit="barang"
      :description="stats.is_filtered ? `${stats.aman_count} dari ${stats.total_products} barang terfilter` : 'Kuantitas di atas batas minimum'"
      color="emerald"
      :icon="CheckCircle2"
      :active="activeCard === 'aman'"
      @click="emit('select-card', 'aman')"
    >
      <template #value>
        <span class="text-[#10B981] dark:text-emerald-400">{{ animated.aman_count }}</span>
      </template>
    </StatCard>

    <!-- 4. Perlu Pengadaan / Reorder -->
    <StatCard
      title="Perlu Pengadaan"
      unit="barang"
      :description="stats.is_filtered ? `${stats.reorder_count} dari ${stats.total_products} barang terfilter` : 'Mendekati atau di bawah batas minimum'"
      color="amber"
      :icon="AlertTriangle"
      :active="activeCard === 'reorder'"
      @click="emit('select-card', 'reorder')"
    >
      <template #value>
        <span class="text-amber-600 dark:text-amber-400">{{ animated.reorder_count }}</span>
      </template>
    </StatCard>
  </div>
</template>
