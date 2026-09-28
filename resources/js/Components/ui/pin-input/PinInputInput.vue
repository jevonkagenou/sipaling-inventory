<script setup>
import { computed } from "vue";
import { PinInputInput, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
  index: { type: Number, required: true },
  disabled: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: [String, Object, Array], required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
  <PinInputInput
    v-bind="forwardedProps"
    :class="
      cn(
        'relative text-center focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-[#2563EB] focus:relative focus:z-10 flex h-12 w-12 items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-lg font-bold text-slate-900 dark:text-slate-100 shadow-sm transition-all selection:bg-transparent disabled:opacity-50 disabled:cursor-not-allowed',
        props.class,
      )
    "
  />
</template>
