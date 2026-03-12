<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue'
import UiCard from '@admin-theme/Components/UI/UiCard.vue'
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue'

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
      <UiPageHeader
        :title="t.settings ?? 'Settings'"
        :subtitle="t.settings_subtitle ?? 'Advanced configuration is grouped here to keep daily CMS navigation focused on core editing tasks.'"
      />

      <UiCard
        tag="section"
        v-for="group in groups"
        :key="group.key"
        class="p-5"
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
      </UiCard>
    </div>
  </AdminLayout>
</template>
