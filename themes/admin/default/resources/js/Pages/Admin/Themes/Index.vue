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

const props = defineProps({
  apiRoutes: {
    type: Object,
    required: true,
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})
const currentSiteId = computed(() => page.props.currentSite?.id ?? null)

const label = (key, fallback) => t.value?.[key] ?? fallback

const loading = ref(false)
const themes = ref([])
const creating = ref(false)
const createForm = ref({
  slug: '',
  name: '',
  version: '1.0.0',
  description: '',
  author: '',
})

const updateRoute = (slug) => props.apiRoutes.updateBase.replace('__THEME__', slug)
const activateRoute = (slug) => props.apiRoutes.activateBase.replace('__THEME__', slug)
const deleteRoute = (slug) => props.apiRoutes.deleteBase.replace('__THEME__', slug)

const loadThemes = async () => {
  loading.value = true

  try {
    const response = await axios.get(props.apiRoutes.index)
    themes.value = response.data.data ?? []
  } finally {
    loading.value = false
  }
}

const createTheme = async () => {
  creating.value = true

  try {
    await axios.post(props.apiRoutes.store, createForm.value)
    toast.success(label('themes_created', 'Theme created.'))
    createForm.value = {
      slug: '',
      name: '',
      version: '1.0.0',
      description: '',
      author: '',
    }
    await loadThemes()
  } catch (error) {
    toast.error(error?.response?.data?.message ?? label('themes_failed_create', 'Failed to create theme.'))
  } finally {
    creating.value = false
  }
}

const activateTheme = async (theme) => {
  try {
    await axios.put(activateRoute(theme.slug), {
      site_id: currentSiteId.value,
    })
    toast.success(label('themes_activated', 'Theme activated.'))
    await loadThemes()
  } catch (error) {
    toast.error(error?.response?.data?.message ?? label('themes_failed_activate', 'Failed to activate theme.'))
  }
}

const saveTheme = async (theme) => {
  try {
    await axios.patch(updateRoute(theme.slug), {
      name: theme.name,
      version: theme.version,
      description: theme.description,
      author: theme.author,
    })
    toast.success(label('themes_updated', 'Theme updated.'))
    await loadThemes()
  } catch (error) {
    toast.error(error?.response?.data?.message ?? label('themes_failed_update', 'Failed to update theme.'))
  }
}

const removeTheme = async (theme) => {
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

  try {
    await axios.delete(deleteRoute(theme.slug))
    toast.success(label('themes_deleted', 'Theme deleted.'))
    await loadThemes()
  } catch (error) {
    toast.error(error?.response?.data?.message ?? label('themes_failed_delete', 'Failed to delete theme.'))
  }
}

onMounted(async () => {
  await loadThemes()
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <UiCard tag="section" class="p-4 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">{{ label('themes_title', 'Theme manager') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ label('themes_subtitle', 'Manage frontend themes stored in themes/main/{THEME_NAME}.') }}</p>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <UiInput v-model="createForm.slug" :label="label('slug', 'Slug')" />
          <UiInput v-model="createForm.name" :label="label('name', 'Name')" />
          <UiInput v-model="createForm.version" :label="label('themes_version', 'Version')" />
          <UiInput v-model="createForm.author" :label="label('themes_author', 'Author')" />
          <UiInput v-model="createForm.description" :label="label('description', 'Description')" class="md:col-span-2" />
        </div>

        <div class="mt-4">
          <UiButton type="button" tone="primary" :disabled="creating" @click="createTheme">
            {{ creating ? label('loading', 'Loading...') : label('themes_create', 'Create theme') }}
          </UiButton>
        </div>
      </UiCard>

      <UiCard tag="section" class="shadow-sm" padding="none">
        <header class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-base font-semibold text-slate-900">{{ label('themes_list_title', 'Themes') }}</h2>
        </header>

        <div v-if="loading" class="p-4 text-sm text-slate-500">{{ label('loading', 'Loading...') }}</div>
        <div v-else-if="themes.length === 0" class="p-4 text-sm text-slate-500">{{ label('themes_empty', 'No themes found.') }}</div>

        <ul v-else class="divide-y divide-slate-100">
          <li v-for="theme in themes" :key="theme.slug" class="space-y-3 px-4 py-4">
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-medium text-slate-900">{{ theme.slug }}</p>
              <UiStatusBadge v-if="theme.is_default" tone="neutral">{{ label('themes_default', 'Default') }}</UiStatusBadge>
              <UiStatusBadge v-if="theme.is_active_for_current_site" tone="success">{{ label('themes_active', 'Active for current site') }}</UiStatusBadge>
              <UiStatusBadge :tone="theme.is_valid ? 'success' : 'warning'">
                {{ theme.is_valid ? label('themes_valid', 'Valid') : label('themes_invalid', 'Invalid') }}
              </UiStatusBadge>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <UiInput v-model="theme.name" :label="label('name', 'Name')" />
              <UiInput v-model="theme.version" :label="label('themes_version', 'Version')" />
              <UiInput v-model="theme.author" :label="label('themes_author', 'Author')" />
              <UiInput v-model="theme.description" :label="label('description', 'Description')" />
            </div>

            <div v-if="(theme.issues ?? []).length > 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              <p class="font-medium">{{ label('themes_manifest_issues', 'Manifest issues') }}</p>
              <ul class="list-disc pl-5">
                <li v-for="issue in theme.issues" :key="issue">{{ issue }}</li>
              </ul>
            </div>

            <div class="flex flex-wrap gap-2">
              <UiButton type="button" tone="neutral" @click="saveTheme(theme)">{{ label('update', 'Update') }}</UiButton>
              <UiButton type="button" tone="primary" @click="activateTheme(theme)">{{ label('themes_activate', 'Activate') }}</UiButton>
              <UiButton
                type="button"
                tone="danger"
                :disabled="theme.can_delete !== true"
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
