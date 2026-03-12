<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue'
import UiCard from '@admin-theme/Components/UI/UiCard.vue'
import UiButton from '@admin-theme/Components/UI/UiButton.vue'
import UiInput from '@admin-theme/Components/UI/UiInput.vue'
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue'
import UiTableShell from '@admin-theme/Components/UI/UiTableShell.vue'
import UiCrudActions from '@admin-theme/Components/UI/UiCrudActions.vue'
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue'

const props = defineProps({
  apiRoutes: {
    type: Object,
    required: true,
  },
})

const page = usePage()
const t = computed(() => page.props.translations?.ui ?? {})

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const permissions = ref([])
const editingId = ref(null)

const form = reactive({
  name: '',
})

const loadPermissions = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await axios.get(props.apiRoutes.index)
    permissions.value = response.data?.data ?? []
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_load_permissions ?? 'Failed to load permissions.')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.name = ''
  editingId.value = null
}

const startEdit = (permission) => {
  editingId.value = permission.id
  form.name = permission.name
}

const submit = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    if (editingId.value === null) {
      await axios.post(props.apiRoutes.index, {
        name: form.name,
      })
      successMessage.value = t.value.permissions_created ?? 'Permission created.'
      toast.success(successMessage.value)
    } else {
      await axios.patch(`${props.apiRoutes.index}/${editingId.value}`, {
        name: form.name,
      })
      successMessage.value = t.value.permissions_updated ?? 'Permission updated.'
      toast.success(successMessage.value)
    }

    resetForm()
    await loadPermissions()
  } catch (error) {
    const message = error?.response?.data?.message ?? (t.value.failed_save_permission ?? 'Failed to save permission.')
    errorMessage.value = message
    toast.error(message)
  } finally {
    loading.value = false
  }
}

const destroyPermission = async (permissionId) => {
  const result = await Swal.fire({
    title: t.value.delete ?? 'Delete',
    text: t.value.confirm_delete_permission ?? 'Delete this permission?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t.value.delete ?? 'Delete',
    cancelButtonText: t.value.cancel ?? 'Cancel',
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

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await axios.delete(`${props.apiRoutes.index}/${permissionId}`)
    successMessage.value = t.value.permissions_deleted ?? 'Permission deleted.'
    toast.success(successMessage.value)
    if (editingId.value === permissionId) {
      resetForm()
    }
    await loadPermissions()
  } catch (error) {
    const message = error?.response?.data?.message ?? (t.value.failed_delete_permission ?? 'Failed to delete permission.')
    errorMessage.value = message
    toast.error(message)
  } finally {
    loading.value = false
  }
}

onMounted(loadPermissions)
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <UiPageHeader
        :title="t.permissions_title ?? 'Permission management'"
        :subtitle="t.permissions_subtitle ?? 'Create, update, and remove administrator permissions.'"
      />

      <UiAlert v-if="errorMessage" tone="danger">{{ errorMessage }}</UiAlert>
      <UiAlert v-if="successMessage" tone="success">{{ successMessage }}</UiAlert>

      <UiCard tag="form" class="grid grid-cols-1 gap-2 md:grid-cols-3" @submit.prevent="submit">
        <UiInput v-model="form.name" class="md:col-span-2" type="text" required :placeholder="t.permissions_name_placeholder ?? 'Permission name (e.g. core.report.view)'" />
        <div class="flex items-center gap-2">
          <UiButton type="submit" radius="lg" :disabled="loading">
            {{ editingId === null ? (t.create ?? 'Create') : (t.update ?? 'Update') }}
          </UiButton>
          <UiButton v-if="editingId !== null" type="button" tone="neutral" radius="lg" @click="resetForm">
            {{ t.reset ?? 'Reset' }}
          </UiButton>
        </div>
      </UiCard>

      <UiTableShell>
        <template #head>
          <tr>
            <th class="px-3 py-2 text-left">{{ t.name ?? 'Name' }}</th>
            <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
          </tr>
        </template>

        <template #body>
          <tr v-for="permission in permissions" :key="permission.id">
            <td class="px-3 py-2">{{ permission.name }}</td>
            <td class="px-3 py-2">
              <UiCrudActions
                :edit-label="t.edit ?? 'Edit'"
                :delete-label="t.delete ?? 'Delete'"
                @edit="startEdit(permission)"
                @delete="destroyPermission(permission.id)"
              />
            </td>
          </tr>
          <tr v-if="!loading && permissions.length === 0">
            <td colspan="2" class="px-3 py-6 text-center text-slate-500">{{ t.permissions_empty ?? 'No permissions found.' }}</td>
          </tr>
        </template>
      </UiTableShell>
    </div>
  </AdminLayout>
</template>
