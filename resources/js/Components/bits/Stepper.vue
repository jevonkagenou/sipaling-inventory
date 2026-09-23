<template>
  <div :class="twMerge('flex flex-col flex-1 justify-center items-center min-h-full w-full', rootClassName, ($attrs.class as string))">
    <div
      :class="twMerge('mx-auto w-full rounded-2xl shadow-2xl bg-white dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800', stepCircleContainerClassName)"
    >
      <!-- Step Header Bar -->
      <div :class="twMerge('flex w-full items-center justify-between p-4 sm:p-6 border-b border-zinc-200 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-950/60', stepContainerClassName)">
        <template v-for="(_, index) in stepsArray" :key="index + 1">
          <component
            :is="renderStepIndicator"
            v-if="renderStepIndicator"
            :step="index + 1"
            :current-step="currentStep"
            :on-step-click="handleCustomStepClick"
          />

          <div
            v-else
            @click="() => handleStepIndicatorClick(index + 1)"
            class="relative outline-none cursor-pointer flex flex-col items-center gap-1.5 group select-none"
            :style="disableStepIndicators ? { pointerEvents: 'none', opacity: 0.5 } : {}"
          >
            <Motion
              as="div"
              class="flex justify-center items-center rounded-full w-8 h-8 font-semibold text-xs transition-shadow duration-300"
              :animate="getStepStatus(index + 1)"
              :variants="indicatorVariants"
              :initial="false"
              :transition="{ duration: 0.3 }"
            >
              <svg
                v-if="getStepStatus(index + 1) === 'complete'"
                class="w-4 h-4 text-white dark:text-zinc-950 font-bold"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                viewBox="0 0 24 24"
              >
                <Motion
                  as="path"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M5 13l4 4L19 7"
                  :initial="{ pathLength: 0 }"
                  :animate="{ pathLength: 1 }"
                  :transition="{ delay: 0.1, type: 'tween', ease: 'easeOut', duration: 0.3 }"
                />
              </svg>
              <div v-else-if="getStepStatus(index + 1) === 'active'" class="bg-white dark:bg-zinc-950 rounded-full w-2.5 h-2.5" />
              <span v-else class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">{{ index + 1 }}</span>
            </Motion>

            <!-- Optional Step Label -->
            <span
              v-if="stepLabels && stepLabels[index]"
              :class="[
                'text-[11px] font-medium transition-colors hidden sm:inline-block max-w-[130px] text-center leading-tight',
                currentStep === index + 1 ? 'text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200'
              ]"
            >
              {{ stepLabels[index] }}
            </span>
          </div>

          <div v-if="index < totalSteps - 1" class="relative flex-1 bg-zinc-200 dark:bg-zinc-800 mx-2 sm:mx-4 rounded h-0.5 overflow-hidden">
            <Motion
              as="div"
              class="top-0 left-0 absolute h-full"
              :variants="lineVariants"
              :initial="false"
              :animate="currentStep > index + 1 ? 'complete' : 'incomplete'"
              :transition="{ duration: 0.4 }"
            />
          </div>
        </template>
      </div>

      <!-- Animated Step Content -->
      <Motion
        as="div"
        :class="twMerge('relative overflow-hidden', contentClassName)"
        :animate="{ height: isCompleted ? 0 : (parentHeight > 0 ? parentHeight : 'auto') }"
        :transition="{ type: 'spring', duration: 0.35, bounce: 0.1 }"
      >
        <AnimatePresence :initial="false" mode="sync" :custom="direction">
          <Motion
            v-if="!isCompleted"
            v-layout-height="measureHeight"
            as="div"
            :key="currentStep"
            :custom="direction"
            :variants="stepVariants"
            initial="enter"
            animate="center"
            exit="exit"
            :transition="{ duration: 0.35 }"
            class="w-full"
            style="position: relative; left: 0; right: 0; top: 0"
          >
            <div :class="twMerge('p-4 sm:p-6 lg:p-8', stepContentClassName)">
              <component :is="stepsArray[currentStep - 1]" />
            </div>
          </Motion>
        </AnimatePresence>
      </Motion>

      <!-- Step Navigation Footer -->
      <div v-if="!isCompleted && showFooter" :class="twMerge('px-4 sm:px-8 pb-6 pt-2 border-t border-zinc-200 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-950/40', footerClassName)">
        <div :class="`flex items-center ${currentStep !== 1 ? 'justify-between' : 'justify-end'} gap-3`">
          <button
            v-if="currentStep !== 1"
            @click="handleBack"
            :class="[
              'cursor-pointer rounded-lg px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-400 transition-colors hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800',
              currentStep === 1 ? 'pointer-events-none opacity-50' : ''
            ]"
            v-bind="backButtonProps"
          >
            {{ backButtonText }}
          </button>
          <button
            @click="isLastStep ? handleComplete() : handleNext()"
            :class="[
              'flex justify-center items-center px-4 py-2 rounded-lg font-semibold text-xs tracking-tight transition-all duration-300 cursor-pointer shadow-sm bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950'
            ]"
            v-bind="nextButtonProps"
          >
            {{ isLastStep ? 'Selesai Alur Operasional' : nextButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AnimatePresence, Motion } from 'motion-v';
import { twMerge } from 'tailwind-merge';
import {
  computed,
  ref,
  useSlots,
  type ButtonHTMLAttributes,
  type Component,
  type DirectiveBinding,
  type VNode
} from 'vue';

interface StepperProps {
  initialStep?: number;
  onStepChange?: (step: number) => void;
  onFinalStepCompleted?: () => void;
  rootClassName?: string;
  stepCircleContainerClassName?: string;
  stepContainerClassName?: string;
  contentClassName?: string;
  stepContentClassName?: string;
  footerClassName?: string;
  backButtonProps?: ButtonHTMLAttributes;
  nextButtonProps?: ButtonHTMLAttributes;
  backButtonText?: string;
  nextButtonText?: string;
  disableStepIndicators?: boolean;
  renderStepIndicator?: Component;
  stepLabels?: string[];
  showFooter?: boolean;
  isDark?: boolean;
}

const props = withDefaults(defineProps<StepperProps>(), {
  initialStep: 1,
  onStepChange: () => {},
  onFinalStepCompleted: () => {},
  rootClassName: '',
  stepCircleContainerClassName: '',
  stepContainerClassName: '',
  contentClassName: '',
  stepContentClassName: 'p-4 sm:p-6 lg:p-8',
  footerClassName: '',
  backButtonProps: () => ({}),
  nextButtonProps: () => ({}),
  backButtonText: 'Kembali',
  nextButtonText: 'Lanjutkan',
  disableStepIndicators: false,
  renderStepIndicator: undefined,
  stepLabels: () => [],
  showFooter: true
});

const emit = defineEmits<{
  (e: 'step-change', step: number): void;
  (e: 'final-step-completed'): void;
  (e: 'update:step', step: number): void;
}>();

const slots = useSlots();
const currentStep = ref<number>(props.initialStep);
const direction = ref<number>(0);
const parentHeight = ref<number>(0);

const stepsArray = computed<VNode[]>(() => {
  const defaultSlot = slots.default?.() || [];
  return defaultSlot.flatMap(node => {
    if (node.type === Symbol.for('v-fgt') && Array.isArray(node.children)) {
      return (node.children as VNode[]).filter(child => child.type !== Symbol.for('v-cmt'));
    }
    if (node.type === Symbol.for('v-cmt')) {
      return [];
    }
    return [node];
  });
});

const totalSteps = computed<number>(() => stepsArray.value.length);
const isCompleted = computed<boolean>(() => currentStep.value > totalSteps.value);
const isLastStep = computed<boolean>(() => currentStep.value === totalSteps.value);

type LayoutHeightHook = (el: HTMLElement, binding: DirectiveBinding<(height: number) => void>) => void;

const vLayoutHeight = {
  mounted(el: HTMLElement, binding: DirectiveBinding<(height: number) => void>) {
    binding.value(el.offsetHeight);
    if (typeof ResizeObserver !== 'undefined') {
      const ro = new ResizeObserver(() => {
        if (el.offsetHeight > 0) {
          binding.value(el.offsetHeight);
        }
      });
      ro.observe(el);
      (el as any).__ro = ro;
    }
  },
  updated(el: HTMLElement, binding: DirectiveBinding<(height: number) => void>) {
    if (el.offsetHeight > 0) {
      binding.value(el.offsetHeight);
    }
  },
  unmounted(el: HTMLElement) {
    (el as any).__ro?.disconnect();
  }
} as { mounted: LayoutHeightHook; updated: LayoutHeightHook; unmounted: (el: HTMLElement) => void };

const measureHeight = (height: number): void => {
  if (height > 0) {
    parentHeight.value = height;
  }
};

const getStepStatus = (step: number): 'active' | 'inactive' | 'complete' => {
  if (currentStep.value === step) return 'active';
  if (currentStep.value > step) return 'complete';
  return 'inactive';
};

const updateStep = (newStep: number): void => {
  currentStep.value = newStep;
  emit('update:step', newStep);
  if (newStep > totalSteps.value) {
    props.onFinalStepCompleted();
    emit('final-step-completed');
  } else {
    props.onStepChange(newStep);
    emit('step-change', newStep);
  }
};

const handleBack = (): void => {
  if (currentStep.value > 1) {
    direction.value = -1;
    updateStep(currentStep.value - 1);
  }
};

const handleNext = (): void => {
  if (!isLastStep.value) {
    direction.value = 1;
    updateStep(currentStep.value + 1);
  }
};

const handleComplete = (): void => {
  direction.value = 1;
  updateStep(totalSteps.value + 1);
};

const handleStepIndicatorClick = (step: number): void => {
  if (step !== currentStep.value && !props.disableStepIndicators) {
    direction.value = step > currentStep.value ? 1 : -1;
    updateStep(step);
  }
};

const handleCustomStepClick = (clicked: number): void => {
  direction.value = clicked > currentStep.value ? 1 : -1;
  updateStep(clicked);
};

const stepVariants = {
  enter: (dir: number) => ({
    x: dir >= 0 ? '25%' : '-25%',
    opacity: 0
  }),
  center: {
    x: '0%',
    opacity: 1
  },
  exit: (dir: number) => ({
    x: dir >= 0 ? '-25%' : '25%',
    opacity: 0
  })
};

const indicatorVariants = computed(() => {
  const isDarkMode = props.isDark !== undefined
    ? props.isDark
    : (typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
  return {
    inactive: { scale: 1, backgroundColor: isDarkMode ? '#27272a' : '#e4e4e7', color: isDarkMode ? '#a1a1aa' : '#71717a' },
    active: { scale: 1, backgroundColor: '#10b981', color: isDarkMode ? '#09090b' : '#ffffff', boxShadow: '0 0 16px rgba(16,185,129,0.35)' },
    complete: { scale: 1, backgroundColor: '#10b981', color: isDarkMode ? '#09090b' : '#ffffff' }
  };
});

const lineVariants = {
  incomplete: { width: 0, backgroundColor: 'transparent' },
  complete: { width: '100%', backgroundColor: '#10b981' }
};

defineExpose({
  currentStep,
  setStep: (step: number) => {
    if (step >= 1 && step <= totalSteps.value) {
      direction.value = step > currentStep.value ? 1 : -1;
      updateStep(step);
    }
  },
  next: handleNext,
  back: handleBack,
  reset: () => {
    direction.value = -1;
    updateStep(1);
  }
});
</script>
