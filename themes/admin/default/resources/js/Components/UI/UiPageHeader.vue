<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div>
    <h1 class="pf-section-title">{{ title }}</h1>
    <p v-if="subtitle" class="pf-section-subtitle">{{ subtitle }}</p>
    <slot />
  </div>
</template>
