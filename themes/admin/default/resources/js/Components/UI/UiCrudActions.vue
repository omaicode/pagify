<script setup>
import UiButton from './UiButton.vue'

const props = defineProps({
  editLabel: {
    type: String,
    default: 'Edit',
  },
  deleteLabel: {
    type: String,
    default: 'Delete',
  },
  editText: {
    type: String,
    default: '',
  },
  deleteText: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['edit', 'delete'])
</script>

<template>
  <div class="flex items-center gap-2">
    <UiButton type="button" tone="neutral" size="xs" radius="lg" @click="emit('edit')">{{ props.editText || props.editLabel }}</UiButton>
    <UiButton type="button" tone="danger" size="xs" radius="lg" @click="emit('delete')">{{ props.deleteText || props.deleteLabel }}</UiButton>
  </div>
</template>
