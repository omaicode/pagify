<script setup>
import { computed } from 'vue'
import { UI_CARD_PADDING, UI_TAGS, oneOf } from './ui-conventions'

const props = defineProps({
  tag: {
    type: String,
    default: 'div',
    validator: oneOf(UI_TAGS),
  },
  as: {
    type: String,
    default: '',
    validator: oneOf(UI_TAGS),
  },
  padding: {
    type: String,
    default: 'default',
    validator: oneOf(UI_CARD_PADDING),
  },
  noPadding: {
    type: Boolean,
    default: false,
  },
})

const resolvedTag = computed(() => props.as || props.tag)
const resolvedPadding = computed(() => (props.noPadding ? 'none' : props.padding))

const classes = computed(() => [
  'pf-card',
  resolvedPadding.value === 'none' ? 'p-0' : '',
])
</script>

<template>
  <component :is="resolvedTag" :class="classes">
    <slot />
  </component>
</template>
