<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  width: { type: Number, default: 32 },
  height: { type: Number, default: 32 },
  x: { type: Number, default: 0 },
  y: { type: Number, default: 0 },
  squares: { type: Array, default: () => [] },
  class: { type: [String, Object, Array], default: "" },
});
</script>

<template>
  <svg
    aria-hidden="true"
    :class="
      cn(
        'pointer-events-none absolute inset-0 h-full w-full fill-zinc-400/20 stroke-zinc-400/20 [mask-image:radial-gradient(ellipse_at_center,white,transparent_80%)]',
        props.class
      )
    "
  >
    <defs>
      <pattern
        :id="`grid-pattern`"
        :width="width"
        :height="height"
        patternUnits="userSpaceOnUse"
        :x="x"
        :y="y"
      >
        <path
          :d="`M.5 ${height}V.5H${width}`"
          fill="none"
          stroke-dasharray="0"
        />
      </pattern>
    </defs>
    <rect width="100%" height="100%" stroke-width="0" fill="url(#grid-pattern)" />
  </svg>
</template>
