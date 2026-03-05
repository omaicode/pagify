<script setup>
import { computed } from 'vue'
import { UI_BADGE_TONES, oneOf } from './ui-conventions'

const props = defineProps({
  tone: {
    type: String,
    default: 'neutral',
    validator: oneOf(UI_BADGE_TONES),
  },
  variant: {
    type: String,
    default: '',
    validator: oneOf(UI_BADGE_TONES),
  },
})

const resolvedTone = computed(() => props.variant || props.tone)

const classes = computed(() => {
  if (resolvedTone.value === 'success') {
    return 'bg-emerald-100 text-emerald-700'
  }

  if (resolvedTone.value === 'warning') {
    return 'bg-amber-100 text-amber-700'
  }

  if (resolvedTone.value === 'danger') {
    return 'bg-rose-100 text-rose-700'
  }

  if (resolvedTone.value === 'info') {
    return 'bg-sky-100 text-sky-700'
  }

  return 'bg-slate-100 text-slate-700'
})
</script>

<template>
  <span class="rounded-full px-2 py-1 text-xs font-medium" :class="classes">
    <slot />
  </span>
</template>
