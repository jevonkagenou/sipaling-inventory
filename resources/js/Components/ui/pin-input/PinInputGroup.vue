<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: [String, Object, Array], required: false },
});
</script>

<template>
  <div :class="cn('flex items-center gap-1.5 sm:gap-2', props.class)">
    <slot />
  </div>
</template>
