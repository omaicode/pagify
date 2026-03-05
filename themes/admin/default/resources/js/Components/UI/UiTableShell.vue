<script setup>
import UiCard from './UiCard.vue'

defineProps({
  tableClass: {
    type: String,
    default: 'min-w-full divide-y divide-slate-200 text-sm',
  },
  headClass: {
    type: String,
    default: 'bg-slate-50',
  },
  bodyClass: {
    type: String,
    default: 'divide-y divide-slate-200',
  },
})
</script>

<template>
  <UiCard class="overflow-hidden" padding="none">
    <table :class="tableClass">
      <thead :class="headClass">
        <slot name="head" />
      </thead>
      <tbody :class="bodyClass">
        <slot name="body" />
      </tbody>
    </table>
  </UiCard>
</template>
