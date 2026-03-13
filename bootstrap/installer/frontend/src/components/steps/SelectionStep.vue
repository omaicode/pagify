<script setup>
defineProps({
  title: String,
  buttonLabel: String,
  retryLabel: String,
  skipLabel: String,
  emptyMessage: String,
  items: {
    type: Array,
    default: () => [],
  },
  disabled: Boolean,
});

const emit = defineEmits(['install', 'retry', 'skip']);
</script>

<template>
  <section>
    <h3 class="text-xl font-bold text-slate-800">{{ title }}</h3>

    <div class="mt-4 grid gap-2">
      <label
        v-for="item in items"
        :key="item.slug"
        class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5"
      >
        <span>
          <span class="block font-semibold text-slate-800">{{ item.name }}</span>
          <span class="block text-sm text-slate-600">{{ item.package_name }}</span>
        </span>
        <input
          :value="item.slug"
          :checked="item.recommended"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-ocean focus:ring-ocean"
        />
      </label>
      <div v-if="items.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-5 text-sm text-slate-500">
        {{ emptyMessage }}
      </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
      <button class="btn-primary" type="button" :disabled="disabled" @click="emit('install')">{{ buttonLabel }}</button>
      <button class="btn-secondary" type="button" :disabled="disabled" @click="emit('retry')">{{ retryLabel }}</button>
      <button class="btn-ghost" type="button" :disabled="disabled" @click="emit('skip')">{{ skipLabel }}</button>
    </div>
  </section>
</template>
