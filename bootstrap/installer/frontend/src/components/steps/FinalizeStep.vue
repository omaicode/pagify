<script setup>
defineProps({
  title: String,
  summary: String,
  preflightLabel: String,
  finalizeLabel: String,
  disabled: Boolean,
});

const emit = defineEmits(['preflight', 'finalize']);
</script>

<template>
  <section>
    <h3 class="text-xl font-bold text-slate-800">{{ title }}</h3>
    <p class="mt-1 text-sm text-slate-600">{{ summary }}</p>

    <div class="mt-4 flex flex-wrap gap-2">
      <button class="btn-ghost" type="button" :disabled="disabled" @click="emit('preflight')">{{ preflightLabel }}</button>
      <button class="btn-primary" type="button" :disabled="disabled" @click="emit('finalize')">{{ finalizeLabel }}</button>
    </div>
  </section>
</template>
