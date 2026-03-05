<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import UiCard from '../../../Components/UI/UiCard.vue'
import UiButton from '../../../Components/UI/UiButton.vue'
import UiStatusBadge from '../../../Components/UI/UiStatusBadge.vue'
import UiInput from '../../../Components/UI/UiInput.vue'
import UiAlert from '../../../Components/UI/UiAlert.vue'

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

const plugins = ref([])
const pluginsLoading = ref(false)
const pluginUpdating = ref({})
const composerPackageName = ref('')
const pluginZip = ref(null)
const pluginExtensions = ref(null)
const pluginExtensionsLoading = ref(false)
const pluginNotice = ref({ tone: 'info', text: '' })

const pluginUpdateRoute = (slug) => props.apiRoutes.pluginUpdateBase.replace('__PLUGIN__', slug)
const pluginUninstallRoute = (slug) => props.apiRoutes.pluginUninstallBase.replace('__PLUGIN__', slug)

const setPluginNotice = (tone, text) => {
  pluginNotice.value = { tone, text }
}

const loadPlugins = async () => {
  pluginsLoading.value = true

  try {
    const response = await axios.get(props.apiRoutes.pluginsIndex)
    plugins.value = response.data.data ?? []
  } finally {
    pluginsLoading.value = false
  }
}

const loadExtensions = async () => {
  pluginExtensionsLoading.value = true

  try {
    const response = await axios.get(props.apiRoutes.pluginExtensions)
    pluginExtensions.value = response.data.data ?? null
  } finally {
    pluginExtensionsLoading.value = false
  }
}

const togglePlugin = async (item) => {
  const nextValue = !item.enabled

  pluginUpdating.value = {
    ...pluginUpdating.value,
    [item.slug]: true,
  }

  try {
    await axios.patch(pluginUpdateRoute(item.slug), {
      enabled: nextValue,
    })

    setPluginNotice('success', label('plugins_state_updated', 'Plugin state updated.'))
    await Promise.all([loadPlugins(), loadExtensions()])
  } catch (error) {
    const fallback = label('plugins_failed_toggle', 'Failed to update plugin state.')
    setPluginNotice('danger', error?.response?.data?.message ?? fallback)
  } finally {
    pluginUpdating.value = {
      ...pluginUpdating.value,
      [item.slug]: false,
    }
  }
}

const installComposerPlugin = async () => {
  if (!composerPackageName.value.trim()) {
    return
  }

  try {
    await axios.post(props.apiRoutes.pluginInstallComposer, {
      package_name: composerPackageName.value.trim(),
    })

    composerPackageName.value = ''
    setPluginNotice('success', label('plugins_installed', 'Plugin installed.'))
    await Promise.all([loadPlugins(), loadExtensions()])
  } catch (error) {
    const fallback = label('plugins_failed_install', 'Failed to install plugin.')
    setPluginNotice('danger', error?.response?.data?.message ?? fallback)
  }
}

const installZipPlugin = async () => {
  if (!pluginZip.value) {
    return
  }

  const formData = new FormData()
  formData.append('plugin_zip', pluginZip.value)

  try {
    await axios.post(props.apiRoutes.pluginInstallZip, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    pluginZip.value = null
    const zipInput = document.getElementById('plugin-zip-input')

    if (zipInput) {
      zipInput.value = ''
    }

    setPluginNotice('success', label('plugins_installed', 'Plugin installed.'))
    await Promise.all([loadPlugins(), loadExtensions()])
  } catch (error) {
    const fallback = label('plugins_failed_install', 'Failed to install plugin.')
    setPluginNotice('danger', error?.response?.data?.message ?? fallback)
  }
}

const uninstallPlugin = async (item) => {
  if (!window.confirm(label('plugins_confirm_uninstall', 'Uninstall this plugin?'))) {
    return
  }

  pluginUpdating.value = {
    ...pluginUpdating.value,
    [item.slug]: true,
  }

  try {
    await axios.delete(pluginUninstallRoute(item.slug))
    setPluginNotice('success', label('plugins_uninstalled', 'Plugin uninstalled.'))
    await Promise.all([loadPlugins(), loadExtensions()])
  } catch (error) {
    const fallback = label('plugins_failed_uninstall', 'Failed to uninstall plugin.')
    setPluginNotice('danger', error?.response?.data?.message ?? fallback)
  } finally {
    pluginUpdating.value = {
      ...pluginUpdating.value,
      [item.slug]: false,
    }
  }
}

onMounted(async () => {
  await Promise.all([loadPlugins(), loadExtensions()])
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <UiCard tag="section" class="shadow-sm" padding="none">
        <header class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-base font-semibold text-slate-900">{{ label('plugins_list_title', 'Plugins & Marketplace') }}</h2>
          <p class="mt-1 text-sm text-slate-500">{{ label('plugins_list_subtitle', 'Install, uninstall, and control plugin runtime states.') }}</p>
        </header>

        <div class="space-y-4 p-4">
          <UiAlert v-if="pluginNotice.text" :tone="pluginNotice.tone">
            {{ pluginNotice.text }}
          </UiAlert>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="space-y-2 rounded border border-slate-200 p-3">
              <p class="text-sm font-medium text-slate-900">{{ label('plugins_install_composer', 'Install from Composer package') }}</p>
              <UiInput
                v-model="composerPackageName"
                type="text"
                :placeholder="label('plugins_package_placeholder', 'vendor/plugin-package')"
              />
              <UiButton type="button" tone="neutral" radius="lg" @click="installComposerPlugin">
                {{ label('plugins_install', 'Install') }}
              </UiButton>
            </div>

            <div class="space-y-2 rounded border border-slate-200 p-3">
              <p class="text-sm font-medium text-slate-900">{{ label('plugins_install_zip', 'Install from ZIP') }}</p>
              <input
                id="plugin-zip-input"
                type="file"
                accept=".zip"
                class="block w-full rounded border border-slate-200 px-2 py-1 text-sm"
                @change="(event) => { pluginZip = event.target.files?.[0] ?? null }"
              >
              <UiButton type="button" tone="neutral" radius="lg" @click="installZipPlugin">
                {{ label('plugins_install', 'Install') }}
              </UiButton>
            </div>
          </div>

          <div class="space-y-2 rounded border border-slate-200 p-3">
            <p class="text-sm font-medium text-slate-900">{{ label('plugins_extensions_title', 'Extension points') }}</p>

            <p v-if="pluginExtensionsLoading" class="text-sm text-slate-500">{{ label('loading', 'Loading...') }}</p>
            <div v-else-if="pluginExtensions" class="grid grid-cols-1 gap-2 text-sm text-slate-700 md:grid-cols-2">
              <div>
                <p class="text-slate-500">{{ label('plugins_extension_field_types', 'Field types') }}</p>
                <p class="font-medium">{{ (pluginExtensions.field_types ?? []).length }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ label('plugins_extension_blocks', 'Blocks') }}</p>
                <p class="font-medium">{{ (pluginExtensions.blocks ?? []).length }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ label('plugins_extension_widgets', 'Dashboard widgets') }}</p>
                <p class="font-medium">{{ (pluginExtensions.dashboard_widgets ?? []).length }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ label('plugins_extension_actions', 'Automation actions') }}</p>
                <p class="font-medium">{{ (pluginExtensions.automation_actions ?? []).length }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ label('plugins_extension_menu_items', 'Menu items') }}</p>
                <p class="font-medium">{{ (pluginExtensions.menu_items ?? []).length }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="pluginsLoading" class="px-4 pb-4 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</div>

        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in plugins"
            :key="item.slug"
            class="flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <div class="flex items-center gap-2">
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <UiStatusBadge :tone="item.is_compatible ? 'success' : 'warning'">
                  {{ item.is_compatible ? label('plugins_compatible', 'Compatible') : label('plugins_incompatible', 'Incompatible') }}
                </UiStatusBadge>
                <UiStatusBadge v-if="item.safe_mode_disabled_at" tone="warning">
                  {{ label('plugins_safe_mode_disabled', 'Safe mode disabled') }}
                </UiStatusBadge>
              </div>
              <p class="text-sm text-slate-500">{{ item.slug }} • {{ item.version || '-' }} • {{ item.source_type || '-' }}</p>
              <p v-if="item.description" class="text-sm text-slate-600">{{ item.description }}</p>
              <p v-if="item.last_error" class="text-sm text-amber-700">{{ item.last_error }}</p>
              <p v-if="(item.compatibility_issues ?? []).length" class="text-sm text-amber-700">
                {{ (item.compatibility_issues ?? []).join(' | ') }}
              </p>
            </div>

            <div class="flex items-center gap-2">
              <UiButton
                type="button"
                tone="neutral"
                radius="lg"
                :disabled="pluginUpdating[item.slug]"
                @click="togglePlugin(item)"
              >
                {{ pluginUpdating[item.slug]
                  ? label('loading', 'Loading...')
                  : (item.enabled ? label('modules_enabled', 'Enabled') : label('modules_disabled', 'Disabled'))
                }}
              </UiButton>

              <UiButton
                type="button"
                tone="danger"
                radius="lg"
                :disabled="pluginUpdating[item.slug]"
                @click="uninstallPlugin(item)"
              >
                {{ label('plugins_uninstall', 'Uninstall') }}
              </UiButton>
            </div>
          </li>
        </ul>
      </UiCard>
    </div>
  </AdminLayout>
</template>
