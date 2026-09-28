<script setup>
import { Dot } from "lucide-vue-next";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: [String, Object, Array], required: false },
});
</script>

<template>
  <div :class="cn('flex items-center justify-center text-slate-400 dark:text-slate-600', props.class)">
    <slot>
      <Dot class="w-6 h-6" />
    </slot>
  </div>
</template>
