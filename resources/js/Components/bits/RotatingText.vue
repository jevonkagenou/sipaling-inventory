<template>
  <span
    :class="['inline-flex items-center overflow-hidden align-middle select-none', props.mainClassName]"
    ref="containerRef"
  >
    <span class="sr-only">{{ currentText }}</span>
    <AnimatePresence :mode="animatePresenceMode" :initial="animatePresenceInitial">
      <Motion
        :key="currentTextIndex"
        as="span"
        :class="['inline-flex items-center whitespace-nowrap', props.splitLevelClassName]"
      >
        <template v-for="(wordObj, wordIndex) in words" :key="wordIndex">
          <span :class="['inline-flex items-center', props.splitLevelClassName]">
            <template v-for="(charObj, charIndex) in wordObj.chars" :key="charIndex">
              <Motion
                as="span"
                :class="['inline-block', props.elementLevelClassName]"
                :initial="props.initial"
                :animate="props.animate"
                :exit="props.exit"
                :transition="{
                  ...props.transition,
                  delay: getStaggerDelay(charObj.globalIndex, totalElements)
                }"
              >
                {{ charObj.char }}
              </Motion>
            </template>
          </span>
          <span v-if="wordIndex < words.length - 1" class="inline-block">&nbsp;</span>
        </template>
      </Motion>
    </AnimatePresence>
  </span>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, type PropType } from 'vue';
import { AnimatePresence, Motion } from 'motion-v';

const props = defineProps({
  texts: {
    type: Array as PropType<string[]>,
    required: true,
    default: () => []
  },
  transition: {
    type: Object,
    default: () => ({ type: 'spring', damping: 25, stiffness: 350 })
  },
  initial: {
    type: Object,
    default: () => ({ y: '100%', opacity: 0 })
  },
  animate: {
    type: Object,
    default: () => ({ y: 0, opacity: 1 })
  },
  exit: {
    type: Object,
    default: () => ({ y: '-120%', opacity: 0 })
  },
  animatePresenceMode: {
    type: String as PropType<'sync' | 'wait'>,
    default: 'wait'
  },
  animatePresenceInitial: {
    type: Boolean,
    default: false
  },
  rotationInterval: {
    type: Number,
    default: 2000
  },
  staggerDuration: {
    type: Number,
    default: 0.025
  },
  staggerFrom: {
    type: [String, Number] as PropType<'first' | 'last' | 'center' | 'random' | number>,
    default: 'first'
  },
  loop: {
    type: Boolean,
    default: true
  },
  auto: {
    type: Boolean,
    default: true
  },
  splitBy: {
    type: String as PropType<'characters' | 'words'>,
    default: 'characters'
  },
  mainClassName: {
    type: String,
    default: ''
  },
  splitLevelClassName: {
    type: String,
    default: ''
  },
  elementLevelClassName: {
    type: String,
    default: ''
  }
});

const currentTextIndex = ref(0);
const containerRef = ref<HTMLElement | null>(null);
let intervalId: ReturnType<typeof setInterval> | null = null;

const currentText = computed(() => props.texts[currentTextIndex.value] || '');

const words = computed(() => {
  const text = currentText.value;
  if (!text) return [];

  if (props.splitBy === 'words') {
    const splitWords = text.split(' ');
    let globalIndex = 0;
    return splitWords.map(word => ({
      chars: [{ char: word, globalIndex: globalIndex++ }]
    }));
  }

  const splitWords = text.split(' ');
  let globalIndex = 0;
  return splitWords.map(word => ({
    chars: word.split('').map(char => ({
      char,
      globalIndex: globalIndex++
    }))
  }));
});

const totalElements = computed(() => {
  return words.value.reduce((acc, word) => acc + word.chars.length, 0);
});

function getStaggerDelay(index: number, total: number): number {
  const duration = props.staggerDuration;
  if (props.staggerFrom === 'last') {
    return (total - 1 - index) * duration;
  }
  if (props.staggerFrom === 'center') {
    const center = Math.floor(total / 2);
    return Math.abs(index - center) * duration;
  }
  if (props.staggerFrom === 'random') {
    return Math.random() * duration * total;
  }
  if (typeof props.staggerFrom === 'number') {
    return Math.abs(index - props.staggerFrom) * duration;
  }
  return index * duration;
}

function next() {
  if (props.texts.length <= 1) return;
  if (currentTextIndex.value < props.texts.length - 1) {
    currentTextIndex.value++;
  } else if (props.loop) {
    currentTextIndex.value = 0;
  }
}

onMounted(() => {
  if (props.auto && props.texts.length > 1) {
    intervalId = setInterval(next, props.rotationInterval);
  }
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});

defineExpose({
  next,
  currentTextIndex
});
</script>
