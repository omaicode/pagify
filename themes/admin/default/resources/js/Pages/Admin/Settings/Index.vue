<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'

defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <section class="pf-card p-5">
        <h1 class="text-lg font-semibold text-[#1e1b4b]">{{ t.settings ?? 'Settings' }}</h1>
        <p class="mt-1 text-sm text-slate-600">
          {{ t.settings_subtitle ?? 'Advanced configuration is grouped here to keep daily CMS navigation focused on core editing tasks.' }}
        </p>
      </section>

      <section
        v-for="group in groups"
        :key="group.key"
        class="pf-card p-5"
      >
        <h2 class="text-base font-semibold text-[#1e1b4b]">{{ t[group.label_key] ?? group.label }}</h2>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <a
            v-for="item in group.items"
            :key="item.href"
            :href="item.href"
            class="rounded-xl border border-[#e5deff] bg-white px-4 py-3 transition hover:bg-[#f8f6ff]"
          >
            <p class="text-sm font-medium text-[#1e1b4b]">{{ t[item.label_key] ?? item.label }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ t[item.description_key] ?? item.description }}</p>
          </a>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>
