<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  trueLabel: {
    type: String,
    default: 'On',
  },
  falseLabel: {
    type: String,
    default: 'Off',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const buttonClass = computed(() => {
  if (props.disabled) {
    return 'bg-slate-300 cursor-not-allowed'
  }

  return props.modelValue ? 'bg-indigo-600' : 'bg-slate-300'
})

const knobClass = computed(() => (props.modelValue ? 'translate-x-5' : 'translate-x-0'))

const toggle = () => {
  if (props.disabled) {
    return
  }

  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <div class="inline-flex items-center gap-2">
    <button
      type="button"
      class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      :class="buttonClass"
      role="switch"
      :aria-checked="modelValue ? 'true' : 'false'"
      :aria-disabled="disabled ? 'true' : undefined"
      :disabled="disabled"
      @click="toggle"
    >
      <span class="sr-only">Toggle switch</span>
      <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform" :class="knobClass" />
    </button>
    <span class="text-sm font-medium text-slate-700">{{ modelValue ? trueLabel : falseLabel }}</span>
  </div>
</template>
