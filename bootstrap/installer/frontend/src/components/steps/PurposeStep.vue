<script setup>
import { ref } from 'vue';

const props = defineProps({
  title: String,
  fieldLabel: String,
  submitLabel: String,
  purposeOptions: {
    type: Object,
    default: () => ({}),
  },
  disabled: Boolean,
});

const emit = defineEmits(['submit']);
const purpose = ref('blog');

function submit() {
  emit('submit', { purpose: purpose.value });
}
</script>

<template>
  <section>
    <h3 class="text-xl font-bold text-slate-800">{{ title }}</h3>

    <div class="mt-4 max-w-sm">
      <label class="text-sm font-semibold text-slate-700">{{ fieldLabel }}
        <select v-model="purpose" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          <option value="blog">{{ props.purposeOptions.blog ?? 'Blog' }}</option>
          <option value="company">{{ props.purposeOptions.company ?? 'Company website' }}</option>
          <option value="ecommerce">{{ props.purposeOptions.ecommerce ?? 'Ecommerce' }}</option>
          <option value="other">{{ props.purposeOptions.other ?? 'Other' }}</option>
        </select>
      </label>
    </div>

    <div class="mt-4">
      <button class="btn-primary" type="button" :disabled="disabled" @click="submit">{{ submitLabel }}</button>
    </div>
  </section>
</template>
