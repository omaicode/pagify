<script setup>
import { computed } from 'vue'
import { UI_BADGE_TONES, oneOf } from './ui-conventions'

const props = defineProps({
  tone: {
    type: String,
    default: 'info',
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
  if (resolvedTone.value === 'danger') {
    return 'rounded border border-rose-200 bg-rose-50 text-rose-700'
  }

  if (resolvedTone.value === 'success') {
    return 'rounded border border-emerald-200 bg-emerald-50 text-emerald-700'
  }

  if (resolvedTone.value === 'warning') {
    return 'rounded border border-amber-200 bg-amber-50 text-amber-900'
  }

  return 'rounded border border-slate-200 bg-slate-50 text-slate-700'
})
</script>

<template>
  <div :class="[classes, 'px-3 py-2 text-sm']">
    <slot />
  </div>
</template>
