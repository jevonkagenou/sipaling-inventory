<template>
  <div
    ref="cardRef"
    class="tilt-card-container [perspective:1000px]"
    @mousemove="handleMouseMove"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <div
      :class="props.class"
      class="h-full w-full [transform-style:preserve-3d] will-change-transform"
      :style="{
        transform: isHovered
          ? `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${scale})`
          : 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)',
        transition: isHovered
          ? 'transform 0.08s ease-out'
          : 'transform 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
      }"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  maxTilt: {
    type: Number,
    default: 7,
  },
  scale: {
    type: Number,
    default: 1.02,
  },
  class: {
    type: String,
    default: '',
  },
});

const cardRef = ref(null);
const tiltX = ref(0);
const tiltY = ref(0);
const isHovered = ref(false);

function handleMouseMove(e) {
  if (!cardRef.value) return;
  const rect = cardRef.value.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const centerX = rect.width / 2;
  const centerY = rect.height / 2;

  tiltX.value = Number((((y - centerY) / centerY) * -props.maxTilt).toFixed(2));
  tiltY.value = Number((((x - centerX) / centerX) * props.maxTilt).toFixed(2));
}

function handleMouseEnter() {
  isHovered.value = true;
}

function handleMouseLeave() {
  isHovered.value = false;
  tiltX.value = 0;
  tiltY.value = 0;
}
</script>

<style scoped>
.tilt-card-container {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
