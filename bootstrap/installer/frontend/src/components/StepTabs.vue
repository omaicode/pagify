<script setup>
defineProps({
  steps: {
    type: Array,
    required: true,
  },
  currentStep: {
    type: Number,
    required: true,
  },
  completedSteps: {
    type: Array,
    required: true,
  },
  stepLabel: {
    type: String,
    required: true,
  },
  completedLabel: {
    type: String,
    required: true,
  },
  pendingLabel: {
    type: String,
    required: true,
  },
  lockedLabel: {
    type: String,
    required: true,
  },
  maxUnlockedStep: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['go']);
</script>

<template>
  <div class="flex flex-col gap-2.5">
    <button
      v-for="step in steps"
      :key="step.number"
      type="button"
      class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 text-left transition"
      :class="[
        completedSteps.includes(step.number) ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50',
        step.number > maxUnlockedStep ? 'opacity-80' : '',
        currentStep === step.number ? 'translate-x-1 border-ocean shadow-lg shadow-cyan-900/10' : '',
      ]"
      @click="emit('go', step.number)"
    >
      <span class="text-sm font-semibold text-slate-700">{{ stepLabel }} {{ step.number }} - {{ step.label }}</span>
      <span
        class="rounded-full px-2 py-0.5 text-xs font-semibold"
        :class="
          completedSteps.includes(step.number)
            ? 'bg-emerald-100 text-emerald-700'
            : step.number <= maxUnlockedStep
              ? 'bg-amber-100 text-amber-700'
              : 'bg-slate-200 text-slate-700'
        "
      >
        {{
          completedSteps.includes(step.number)
            ? completedLabel
            : step.number <= maxUnlockedStep
              ? pendingLabel
              : lockedLabel
        }}
      </span>
    </button>
  </div>
</template>
