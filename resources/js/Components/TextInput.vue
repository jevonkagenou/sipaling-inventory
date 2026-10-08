<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="h-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 text-xs sm:text-sm shadow-2xs transition-all duration-200 outline-none focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
        v-model="model"
        ref="input"
    />
</template>
