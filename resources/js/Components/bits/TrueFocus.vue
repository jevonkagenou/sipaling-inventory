<template>
  <div
    ref="containerRef"
    class="relative inline-flex flex-wrap items-center justify-center gap-x-2.5 sm:gap-x-3 gap-y-1.5 py-1 select-none"
  >
    <!-- Focus Box with 4 Corner Brackets -->
    <div
      v-if="focusBox.width > 0"
      class="absolute pointer-events-none transition-all ease-out"
      :style="{
        transform: `translate3d(${focusBox.x}px, ${focusBox.y}px, 0)`,
        width: `${focusBox.width}px`,
        height: `${focusBox.height}px`,
        transitionDuration: `${animationDuration}s`,
        boxShadow: `0 0 15px ${glowColor}`
      }"
    >
      <!-- Corner Brackets -->
      <span
        class="absolute -top-1 -left-1 w-2.5 h-2.5 border-t-2 border-l-2"
        :style="{ borderColor }"
      />
      <span
        class="absolute -top-1 -right-1 w-2.5 h-2.5 border-t-2 border-r-2"
        :style="{ borderColor }"
      />
      <span
        class="absolute -bottom-1 -left-1 w-2.5 h-2.5 border-b-2 border-l-2"
        :style="{ borderColor }"
      />
      <span
        class="absolute -bottom-1 -right-1 w-2.5 h-2.5 border-b-2 border-r-2"
        :style="{ borderColor }"
      />
    </div>

    <!-- Word Spans -->
    <span
      v-for="(word, index) in words"
      :key="index"
      :ref="el => setWordRef(el, index)"
      class="relative z-10 cursor-pointer transition-all duration-300 font-bold"
      :style="{
        filter: currentIndex === index ? 'blur(0px)' : `blur(${blurAmount}px)`,
        opacity: currentIndex === index ? 1 : 0.7,
        color: currentIndex === index ? '#ffffff' : '#d4d4d8'
      }"
      @mouseenter="handleMouseEnter(index)"
      @mouseleave="handleMouseLeave"
    >
      {{ word }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const props = withDefaults(
  defineProps<{
    sentence: string;
    manualMode?: boolean;
    blurAmount?: number;
    borderColor?: string;
    glowColor?: string;
    animationDuration?: number;
    pauseBetweenAnimations?: number;
  }>(),
  {
    manualMode: false,
    blurAmount: 3,
    borderColor: '#10b981',
    glowColor: 'rgba(16, 185, 129, 0.25)',
    animationDuration: 0.4,
    pauseBetweenAnimations: 1.8
  }
);

const containerRef = ref<HTMLElement | null>(null);
const wordRefs = ref<HTMLElement[]>([]);
const currentIndex = ref(0);
const isHovered = ref(false);

const focusBox = reactive({
  x: 0,
  y: 0,
  width: 0,
  height: 0
});

const words = computed(() => {
  return props.sentence.trim().split(/\s+/);
});

function setWordRef(el: any, index: number) {
  if (el) {
    wordRefs.value[index] = el;
  }
}

function updateFocusBox() {
  const container = containerRef.value;
  const target = wordRefs.value[currentIndex.value];

  if (!container || !target) return;

  const containerRect = container.getBoundingClientRect();
  const targetRect = target.getBoundingClientRect();

  const padding = 6;
  focusBox.x = targetRect.left - containerRect.left - padding;
  focusBox.y = targetRect.top - containerRect.top - padding / 2;
  focusBox.width = targetRect.width + padding * 2;
  focusBox.height = targetRect.height + padding;
}

let timer: ReturnType<typeof setInterval> | null = null;

function startAutoCycle() {
  if (props.manualMode || words.value.length <= 1) return;
  stopAutoCycle();

  const intervalTime = (props.animationDuration + props.pauseBetweenAnimations) * 1000;
  timer = setInterval(() => {
    if (!isHovered.value) {
      currentIndex.value = (currentIndex.value + 1) % words.value.length;
    }
  }, intervalTime);
}

function stopAutoCycle() {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
}

function handleMouseEnter(index: number) {
  isHovered.value = true;
  currentIndex.value = index;
}

function handleMouseLeave() {
  isHovered.value = false;
}

watch(currentIndex, async () => {
  await nextTick();
  updateFocusBox();
});

watch(
  () => props.sentence,
  async () => {
    currentIndex.value = 0;
    await nextTick();
    updateFocusBox();
  }
);

onMounted(async () => {
  await nextTick();
  updateFocusBox();
  startAutoCycle();
  window.addEventListener('resize', updateFocusBox);
});

onUnmounted(() => {
  stopAutoCycle();
  window.removeEventListener('resize', updateFocusBox);
});
</script>
