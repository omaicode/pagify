<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import UiCard from '../../../Components/UI/UiCard.vue'
import UiButton from '../../../Components/UI/UiButton.vue'
import UiInput from '../../../Components/UI/UiInput.vue'
import UiAlert from '../../../Components/UI/UiAlert.vue'
import UiTableShell from '../../../Components/UI/UiTableShell.vue'
import UiCrudActions from '../../../Components/UI/UiCrudActions.vue'
import UiPageHeader from '../../../Components/UI/UiPageHeader.vue'

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
const groups = ref([])
const availablePermissions = ref([])
const editingId = ref(null)

const form = reactive({
  name: '',
  permissions: [],
})

const loadGroups = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const [groupResponse, permissionResponse] = await Promise.all([
      axios.get(props.apiRoutes.index),
      axios.get(props.apiRoutes.permissions),
    ])

    groups.value = groupResponse.data?.data ?? []
    availablePermissions.value = permissionResponse.data?.data ?? []
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_load_admin_groups ?? 'Failed to load administrator groups.')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.name = ''
  form.permissions = []
  editingId.value = null
}

const startEdit = (group) => {
  editingId.value = group.id
  form.name = group.name
  form.permissions = Array.isArray(group.permissions) ? [...group.permissions] : []
}

const submit = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      name: form.name,
      permissions: form.permissions,
    }

    if (editingId.value === null) {
      await axios.post(props.apiRoutes.index, payload)
      successMessage.value = t.value.admin_groups_created ?? 'Administrator group created.'
    } else {
      await axios.patch(`${props.apiRoutes.index}/${editingId.value}`, payload)
      successMessage.value = t.value.admin_groups_updated ?? 'Administrator group updated.'
    }

    resetForm()
    await loadGroups()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_save_admin_group ?? 'Failed to save administrator group.')
  } finally {
    loading.value = false
  }
}

const destroyGroup = async (groupId) => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await axios.delete(`${props.apiRoutes.index}/${groupId}`)
    successMessage.value = t.value.admin_groups_deleted ?? 'Administrator group deleted.'
    if (editingId.value === groupId) {
      resetForm()
    }
    await loadGroups()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_delete_admin_group ?? 'Failed to delete administrator group.')
  } finally {
    loading.value = false
  }
}

onMounted(loadGroups)
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <UiPageHeader
        :title="t.admin_groups_title ?? 'Administrator group management'"
        :subtitle="t.admin_groups_subtitle ?? 'Create groups (roles) and assign permissions to each group.'"
      />

      <UiAlert v-if="errorMessage" tone="danger">{{ errorMessage }}</UiAlert>
      <UiAlert v-if="successMessage" tone="success">{{ successMessage }}</UiAlert>

      <UiCard tag="form" class="space-y-3" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
          <UiInput v-model="form.name" class="md:col-span-2" type="text" required :placeholder="t.admin_group_name_placeholder ?? 'Group name (e.g. editor)'" />
          <div class="flex items-center gap-2">
            <UiButton type="submit" radius="lg" :disabled="loading">
              {{ editingId === null ? (t.create ?? 'Create') : (t.update ?? 'Update') }}
            </UiButton>
            <UiButton v-if="editingId !== null" type="button" tone="neutral" radius="lg" @click="resetForm">
              {{ t.reset ?? 'Reset' }}
            </UiButton>
          </div>
        </div>

        <div>
          <p class="mb-2 text-sm font-medium text-slate-700">{{ t.permissions_title ?? 'Permissions' }}</p>
          <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <label v-for="permission in availablePermissions" :key="permission.id" class="flex items-center gap-2 rounded border border-slate-200 px-2 py-1 text-sm">
              <input v-model="form.permissions" type="checkbox" :value="permission.name">
              <span>{{ permission.name }}</span>
            </label>
          </div>
        </div>
      </UiCard>

      <UiTableShell>
        <template #head>
          <tr>
            <th class="px-3 py-2 text-left">{{ t.name ?? 'Name' }}</th>
            <th class="px-3 py-2 text-left">{{ t.permissions_title ?? 'Permissions' }}</th>
            <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
          </tr>
        </template>

        <template #body>
          <tr v-for="group in groups" :key="group.id">
            <td class="px-3 py-2">{{ group.name }}</td>
            <td class="px-3 py-2">{{ Array.isArray(group.permissions) ? group.permissions.join(', ') : '-' }}</td>
            <td class="px-3 py-2">
              <UiCrudActions
                :edit-label="t.edit ?? 'Edit'"
                :delete-label="t.delete ?? 'Delete'"
                @edit="startEdit(group)"
                @delete="destroyGroup(group.id)"
              />
            </td>
          </tr>
          <tr v-if="!loading && groups.length === 0">
            <td colspan="3" class="px-3 py-6 text-center text-slate-500">{{ t.admin_groups_empty ?? 'No administrator groups found.' }}</td>
          </tr>
        </template>
      </UiTableShell>
    </div>
  </AdminLayout>
</template>
