<template>
  <div
    ref="elRef"
    :class="props.class"
    :style="{
      transition: `opacity ${duration}ms cubic-bezier(0.16, 1, 0.3, 1) ${delay}ms, transform ${duration}ms cubic-bezier(0.16, 1, 0.3, 1) ${delay}ms`,
      opacity: isVisible ? 1 : 0,
      transform: isVisible ? 'translate3d(0, 0, 0)' : getInitialTransform(),
      willChange: 'opacity, transform',
    }"
  >
    <slot />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  delay: {
    type: Number,
    default: 0,
  },
  direction: {
    type: String,
    default: 'up', // 'up' | 'down' | 'left' | 'right' | 'none'
  },
  distance: {
    type: Number,
    default: 24,
  },
  duration: {
    type: Number,
    default: 650,
  },
  threshold: {
    type: Number,
    default: 0.1,
  },
  class: {
    type: String,
    default: '',
  },
});

const isVisible = ref(false);
const elRef = ref(null);
let observer = null;

function getInitialTransform() {
  if (props.direction === 'up') return `translate3d(0, ${props.distance}px, 0)`;
  if (props.direction === 'down') return `translate3d(0, -${props.distance}px, 0)`;
  if (props.direction === 'left') return `translate3d(${props.distance}px, 0, 0)`;
  if (props.direction === 'right') return `translate3d(-${props.distance}px, 0, 0)`;
  return 'translate3d(0, 0, 0)';
}

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          isVisible.value = true;
          if (observer && elRef.value) {
            observer.unobserve(elRef.value);
          }
          break;
        }
      }
    },
    {
      threshold: 0,
      rootMargin: '150px 0px 150px 0px',
    }
  );

  if (elRef.value) {
    observer.observe(elRef.value);
  }
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
});
</script>
