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
const admins = ref([])
const availableGroups = ref([])
const editingId = ref(null)

const form = reactive({
  name: '',
  username: '',
  email: '',
  locale: 'en',
  password: '',
  password_confirmation: '',
  roles: [],
})

const loadAdmins = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const [adminResponse, groupResponse] = await Promise.all([
      axios.get(props.apiRoutes.index),
      axios.get(props.apiRoutes.groups),
    ])

    admins.value = adminResponse.data?.data ?? []
    availableGroups.value = groupResponse.data?.data ?? []
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_load_admins ?? 'Failed to load administrators.')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.name = ''
  form.username = ''
  form.email = ''
  form.locale = 'en'
  form.password = ''
  form.password_confirmation = ''
  form.roles = []
  editingId.value = null
}

const startEdit = (admin) => {
  editingId.value = admin.id
  form.name = admin.name ?? ''
  form.username = admin.username ?? ''
  form.email = admin.email ?? ''
  form.locale = admin.locale ?? 'en'
  form.password = ''
  form.password_confirmation = ''
  form.roles = Array.isArray(admin.roles) ? [...admin.roles] : []
}

const submit = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      name: form.name,
      username: form.username,
      email: form.email || null,
      locale: form.locale,
      password: form.password || undefined,
      password_confirmation: form.password_confirmation || undefined,
      roles: form.roles,
    }

    if (editingId.value === null) {
      await axios.post(props.apiRoutes.index, payload)
      successMessage.value = t.value.admins_created ?? 'Administrator created.'
    } else {
      await axios.patch(`${props.apiRoutes.index}/${editingId.value}`, payload)
      successMessage.value = t.value.admins_updated ?? 'Administrator updated.'
    }

    resetForm()
    await loadAdmins()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_save_admin ?? 'Failed to save administrator.')
  } finally {
    loading.value = false
  }
}

const destroyAdmin = async (adminId) => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await axios.delete(`${props.apiRoutes.index}/${adminId}`)
    successMessage.value = t.value.admins_deleted ?? 'Administrator deleted.'
    if (editingId.value === adminId) {
      resetForm()
    }
    await loadAdmins()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? (t.value.failed_delete_admin ?? 'Failed to delete administrator.')
  } finally {
    loading.value = false
  }
}

onMounted(loadAdmins)
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <UiPageHeader
        :title="t.admins_title ?? 'Administrator management'"
        :subtitle="t.admins_subtitle ?? 'Manage administrator accounts and group assignments within current site.'"
      />

      <UiAlert v-if="errorMessage" tone="danger">{{ errorMessage }}</UiAlert>
      <UiAlert v-if="successMessage" tone="success">{{ successMessage }}</UiAlert>

      <UiCard tag="form" class="space-y-3" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
          <UiInput v-model="form.name" type="text" required :placeholder="t.name ?? 'Name'" />
          <UiInput v-model="form.username" type="text" required :placeholder="t.username ?? 'Username'" />
          <UiInput v-model="form.email" type="email" :placeholder="t.email ?? 'Email'" />
          <UiInput v-model="form.locale" type="text" required :placeholder="t.locale ?? 'Locale'" />
          <UiInput v-model="form.password" :required="editingId === null" type="password" :placeholder="t.password ?? 'Password'" />
          <UiInput v-model="form.password_confirmation" :required="editingId === null || form.password.length > 0" type="password" :placeholder="t.password_confirmation ?? 'Confirm password'" />
        </div>

        <div>
          <p class="mb-2 text-sm font-medium text-slate-700">{{ t.admin_groups_title ?? 'Administrator groups' }}</p>
          <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <label v-for="group in availableGroups" :key="group.id" class="flex items-center gap-2 rounded border border-slate-200 px-2 py-1 text-sm">
              <input v-model="form.roles" type="checkbox" :value="group.name">
              <span>{{ group.name }}</span>
            </label>
          </div>
        </div>

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
            <th class="px-3 py-2 text-left">{{ t.username ?? 'Username' }}</th>
            <th class="px-3 py-2 text-left">{{ t.email ?? 'Email' }}</th>
            <th class="px-3 py-2 text-left">{{ t.admin_groups_title ?? 'Administrator groups' }}</th>
            <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
          </tr>
        </template>

        <template #body>
          <tr v-for="admin in admins" :key="admin.id">
            <td class="px-3 py-2">{{ admin.name }}</td>
            <td class="px-3 py-2">{{ admin.username }}</td>
            <td class="px-3 py-2">{{ admin.email ?? '-' }}</td>
            <td class="px-3 py-2">{{ Array.isArray(admin.roles) ? admin.roles.join(', ') : '-' }}</td>
            <td class="px-3 py-2">
              <UiCrudActions
                :edit-label="t.edit ?? 'Edit'"
                :delete-label="t.delete ?? 'Delete'"
                @edit="startEdit(admin)"
                @delete="destroyAdmin(admin.id)"
              />
            </td>
          </tr>
          <tr v-if="!loading && admins.length === 0">
            <td colspan="5" class="px-3 py-6 text-center text-slate-500">{{ t.admins_empty ?? 'No administrators found.' }}</td>
          </tr>
        </template>
      </UiTableShell>
    </div>
  </AdminLayout>
</template>
