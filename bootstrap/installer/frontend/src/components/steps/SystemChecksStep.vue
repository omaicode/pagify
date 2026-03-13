<script setup>
defineProps({
  title: String,
  summary: String,
  runLabel: String,
  retryLabel: String,
  checks: {
    type: Object,
    default: () => ({}),
  },
  checkLabels: {
    type: Object,
    default: () => ({}),
  },
  reasonLabel: {
    type: String,
    default: 'Reason',
  },
  statusOk: String,
  statusError: String,
  disabled: Boolean,
});

const emit = defineEmits(['run', 'retry']);
</script>

<template>
  <section>
    <h3 class="text-xl font-bold text-slate-800">{{ title }}</h3>
    <p class="mt-1 text-sm text-slate-600">{{ summary }}</p>

    <div class="mt-3 flex flex-wrap gap-2">
      <button class="btn-primary" type="button" :disabled="disabled" @click="emit('run')">{{ runLabel }}</button>
      <button class="btn-secondary" type="button" :disabled="disabled" @click="emit('retry')">{{ retryLabel }}</button>
    </div>

    <div class="mt-4 grid gap-2">
      <div v-for="(item, key) in checks" :key="key" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5">
        <div>
          <div class="font-semibold text-slate-800">{{ checkLabels[key] ?? key }}</div>
          <div class="text-sm text-slate-600">{{ item.message }}</div>
          <div v-if="item.reason" class="mt-1 text-sm text-slate-700">
            <span class="font-semibold">{{ reasonLabel }}:</span>
            <span> {{ item.reason }}</span>
          </div>
        </div>
        <span
          class="rounded-full px-2 py-0.5 text-xs font-bold"
          :class="item.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
        >
          {{ item.ok ? statusOk : statusError }}
        </span>
      </div>
    </div>
  </section>
</template>
