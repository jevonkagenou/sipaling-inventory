<template>
  <span :class="['inline-flex items-center align-middle', props.class]">
    <span class="whitespace-pre">{{ displayText }}</span>
    <span
      v-if="showCursor"
      :class="['inline-block ml-0.5 animate-pulse font-normal select-none', cursorClassName]"
      aria-hidden="true"
    >
      {{ cursorCharacter }}
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, type PropType } from 'vue';

const props = defineProps({
  text: {
    type: [String, Array] as PropType<string | string[]>,
    required: true,
    default: () => []
  },
  typingSpeed: {
    type: Number,
    default: 75
  },
  deletingSpeed: {
    type: Number,
    default: 40
  },
  pauseDuration: {
    type: Number,
    default: 1500
  },
  showCursor: {
    type: Boolean,
    default: true
  },
  cursorCharacter: {
    type: String,
    default: '|'
  },
  cursorClassName: {
    type: String,
    default: 'text-emerald-400'
  },
  loop: {
    type: Boolean,
    default: true
  },
  class: {
    type: String,
    default: ''
  }
});

const textsArray = computed(() => {
  if (Array.isArray(props.text)) return props.text;
  return [props.text];
});

const displayText = ref('');
const textIndex = ref(0);
const isDeleting = ref(false);
let timer: ReturnType<typeof setTimeout> | null = null;

function tick() {
  const currentFullText = textsArray.value[textIndex.value] || '';

  if (isDeleting.value) {
    displayText.value = currentFullText.substring(0, displayText.value.length - 1);
  } else {
    displayText.value = currentFullText.substring(0, displayText.value.length + 1);
  }

  let delta = isDeleting.value ? props.deletingSpeed : props.typingSpeed;

  if (!isDeleting.value && displayText.value === currentFullText) {
    // Finished typing full word, pause before deleting
    if (textsArray.value.length > 1 || props.loop) {
      delta = props.pauseDuration;
      isDeleting.value = true;
    } else {
      return; // Stop if single text and no loop
    }
  } else if (isDeleting.value && displayText.value === '') {
    // Finished deleting, move to next word
    isDeleting.value = false;
    textIndex.value = (textIndex.value + 1) % textsArray.value.length;
    delta = 400; // brief pause before next word
  }

  timer = setTimeout(tick, delta);
}

onMounted(() => {
  if (textsArray.value.length > 0) {
    timer = setTimeout(tick, props.typingSpeed);
  }
});

onUnmounted(() => {
  if (timer) clearTimeout(timer);
});
</script>
