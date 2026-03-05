<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    default: '',
  },
  for: {
    type: String,
    default: '',
  },
  forId: {
    type: String,
    default: '',
  },
  labelTone: {
    type: String,
    default: 'primary',
  },
  labelClass: {
    type: [String, Array, Object],
    default: '',
  },
})

const resolvedFor = computed(() => props.forId || props.for)
const resolvedLabelClass = computed(() => {
  const toneClass = props.labelTone === 'muted' ? 'text-slate-700' : 'text-[#1e1b4b]'

  return [toneClass, props.labelClass]
})
</script>

<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium" :class="resolvedLabelClass" :for="resolvedFor || undefined">{{ label }}</label>
    <slot />
  </div>
</template>
