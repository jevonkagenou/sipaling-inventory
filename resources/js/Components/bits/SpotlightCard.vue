<script setup>
import { ref } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  spotlightColor: {
    type: String,
    default: "rgba(14, 165, 233, 0.15)", // Tailwind sky-500 tint
  },
  spotlightSize: {
    type: Number,
    default: 320,
  },
  class: {
    type: [String, Object, Array],
    default: "",
  },
});

const mouseX = ref(-1000);
const mouseY = ref(-1000);
const isHovered = ref(false);

function handleMouseMove(event) {
  const rect = event.currentTarget.getBoundingClientRect();
  mouseX.value = event.clientX - rect.left;
  mouseY.value = event.clientY - rect.top;
}

function handleMouseEnter() {
  isHovered.value = true;
}

function handleMouseLeave() {
  isHovered.value = false;
  mouseX.value = -1000;
  mouseY.value = -1000;
}
</script>

<template>
  <div
    :class="
      cn(
        'relative overflow-hidden rounded-2xl p-6 shadow-sm transition-all duration-300 hover:shadow-md backdrop-blur-xs',
        props.class
      )
    "
    @mousemove="handleMouseMove"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Spotlight Effect Overlay inspired by Vue Bits -->
    <div
      class="pointer-events-none absolute -inset-px transition-opacity duration-300"
      :style="{
        opacity: isHovered ? 1 : 0,
        background: `radial-gradient(${spotlightSize}px circle at ${mouseX}px ${mouseY}px, ${spotlightColor}, transparent 80%)`,
      }"
    />
    <div class="relative z-10">
      <slot />
    </div>
  </div>
</template>
