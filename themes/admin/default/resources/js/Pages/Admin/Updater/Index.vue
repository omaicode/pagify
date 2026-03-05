<script setup>
import axios from 'axios'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import UiCard from '../../../Components/UI/UiCard.vue'
import UiButton from '../../../Components/UI/UiButton.vue'
import UiAlert from '../../../Components/UI/UiAlert.vue'
import UiTableShell from '../../../Components/UI/UiTableShell.vue'
import UiStatusBadge from '../../../Components/UI/UiStatusBadge.vue'

const props = defineProps({
  apiRoutes: {
    type: Object,
    required: true,
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})

const executions = ref([])
const selectedExecution = ref(null)
const planItems = ref([])

const loadingExecutions = ref(false)
const loadingDetail = ref(false)
const loadingPlan = ref(false)
const runningAction = ref(false)
const errorMessage = ref('')

let pollTimer = null
let pollTick = 0

const BASE_POLL_INTERVAL_MS = 10000
const EXECUTIONS_REFRESH_EVERY_TICKS = 3

const label = (key, fallback) => t.value?.[key] ?? fallback

const showRoute = (executionId) => props.apiRoutes.showBase.replace('__EXECUTION__', String(executionId))
const updateModuleRoute = (moduleSlug) => props.apiRoutes.updateModuleBase.replace('__MODULE__', String(moduleSlug))
const rollbackRoute = (executionId) => props.apiRoutes.rollbackBase.replace('__EXECUTION__', String(executionId))

const isRateLimited = (error) => Number(error?.response?.status) === 429

const statusBadgeVariant = (status) => {
  if (status === 'succeeded') return 'success'
  if (status === 'failed') return 'danger'
  if (status === 'running') return 'warning'
  return 'neutral'
}

const fetchExecutions = async () => {
  loadingExecutions.value = true
  errorMessage.value = ''

  try {
    const response = await axios.get(props.apiRoutes.index)
    executions.value = response.data?.data ?? []
  } catch (error) {
    if (isRateLimited(error)) {
      errorMessage.value = label('updater_rate_limited', 'Too many updater requests. Polling has been slowed down automatically.')
      return
    }

    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_load_executions', 'Failed to load updater executions.')
  } finally {
    loadingExecutions.value = false
  }
}

const fetchExecutionDetail = async (executionId, silent = false) => {
  if (!executionId) {
    return
  }

  if (!silent) {
    loadingDetail.value = true
  }

  errorMessage.value = ''

  try {
    const response = await axios.get(showRoute(executionId))
    selectedExecution.value = response.data?.data ?? null
  } catch (error) {
    if (isRateLimited(error)) {
      if (!silent) {
        errorMessage.value = label('updater_rate_limited', 'Too many updater requests. Polling has been slowed down automatically.')
      }

      return
    }

    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_load_execution_detail', 'Failed to load execution detail.')
  } finally {
    if (!silent) {
      loadingDetail.value = false
    }
  }
}

const runDryPlan = async () => {
  loadingPlan.value = true
  errorMessage.value = ''

  try {
    const response = await axios.post(props.apiRoutes.dryRun, {
      target_type: 'all',
    })

    planItems.value = response.data?.data?.items ?? []
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_dry_run', 'Failed to generate update plan.')
  } finally {
    loadingPlan.value = false
  }
}

const queueUpdateAll = async () => {
  runningAction.value = true
  errorMessage.value = ''

  try {
    const response = await axios.post(props.apiRoutes.updateAll)
    const executionId = response.data?.data?.execution_id

    await fetchExecutions()

    if (executionId) {
      await fetchExecutionDetail(executionId)
    }
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_queue_all', 'Failed to queue full update.')
  } finally {
    runningAction.value = false
  }
}

const queueUpdateModule = async (moduleSlug) => {
  runningAction.value = true
  errorMessage.value = ''

  try {
    const response = await axios.post(updateModuleRoute(moduleSlug))
    const executionId = response.data?.data?.execution_id

    await fetchExecutions()

    if (executionId) {
      await fetchExecutionDetail(executionId)
    }
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_queue_module', 'Failed to queue module update.')
  } finally {
    runningAction.value = false
  }
}

const rollbackExecution = async (executionId) => {
  runningAction.value = true
  errorMessage.value = ''

  try {
    await axios.post(rollbackRoute(executionId))

    await fetchExecutions()
    await fetchExecutionDetail(executionId)
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? label('updater_failed_rollback', 'Rollback failed.')
  } finally {
    runningAction.value = false
  }
}

const startPolling = () => {
  stopPolling()
  pollTick = 0

  pollTimer = setInterval(async () => {
    if (runningAction.value || loadingExecutions.value || loadingDetail.value) {
      return
    }

    pollTick += 1

    const shouldRefreshExecutions = pollTick % EXECUTIONS_REFRESH_EVERY_TICKS === 0
    const activeExecution = selectedExecution.value?.status === 'queued' || selectedExecution.value?.status === 'running'

    if (shouldRefreshExecutions || activeExecution) {
      await fetchExecutions()
    }

    if (selectedExecution.value?.id && (activeExecution || shouldRefreshExecutions)) {
      await fetchExecutionDetail(selectedExecution.value.id, true)
    }
  }, BASE_POLL_INTERVAL_MS)
}

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  await Promise.all([fetchExecutions(), runDryPlan()])

  if (executions.value.length > 0) {
    await fetchExecutionDetail(executions.value[0].id)
  }

  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <UiCard tag="section" class="p-5">
        <h1 class="text-lg font-semibold text-[#1e1b4b]">{{ label('updater_title', 'Updater') }}</h1>
        <p class="mt-1 text-sm text-slate-600">
          {{ label('updater_subtitle', 'Queue module updates, monitor execution status, and rollback when needed.') }}
        </p>
      </UiCard>

      <UiAlert v-if="errorMessage" tone="danger" class="rounded-xl px-4 py-3">
        {{ errorMessage }}
      </UiAlert>

      <UiCard tag="section" class="p-5">
        <div class="flex flex-wrap items-center gap-3">
          <UiButton
            type="button"
            radius="lg"
            :disabled="runningAction"
            @click="queueUpdateAll"
          >
            {{ label('updater_action_update_all', 'Update all modules') }}
          </UiButton>

          <UiButton
            type="button"
            tone="neutral"
            radius="lg"
            :disabled="loadingPlan"
            @click="runDryPlan"
          >
            {{ loadingPlan ? label('loading', 'Loading...') : label('updater_action_refresh_plan', 'Refresh plan') }}
          </UiButton>
        </div>
      </UiCard>

      <UiCard tag="section" class="p-5">
        <h2 class="text-base font-semibold text-[#1e1b4b]">{{ label('updater_plan_title', 'Dry-run plan') }}</h2>

        <p v-if="loadingPlan" class="mt-3 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</p>

        <div v-else class="mt-4 overflow-x-auto">
          <UiTableShell table-class="min-w-full divide-y divide-[#ece8ff] text-sm" head-class="bg-[#f8f6ff]" body-class="divide-y divide-slate-100">
            <template #head>
              <tr>
                <th class="px-3 py-2 text-left">{{ label('slug', 'Slug') }}</th>
                <th class="px-3 py-2 text-left">{{ label('updater_package_name', 'Package') }}</th>
                <th class="px-3 py-2 text-left">{{ label('updater_installed_version', 'Installed version') }}</th>
                <th class="px-3 py-2 text-left">{{ label('actions', 'Actions') }}</th>
              </tr>
            </template>

            <template #body>
              <tr v-for="item in planItems" :key="item.module_slug">
                <td class="px-3 py-2">{{ item.module_slug }}</td>
                <td class="px-3 py-2">{{ item.package_name }}</td>
                <td class="px-3 py-2">{{ item.installed_version ?? '-' }}</td>
                <td class="px-3 py-2">
                  <UiButton
                    type="button"
                    tone="neutral"
                    size="sm"
                    radius="lg"
                    :disabled="runningAction"
                    @click="queueUpdateModule(item.module_slug)"
                  >
                    {{ label('updater_action_update_module', 'Update module') }}
                  </UiButton>
                </td>
              </tr>
              <tr v-if="planItems.length === 0">
                <td colspan="4" class="px-3 py-6 text-center text-slate-500">{{ label('updater_no_plan_items', 'No modules resolved for update plan.') }}</td>
              </tr>
            </template>
          </UiTableShell>
        </div>
      </UiCard>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <UiCard tag="section" class="p-5">
          <h2 class="text-base font-semibold text-[#1e1b4b]">{{ label('updater_executions_title', 'Executions') }}</h2>

          <p v-if="loadingExecutions" class="mt-3 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</p>

          <ul v-else class="mt-4 space-y-3">
            <li
              v-for="execution in executions"
              :key="execution.id"
              class="rounded-xl border border-[#ece8ff] bg-white p-3"
            >
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-[#1e1b4b]">#{{ execution.id }} · {{ execution.target_type }}{{ execution.target_value ? `:${execution.target_value}` : '' }}</p>
                <UiStatusBadge :tone="statusBadgeVariant(execution.status)">
                  {{ execution.status }}
                </UiStatusBadge>
              </div>

              <div class="mt-3 flex flex-wrap items-center gap-2">
                <UiButton
                  type="button"
                  tone="neutral"
                  size="sm"
                  radius="lg"
                  @click="fetchExecutionDetail(execution.id)"
                >
                  {{ label('updater_action_view_detail', 'View detail') }}
                </UiButton>

                <UiButton
                  type="button"
                  tone="danger"
                  size="sm"
                  radius="lg"
                  :disabled="runningAction"
                  @click="rollbackExecution(execution.id)"
                >
                  {{ label('updater_action_rollback', 'Rollback') }}
                </UiButton>
              </div>
            </li>
            <li v-if="executions.length === 0" class="rounded-xl border border-dashed border-[#e5deff] p-4 text-sm text-slate-500">
              {{ label('updater_no_executions', 'No updater executions yet.') }}
            </li>
          </ul>
        </UiCard>

        <UiCard tag="section" class="p-5">
          <h2 class="text-base font-semibold text-[#1e1b4b]">{{ label('updater_execution_detail_title', 'Execution detail') }}</h2>

          <p v-if="loadingDetail" class="mt-3 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</p>

          <div v-else-if="selectedExecution" class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
              <div>
                <p class="text-slate-500">{{ label('status', 'Status') }}</p>
                <p class="font-medium text-[#1e1b4b]">{{ selectedExecution.status }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ label('updater_exit_code', 'Exit code') }}</p>
                <p class="font-medium text-[#1e1b4b]">{{ selectedExecution.log?.exit_code ?? '-' }}</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-[#1e1b4b]">{{ label('updater_output_excerpt', 'Output excerpt') }}</p>
              <pre class="mt-2 max-h-52 overflow-auto rounded-xl border border-[#ece8ff] bg-[#faf9ff] p-3 text-xs text-slate-700 whitespace-pre-wrap">{{ selectedExecution.log?.output_excerpt || '-' }}</pre>
            </div>

            <div>
              <p class="text-sm font-medium text-[#1e1b4b]">{{ label('updater_error_output_excerpt', 'Error output excerpt') }}</p>
              <pre class="mt-2 max-h-52 overflow-auto rounded-xl border border-[#ece8ff] bg-[#faf9ff] p-3 text-xs text-slate-700 whitespace-pre-wrap">{{ selectedExecution.log?.error_output_excerpt || '-' }}</pre>
            </div>
          </div>

          <p v-else class="mt-3 text-sm text-slate-500">{{ label('updater_select_execution_hint', 'Select an execution to view details.') }}</p>
        </UiCard>
      </div>
    </div>
  </AdminLayout>
</template>
