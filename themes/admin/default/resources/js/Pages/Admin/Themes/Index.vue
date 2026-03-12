<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import UiCard from '../../../Components/UI/UiCard.vue'
import UiButton from '../../../Components/UI/UiButton.vue'
import UiInput from '../../../Components/UI/UiInput.vue'
import UiStatusBadge from '../../../Components/UI/UiStatusBadge.vue'
import UiAlert from '../../../Components/UI/UiAlert.vue'
import UiPageHeader from '../../../Components/UI/UiPageHeader.vue'

const props = defineProps({
  apiRoutes: {
    type: Object,
    required: true,
  },
  sites: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})
const currentSiteId = computed(() => page.props.currentSite?.id ?? null)

const label = (key, fallback) => t.value?.[key] ?? fallback

const loading = ref(false)
const busyBySlug = ref({})
const errorMessage = ref('')
const noticeMessage = ref('')
const themes = ref([])
const searchTerm = ref('')
const statusFilter = ref('all')
const selectedSiteId = ref(currentSiteId.value ? String(currentSiteId.value) : '')
const managingThemeSlug = ref('')
const editingThemeSlug = ref('')
const editForm = ref({
  name: '',
  description: '',
  author: '',
  version: '',
})

const filteredThemes = computed(() => {
  const keyword = searchTerm.value.trim().toLowerCase()

  return themes.value.filter((theme) => {
    if (statusFilter.value === 'invalid' && theme.is_valid) {
      return false
    }

    if (statusFilter.value === 'active' && !theme.is_active_for_current_site) {
      return false
    }

    if (statusFilter.value === 'in_use' && Number(theme.usage_count ?? 0) === 0) {
      return false
    }

    if (statusFilter.value === 'default' && !theme.is_default) {
      return false
    }

    if (!keyword) {
      return true
    }

    const text = [
      theme.slug,
      theme.name,
      theme.version,
      theme.author,
      theme.description,
    ].join(' ').toLowerCase()

    return text.includes(keyword)
  })
})

const updateRoute = (slug) => props.apiRoutes.updateBase.replace('__THEME__', slug)
const activateRoute = (slug) => props.apiRoutes.activateBase.replace('__THEME__', slug)
const deleteRoute = (slug) => props.apiRoutes.deleteBase.replace('__THEME__', slug)

const setBusy = (slug, value) => {
  busyBySlug.value = {
    ...busyBySlug.value,
    [slug]: value,
  }
}

const openManageMenu = (slug) => {
  managingThemeSlug.value = managingThemeSlug.value === slug ? '' : slug
}

const closeManageMenu = () => {
  managingThemeSlug.value = ''
}

const startEditProperties = (theme) => {
  editingThemeSlug.value = theme.slug
  editForm.value = {
    name: theme.name ?? '',
    description: theme.description ?? '',
    author: theme.author ?? '',
    version: theme.version ?? '',
  }
  closeManageMenu()
}

const cancelEditProperties = () => {
  editingThemeSlug.value = ''
  editForm.value = {
    name: '',
    description: '',
    author: '',
    version: '',
  }
}

const formatActiveSites = (theme) => {
  const sites = Array.isArray(theme.active_sites) ? theme.active_sites : []

  return sites.map((site) => `${site.name} (${site.slug})`).join(' | ')
}

const resolveActionError = (error, fallback) => {
  const code = error?.response?.data?.code

  if (code === 'THEME_LOCKED') {
    return label('themes_error_locked', 'Default theme cannot be deleted.')
  }

  if (code === 'THEME_IN_USE') {
    return error?.response?.data?.message ?? label('themes_error_in_use', 'Theme is currently used by one or more sites.')
  }

  if (code === 'THEME_INVALID') {
    return label('themes_error_invalid', 'Theme manifest is invalid.')
  }

  return error?.response?.data?.message ?? fallback
}

const loadThemes = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await axios.get(props.apiRoutes.index)
    themes.value = response.data.data ?? []

    if (!selectedSiteId.value && props.sites.length > 0) {
      selectedSiteId.value = String(props.sites[0]?.id ?? '')
    }
  } catch (error) {
    errorMessage.value = resolveActionError(error, label('themes_failed_load', 'Failed to load themes.'))
  } finally {
    loading.value = false
  }
}

const activateTheme = async (theme) => {
  setBusy(theme.slug, true)
  errorMessage.value = ''
  noticeMessage.value = ''

  try {
    await axios.put(activateRoute(theme.slug), {
      site_id: selectedSiteId.value ? Number(selectedSiteId.value) : null,
    })

    noticeMessage.value = label('themes_activated', 'Theme activated.')
    toast.success(noticeMessage.value)
    await loadThemes()
  } catch (error) {
    errorMessage.value = resolveActionError(error, label('themes_failed_activate', 'Failed to activate theme.'))
    toast.error(errorMessage.value)
  } finally {
    setBusy(theme.slug, false)
  }
}

const saveThemeProperties = async (theme) => {
  setBusy(theme.slug, true)
  errorMessage.value = ''
  noticeMessage.value = ''

  try {
    await axios.patch(updateRoute(theme.slug), {
      name: editForm.value.name,
      version: editForm.value.version,
      description: editForm.value.description,
      author: editForm.value.author,
    })

    noticeMessage.value = label('themes_updated', 'Theme updated.')
    toast.success(noticeMessage.value)
    cancelEditProperties()
    await loadThemes()
  } catch (error) {
    errorMessage.value = resolveActionError(error, label('themes_failed_update', 'Failed to update theme.'))
    toast.error(errorMessage.value)
  } finally {
    setBusy(theme.slug, false)
  }
}

const removeTheme = async (theme) => {
  if (theme.can_delete !== true) {
    return
  }

  closeManageMenu()

  const result = await Swal.fire({
    title: label('delete', 'Delete'),
    text: label('themes_confirm_delete', 'Delete this theme?'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: label('delete', 'Delete'),
    cancelButtonText: label('cancel', 'Cancel'),
    reverseButtons: true,
    buttonsStyling: false,
    customClass: {
      popup: 'pf-swal-popup',
      title: 'pf-swal-title',
      htmlContainer: 'pf-swal-content',
      confirmButton: 'pf-swal-confirm',
      cancelButton: 'pf-swal-cancel',
    },
  })

  if (!result.isConfirmed) {
    return
  }

  setBusy(theme.slug, true)
  errorMessage.value = ''
  noticeMessage.value = ''

  try {
    await axios.delete(deleteRoute(theme.slug))
    noticeMessage.value = label('themes_deleted', 'Theme deleted.')
    toast.success(noticeMessage.value)
    await loadThemes()
  } catch (error) {
    errorMessage.value = resolveActionError(error, label('themes_failed_delete', 'Failed to delete theme.'))
    toast.error(errorMessage.value)
  } finally {
    setBusy(theme.slug, false)
  }
}

onMounted(async () => {
  if (!selectedSiteId.value && props.sites.length > 0) {
    selectedSiteId.value = String(props.sites[0]?.id ?? '')
  }

  await loadThemes()
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <UiPageHeader
        :title="label('themes_title', 'Theme manager')"
        :subtitle="label('themes_subtitle', 'Manage frontend themes stored in themes/main/{THEME_NAME}.')"
      />

      <UiAlert v-if="errorMessage" tone="danger">{{ errorMessage }}</UiAlert>
      <UiAlert v-if="noticeMessage" tone="success">{{ noticeMessage }}</UiAlert>

      <UiCard tag="section" class="p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <UiInput v-model="searchTerm" :placeholder="label('themes_search_placeholder', 'Search themes...')" class="md:col-span-2" />

          <select v-model="statusFilter" class="pf-input">
            <option value="all">{{ label('themes_filter_all', 'All statuses') }}</option>
            <option value="active">{{ label('themes_filter_active', 'Active for current site') }}</option>
            <option value="default">{{ label('themes_filter_default', 'Default themes') }}</option>
            <option value="in_use">{{ label('themes_filter_in_use', 'In use') }}</option>
            <option value="invalid">{{ label('themes_filter_invalid', 'Invalid manifests') }}</option>
          </select>

          <select v-model="selectedSiteId" class="pf-input">
            <option value="">{{ label('themes_site_auto', 'Use current site') }}</option>
            <option v-for="site in sites" :key="site.id" :value="String(site.id)">
              {{ site.name }} ({{ site.slug }})
            </option>
          </select>
        </div>
      </UiCard>

      <UiCard tag="section" class="shadow-sm" padding="none">
        <header class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-base font-semibold text-slate-900">{{ label('themes_list_title', 'Themes') }}</h2>
        </header>

        <div v-if="loading" class="p-4 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</div>
        <div v-else-if="filteredThemes.length === 0" class="p-4 text-sm text-slate-500">{{ label('themes_empty', 'No themes found.') }}</div>

        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="theme in filteredThemes"
            :key="theme.slug"
            class="px-4 py-4"
          >
            <div class="flex items-start gap-4">
              <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                <img
                  v-if="theme.thumbnail_url"
                  :src="theme.thumbnail_url"
                  :alt="theme.name"
                  class="h-full w-full object-cover"
                >
                <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {{ (theme.slug || 'na').slice(0, 3) }}
                </div>
              </div>

              <div class="min-w-0 flex-1 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="truncate text-base font-semibold text-slate-900">{{ theme.name || theme.slug }}</h3>
                  <UiStatusBadge v-if="theme.is_default" tone="neutral">{{ label('themes_default', 'Default') }}</UiStatusBadge>
                  <UiStatusBadge v-if="theme.is_active_for_current_site" tone="success">{{ label('themes_active', 'Active for current site') }}</UiStatusBadge>
                  <UiStatusBadge v-if="Number(theme.usage_count ?? 0) > 0" tone="neutral">{{ label('themes_in_use', 'In use') }}: {{ theme.usage_count }}</UiStatusBadge>
                  <UiStatusBadge :tone="theme.is_valid ? 'success' : 'warning'">{{ theme.is_valid ? label('themes_valid', 'Valid') : label('themes_invalid', 'Invalid') }}</UiStatusBadge>
                </div>

                <p class="text-sm text-slate-600">{{ theme.description || label('themes_no_description', 'No description.') }}</p>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                  <span>{{ label('themes_meta_author', 'Author') }}: {{ theme.author || '-' }}</span>
                  <span>{{ label('themes_meta_version', 'Version') }}: {{ theme.version || '-' }}</span>
                  <span>{{ label('slug', 'Slug') }}: {{ theme.slug }}</span>
                </div>

                <div v-if="(theme.active_sites ?? []).length > 0" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                  <p class="font-medium text-slate-800">{{ label('themes_active_sites', 'Active on sites') }}</p>
                  <p class="mt-1">{{ formatActiveSites(theme) }}</p>
                </div>

                <div v-if="editingThemeSlug === theme.slug" class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 p-3 md:grid-cols-2">
                  <UiInput v-model="editForm.name" :placeholder="label('name', 'Name')" />
                  <UiInput v-model="editForm.version" :placeholder="label('themes_version', 'Version')" />
                  <UiInput v-model="editForm.author" :placeholder="label('themes_author', 'Author')" />
                  <UiInput v-model="editForm.description" :placeholder="label('description', 'Description')" class="md:col-span-2" />

                  <div class="md:col-span-2 flex flex-wrap gap-2">
                    <UiButton type="button" tone="primary" :disabled="busyBySlug[theme.slug]" @click="saveThemeProperties(theme)">
                      {{ busyBySlug[theme.slug] ? label('loading', 'Loading...') : label('themes_save_properties', 'Save properties') }}
                    </UiButton>
                    <UiButton type="button" tone="neutral" :disabled="busyBySlug[theme.slug]" @click="cancelEditProperties">
                      {{ label('cancel', 'Cancel') }}
                    </UiButton>
                  </div>
                </div>
              </div>

              <div class="relative shrink-0">
                <div class="flex items-center gap-2">
                  <UiButton
                    type="button"
                    tone="primary"
                    :disabled="busyBySlug[theme.slug] || !theme.is_valid"
                    @click="activateTheme(theme)"
                  >
                    {{ label('themes_activate', 'Activate') }}
                  </UiButton>

                  <UiButton
                    type="button"
                    tone="neutral"
                    :disabled="busyBySlug[theme.slug]"
                    @click="openManageMenu(theme.slug)"
                  >
                    {{ label('themes_manage', 'Manage') }}
                  </UiButton>
                </div>

                <div
                  v-if="managingThemeSlug === theme.slug"
                  class="absolute right-0 z-20 mt-2 w-44 rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                  <button
                    type="button"
                    class="block w-full rounded px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    @click="startEditProperties(theme)"
                  >
                    {{ label('themes_manage_edit_properties', 'Edit Properties') }}
                  </button>
                  <button
                    type="button"
                    class="block w-full rounded px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50 disabled:text-slate-400"
                    :disabled="theme.can_delete !== true"
                    @click="removeTheme(theme)"
                  >
                    {{ label('themes_manage_delete', 'Delete') }}
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </UiCard>

      <div v-if="managingThemeSlug" class="fixed inset-0 z-10" @click="closeManageMenu" />
    </div>
  </AdminLayout>
</template>
