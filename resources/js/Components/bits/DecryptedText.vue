<template>
  <span ref="containerRef" :class="`inline-block whitespace-pre-wrap ${parentClassName}`" v-bind="animateListeners">
    <span class="sr-only">{{ displayText }}</span>

    <span aria-hidden="true">
      <span
        v-for="(char, index) in displayText.split('')"
        :key="index"
        :class="isRevealedOrDone(index) ? className : encryptedClassName"
      >
        {{ char }}
      </span>
    </span>
  </span>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  text: {
    type: String,
    required: true,
  },
  speed: {
    type: Number,
    default: 45,
  },
  maxIterations: {
    type: Number,
    default: 12,
  },
  sequential: {
    type: Boolean,
    default: true,
  },
  revealDirection: {
    type: String,
    default: 'start', // 'start' | 'end' | 'center'
  },
  useOriginalCharsOnly: {
    type: Boolean,
    default: false,
  },
  characters: {
    type: String,
    default: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+',
  },
  className: {
    type: String,
    default: '',
  },
  encryptedClassName: {
    type: String,
    default: 'text-sky-500 font-mono opacity-80',
  },
  parentClassName: {
    type: String,
    default: '',
  },
  animateOn: {
    type: String,
    default: 'view', // 'view' | 'hover' | 'inViewHover' | 'click'
  },
  clickMode: {
    type: String,
    default: 'once',
  },
});

const containerRef = ref(null);
const displayText = ref(props.text);
const isAnimating = ref(false);
const revealedIndices = ref(new Set());
const hasAnimated = ref(false);
const isDecrypted = ref(props.animateOn !== 'click');
const direction = ref('forward');

const orderRef = ref([]);
const pointerRef = ref(0);
let intervalId = null;

const availableChars = computed(() => {
  return props.useOriginalCharsOnly
    ? Array.from(new Set(props.text.split(''))).filter((char) => char !== ' ')
    : props.characters.split('');
});

function shuffleText(originalText, currentRevealed) {
  return originalText
    .split('')
    .map((char, i) => {
      if (char === ' ') return ' ';
      if (currentRevealed.has(i)) return originalText[i];
      return availableChars.value[Math.floor(Math.random() * availableChars.value.length)];
    })
    .join('');
}

function computeOrder(len) {
  const order = [];
  if (len <= 0) return order;
  if (props.revealDirection === 'start') {
    for (let i = 0; i < len; i++) order.push(i);
    return order;
  }
  if (props.revealDirection === 'end') {
    for (let i = len - 1; i >= 0; i--) order.push(i);
    return order;
  }
  const middle = Math.floor(len / 2);
  let offset = 0;
  while (order.length < len) {
    if (offset % 2 === 0) {
      const idx = middle + offset / 2;
      if (idx >= 0 && idx < len) order.push(idx);
    } else {
      const idx = middle - Math.ceil(offset / 2);
      if (idx >= 0 && idx < len) order.push(idx);
    }
    offset++;
  }
  return order.slice(0, len);
}

function stopInterval() {
  if (intervalId !== null) {
    clearInterval(intervalId);
    intervalId = null;
  }
}

function getNextIndex(revealedSet) {
  const textLength = props.text.length;
  switch (props.revealDirection) {
    case 'start':
      return revealedSet.size;
    case 'end':
      return textLength - 1 - revealedSet.size;
    case 'center': {
      const middle = Math.floor(textLength / 2);
      const offset = Math.floor(revealedSet.size / 2);
      const nextIndex = revealedSet.size % 2 === 0 ? middle + offset : middle - offset - 1;
      if (nextIndex >= 0 && nextIndex < textLength && !revealedSet.has(nextIndex)) {
        return nextIndex;
      }
      for (let i = 0; i < textLength; i++) {
        if (!revealedSet.has(i)) return i;
      }
      return 0;
    }
    default:
      return revealedSet.size;
  }
}

function triggerDecrypt() {
  if (props.sequential) {
    orderRef.value = computeOrder(props.text.length);
    pointerRef.value = 0;
    revealedIndices.value = new Set();
  } else {
    revealedIndices.value = new Set();
  }
  direction.value = 'forward';
  isAnimating.value = true;
}

function startInterval() {
  stopInterval();
  let currentIteration = 0;

  intervalId = setInterval(() => {
    if (props.sequential) {
      if (direction.value === 'forward') {
        if (revealedIndices.value.size < props.text.length) {
          const nextIndex = getNextIndex(revealedIndices.value);
          const newRevealed = new Set(revealedIndices.value);
          newRevealed.add(nextIndex);
          revealedIndices.value = newRevealed;
          displayText.value = shuffleText(props.text, newRevealed);
        } else {
          stopInterval();
          isAnimating.value = false;
          isDecrypted.value = true;
          displayText.value = props.text;
        }
      }
    } else {
      displayText.value = shuffleText(props.text, revealedIndices.value);
      currentIteration++;
      if (currentIteration >= props.maxIterations) {
        stopInterval();
        isAnimating.value = false;
        displayText.value = props.text;
        isDecrypted.value = true;
      }
    }
  }, props.speed);
}

watch(isAnimating, (val) => {
  if (val) startInterval();
  else stopInterval();
});

function triggerHoverDecrypt() {
  if (isAnimating.value) return;
  revealedIndices.value = new Set();
  isDecrypted.value = false;
  displayText.value = props.text;
  direction.value = 'forward';
  isAnimating.value = true;
}

function resetToPlainText() {
  stopInterval();
  isAnimating.value = false;
  revealedIndices.value = new Set();
  displayText.value = props.text;
  isDecrypted.value = true;
  direction.value = 'forward';
}

const animateListeners = computed(() => {
  if (props.animateOn === 'hover' || props.animateOn === 'inViewHover') {
    return {
      onMouseenter: triggerHoverDecrypt,
      onMouseleave: resetToPlainText,
    };
  }
  return {};
});

function isRevealedOrDone(index) {
  return revealedIndices.value.has(index) || (!isAnimating.value && isDecrypted.value);
}

let intersectionObserver = null;

onMounted(() => {
  if (props.animateOn === 'view' || props.animateOn === 'inViewHover') {
    intersectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !hasAnimated.value) {
            triggerDecrypt();
            hasAnimated.value = true;
          }
        });
      },
      { root: null, rootMargin: '0px', threshold: 0.1 }
    );
    if (containerRef.value) intersectionObserver.observe(containerRef.value);
  } else {
    displayText.value = props.text;
    isDecrypted.value = true;
  }
});

onUnmounted(() => {
  stopInterval();
  if (intersectionObserver && containerRef.value) {
    intersectionObserver.unobserve(containerRef.value);
  }
});
</script>
