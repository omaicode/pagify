<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import UiCard from '../../../Components/UI/UiCard.vue'
import UiButton from '../../../Components/UI/UiButton.vue'
import UiStatusBadge from '../../../Components/UI/UiStatusBadge.vue'

const props = defineProps({
  apiRoutes: {
    type: Object,
    required: true,
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})

const label = (key, fallback) => {
  return t.value?.[key] ?? fallback
}

const modules = ref([])
const health = ref(null)
const loading = ref(false)
const healthLoading = ref(false)
const updating = ref({})

const hasIssues = computed(() => {
  if (!health.value) {
    return false
  }

  return !health.value.healthy
})

const updateRoute = (slug) => props.apiRoutes.updateBase.replace('__MODULE__', slug)

const loadModules = async () => {
  loading.value = true

  try {
    const response = await axios.get(props.apiRoutes.index)
    modules.value = response.data.data ?? []
  } finally {
    loading.value = false
  }
}

const loadHealth = async () => {
  healthLoading.value = true

  try {
    const response = await axios.get(props.apiRoutes.health)
    health.value = response.data.data ?? null
  } finally {
    healthLoading.value = false
  }
}

const toggleModule = async (item) => {
  if (item.can_disable === false) {
    return
  }

  const nextValue = !item.enabled
  updating.value = {
    ...updating.value,
    [item.slug]: true,
  }

  try {
    await axios.patch(updateRoute(item.slug), {
      enabled: nextValue,
    })

    item.enabled = nextValue
    await loadHealth()
  } finally {
    updating.value = {
      ...updating.value,
      [item.slug]: false,
    }
  }
}

onMounted(async () => {
  await Promise.all([loadModules(), loadHealth()])
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <UiCard tag="section" class="p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-900">{{ label('modules_health_title', 'Registry Health') }}</h2>
          <UiStatusBadge :tone="hasIssues ? 'warning' : 'success'">
            {{ hasIssues ? label('modules_status_warning', 'Needs Attention') : label('modules_status_healthy', 'Healthy') }}
          </UiStatusBadge>
        </div>

        <p v-if="healthLoading" class="mt-2 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</p>
        <div v-else-if="health" class="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-3">
          <div>
            <p class="text-slate-500">{{ label('modules_total_configured', 'Configured modules') }}</p>
            <p class="font-medium">{{ health.total_configured_modules }}</p>
          </div>
          <div>
            <p class="text-slate-500">{{ label('modules_total_runtime', 'Runtime state entries') }}</p>
            <p class="font-medium">{{ health.total_runtime_modules }}</p>
          </div>
          <div>
            <p class="text-slate-500">{{ label('modules_unknown_runtime', 'Unknown runtime modules') }}</p>
            <p class="font-medium">{{ (health.unknown_runtime_modules ?? []).join(', ') || '-' }}</p>
          </div>
        </div>
      </UiCard>

      <UiCard tag="section" class="shadow-sm" padding="none">
        <header class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-base font-semibold text-slate-900">{{ label('modules_list_title', 'Module States') }}</h2>
        </header>

        <div v-if="loading" class="p-4 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</div>

        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in modules"
            :key="item.slug"
            class="flex items-center justify-between gap-4 px-4 py-3"
          >
            <div>
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-sm text-slate-500">{{ item.slug }}</p>
              <p v-if="item.description" class="text-sm text-slate-600">{{ item.description }}</p>
            </div>

            <UiButton
              type="button"
              tone="neutral"
              radius="lg"
              :class="item.can_disable === false
                ? 'bg-slate-100 text-slate-500 cursor-not-allowed'
                : (item.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')"
              :disabled="updating[item.slug] || item.can_disable === false"
              @click="toggleModule(item)"
            >
              {{ item.can_disable === false
                ? label('locked', 'Locked')
                : (updating[item.slug]
                  ? label('loading', 'Loading...')
                  : (item.enabled ? label('modules_enabled', 'Enabled') : label('modules_disabled', 'Disabled')))
              }}
            </UiButton>
          </li>
        </ul>
      </UiCard>
    </div>
  </AdminLayout>
</template>
