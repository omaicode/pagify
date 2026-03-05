<script setup>
import { computed, useAttrs } from 'vue'
import { UI_BUTTON_RADII, UI_BUTTON_SIZES, UI_TAGS, UI_TONES, oneOf } from './ui-conventions'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  tag: {
    type: String,
    default: 'button',
    validator: oneOf(UI_TAGS),
  },
  as: {
    type: String,
    default: '',
    validator: oneOf(UI_TAGS),
  },
  type: {
    type: String,
    default: 'button',
  },
  tone: {
    type: String,
    default: 'primary',
    validator: oneOf(UI_TONES),
  },
  variant: {
    type: String,
    default: '',
    validator: oneOf(UI_TONES),
  },
  size: {
    type: String,
    default: 'md',
    validator: oneOf(UI_BUTTON_SIZES),
  },
  radius: {
    type: String,
    default: 'full',
    validator: oneOf(UI_BUTTON_RADII),
  },
  rounded: {
    type: String,
    default: '',
    validator: oneOf(UI_BUTTON_RADII),
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const attrs = useAttrs()
const resolvedTag = computed(() => props.as || props.tag)
const resolvedTone = computed(() => props.variant || props.tone)
const resolvedRadius = computed(() => props.rounded || props.radius)
const resolvedFullWidth = computed(() => props.fullWidth || props.block)

const variantClass = computed(() => {
  if (resolvedTone.value === 'outline') {
    return 'pf-btn-outline'
  }

  if (resolvedTone.value === 'danger') {
    return 'rounded-lg border border-rose-300 bg-white text-rose-700 transition hover:bg-rose-50'
  }

  if (resolvedTone.value === 'neutral') {
    return 'rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50'
  }

  return 'pf-btn-primary'
})

const sizeClass = computed(() => {
  if (props.size === 'xs') {
    return '!px-2 !py-1 !text-xs'
  }

  if (props.size === 'sm') {
    return '!px-3 !py-1.5 !text-xs'
  }

  if (props.size === 'lg') {
    return '!px-4 !py-2.5 !text-sm'
  }

  return ''
})

const roundedClass = computed(() => {
  if (resolvedRadius.value === 'lg') {
    return '!rounded-lg'
  }

  return ''
})

const classes = computed(() => [
  variantClass.value,
  sizeClass.value,
  roundedClass.value,
  resolvedFullWidth.value ? 'w-full' : '',
  props.disabled ? 'disabled:opacity-50' : '',
])

const resolvedType = computed(() => (resolvedTag.value === 'button' ? props.type : undefined))
const resolvedDisabled = computed(() => (resolvedTag.value === 'button' ? props.disabled : undefined))
const resolvedAriaDisabled = computed(() => (resolvedTag.value !== 'button' && props.disabled ? 'true' : undefined))
</script>

<template>
  <component
    :is="resolvedTag"
    v-bind="attrs"
    :type="resolvedType"
    :disabled="resolvedDisabled"
    :aria-disabled="resolvedAriaDisabled"
    :class="classes"
  >
    <slot />
  </component>
</template>
