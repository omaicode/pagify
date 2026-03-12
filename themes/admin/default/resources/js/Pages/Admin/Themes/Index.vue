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
const creating = ref(false)
const searchTerm = ref('')
const statusFilter = ref('all')
const selectedSiteId = ref(currentSiteId.value ? String(currentSiteId.value) : '')

const createForm = ref({
  slug: '',
  name: '',
  version: '1.0.0',
  description: '',
  author: '',
})

const slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

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

const canCreate = computed(() => {
  return slugPattern.test(createForm.value.slug)
    && String(createForm.value.name ?? '').trim() !== ''
    && !creating.value
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

const createTheme = async () => {
  if (!canCreate.value) {
    return
  }

  creating.value = true
  errorMessage.value = ''
  noticeMessage.value = ''

  try {
    await axios.post(props.apiRoutes.store, createForm.value)
    noticeMessage.value = label('themes_created', 'Theme created.')
    toast.success(noticeMessage.value)

    createForm.value = {
      slug: '',
      name: '',
      version: '1.0.0',
      description: '',
      author: '',
    }

    await loadThemes()
  } catch (error) {
    errorMessage.value = resolveActionError(error, label('themes_failed_create', 'Failed to create theme.'))
    toast.error(errorMessage.value)
  } finally {
    creating.value = false
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

const saveTheme = async (theme) => {
  setBusy(theme.slug, true)
  errorMessage.value = ''
  noticeMessage.value = ''

  try {
    await axios.patch(updateRoute(theme.slug), {
      name: theme.name,
      version: theme.version,
      description: theme.description,
      author: theme.author,
    })

    noticeMessage.value = label('themes_updated', 'Theme updated.')
    toast.success(noticeMessage.value)
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
        <h2 class="text-base font-semibold text-slate-900">{{ label('themes_create_title', 'Create theme') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ label('themes_create_subtitle', 'Create a new theme skeleton under themes/main/{THEME_NAME}.') }}</p>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <UiInput v-model="createForm.slug" :placeholder="label('slug', 'Slug')" />
          <UiInput v-model="createForm.name" :placeholder="label('name', 'Name')" />
          <UiInput v-model="createForm.version" :placeholder="label('themes_version', 'Version')" />
          <UiInput v-model="createForm.author" :placeholder="label('themes_author', 'Author')" />
          <UiInput v-model="createForm.description" :placeholder="label('description', 'Description')" class="md:col-span-2" />
        </div>

        <p v-if="createForm.slug && !slugPattern.test(createForm.slug)" class="mt-2 text-xs text-amber-700">
          {{ label('themes_slug_rule', 'Slug must use lowercase letters, numbers, and single hyphens.') }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-2">
          <UiButton type="button" tone="primary" :disabled="!canCreate" @click="createTheme">
            {{ creating ? label('loading', 'Loading...') : label('themes_create', 'Create theme') }}
          </UiButton>
          <p class="text-xs text-slate-500">{{ label('themes_manifest_required', 'Each theme must include a valid theme.json manifest.') }}</p>
        </div>
      </UiCard>

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
          <li v-for="theme in filteredThemes" :key="theme.slug" class="space-y-3 px-4 py-4">
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-medium text-slate-900">{{ theme.slug }}</p>
              <UiStatusBadge v-if="theme.is_default" tone="neutral">{{ label('themes_default', 'Default') }}</UiStatusBadge>
              <UiStatusBadge v-if="theme.is_active_for_current_site" tone="success">{{ label('themes_active', 'Active for current site') }}</UiStatusBadge>
              <UiStatusBadge v-if="Number(theme.usage_count ?? 0) > 0" tone="neutral">
                {{ label('themes_in_use', 'In use') }}: {{ theme.usage_count }}
              </UiStatusBadge>
              <UiStatusBadge :tone="theme.is_valid ? 'success' : 'warning'">
                {{ theme.is_valid ? label('themes_valid', 'Valid') : label('themes_invalid', 'Invalid') }}
              </UiStatusBadge>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <UiInput v-model="theme.name" :placeholder="label('name', 'Name')" />
              <UiInput v-model="theme.version" :placeholder="label('themes_version', 'Version')" />
              <UiInput v-model="theme.author" :placeholder="label('themes_author', 'Author')" />
              <UiInput v-model="theme.description" :placeholder="label('description', 'Description')" />
            </div>

            <div v-if="(theme.issues ?? []).length > 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              <p class="font-medium">{{ label('themes_manifest_issues', 'Manifest issues') }}</p>
              <ul class="list-disc pl-5">
                <li v-for="issue in theme.issues" :key="issue">{{ issue }}</li>
              </ul>
            </div>

            <div v-if="(theme.active_sites ?? []).length > 0" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
              <p class="font-medium text-slate-800">{{ label('themes_active_sites', 'Active on sites') }}</p>
              <p class="mt-1">{{ formatActiveSites(theme) }}</p>
            </div>

            <p v-if="theme.can_delete !== true" class="text-xs text-slate-500">
              {{ theme.is_default
                ? label('themes_delete_blocked_default', 'Default theme cannot be deleted.')
                : label('themes_delete_blocked_in_use', 'Theme cannot be deleted while in use.')
              }}
            </p>

            <div class="flex flex-wrap gap-2">
              <UiButton type="button" tone="neutral" :disabled="busyBySlug[theme.slug]" @click="saveTheme(theme)">
                {{ busyBySlug[theme.slug] ? label('loading', 'Loading...') : label('update', 'Update') }}
              </UiButton>
              <UiButton type="button" tone="primary" :disabled="busyBySlug[theme.slug] || !theme.is_valid" @click="activateTheme(theme)">
                {{ label('themes_activate', 'Activate') }}
              </UiButton>
              <UiButton
                type="button"
                tone="danger"
                :disabled="busyBySlug[theme.slug] || theme.can_delete !== true"
                @click="removeTheme(theme)"
              >
                {{ label('delete', 'Delete') }}
              </UiButton>
            </div>
          </li>
        </ul>
      </UiCard>
    </div>
  </AdminLayout>
</template>
