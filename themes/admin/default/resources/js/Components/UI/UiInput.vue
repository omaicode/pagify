<script setup>
import { computed, useAttrs } from 'vue'
import { UI_INPUT_TAGS, oneOf } from './ui-conventions'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  tag: {
    type: String,
    default: 'input',
    validator: oneOf(UI_INPUT_TAGS),
  },
  as: {
    type: String,
    default: '',
    validator: oneOf(UI_INPUT_TAGS),
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  rows: {
    type: Number,
    default: 4,
  },
})

const emit = defineEmits(['update:modelValue'])
const attrs = useAttrs()
const resolvedTag = computed(() => props.as || props.tag)

const isInput = computed(() => resolvedTag.value === 'input')
const isTextarea = computed(() => resolvedTag.value === 'textarea')

const updateValue = (event) => {
  emit('update:modelValue', event?.target?.value ?? '')
}
</script>

<template>
  <component
    :is="resolvedTag"
    v-bind="attrs"
    class="pf-input"
    :value="modelValue"
    :type="isInput ? type : undefined"
    :rows="isTextarea ? rows : undefined"
    @input="updateValue"
    @change="updateValue"
  >
    <slot />
  </component>
</template>
