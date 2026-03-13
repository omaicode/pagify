<script setup>
defineProps({
  statusType: {
    type: String,
    required: true,
  },
  statusMessage: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
  loadingLabel: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <div>
    <div v-if="loading" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-800">
      {{ loadingLabel }}
    </div>

    <div
      class="mt-4 rounded-xl border px-3 py-2.5 text-sm"
      :class="{
        'border-indigo-200 bg-indigo-50 text-indigo-700': statusType === 'info',
        'border-emerald-200 bg-emerald-50 text-emerald-700': statusType === 'success',
        'border-rose-200 bg-rose-50 text-rose-700': statusType === 'error',
      }"
    >
      {{ statusMessage }}
    </div>
  </div>
</template>
