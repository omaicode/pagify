<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiCard from '../../../Components/UI/UiCard.vue';
import UiButton from '../../../Components/UI/UiButton.vue';
import UiInput from '../../../Components/UI/UiInput.vue';
import UiAlert from '../../../Components/UI/UiAlert.vue';
import UiTableShell from '../../../Components/UI/UiTableShell.vue';
import UiPageHeader from '../../../Components/UI/UiPageHeader.vue';

const props = defineProps({
    apiRoutes: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const loading = ref(false);
const errorMessage = ref('');
const tokens = ref([]);
const createdToken = ref('');

const form = reactive({
    name: '',
    abilities: '*',
    expires_at: '',
});

const parsedAbilities = computed(() => {
    const raw = form.abilities.trim();

    if (raw === '' || raw === '*') {
        return ['*'];
    }

    return raw
        .split(',')
        .map((ability) => ability.trim())
        .filter((ability) => ability.length > 0);
});

const loadTokens = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(props.apiRoutes.index);
        tokens.value = response.data?.data ?? [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? (t.value.failed_load_tokens ?? 'Failed to load tokens.');
    } finally {
        loading.value = false;
    }
};

const createToken = async () => {
    loading.value = true;
    errorMessage.value = '';
    createdToken.value = '';

    try {
        const payload = {
            name: form.name,
            abilities: parsedAbilities.value,
            expires_at: form.expires_at || null,
        };

        const response = await axios.post(props.apiRoutes.store, payload);
        createdToken.value = response.data?.data?.token ?? '';
        form.name = '';
        form.abilities = '*';
        form.expires_at = '';
        toast.success(t.value.token_created ?? 'Token created.');
        await loadTokens();
    } catch (error) {
        const message = error?.response?.data?.message ?? (t.value.failed_create_token ?? 'Failed to create token.');
        errorMessage.value = message;
        toast.error(message);
    } finally {
        loading.value = false;
    }
};

const revokeToken = async (tokenId) => {
    const result = await Swal.fire({
        title: t.value.revoke ?? 'Revoke',
        text: t.value.confirm_revoke_token ?? 'Revoke this token?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t.value.revoke ?? 'Revoke',
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
    });

    if (!result.isConfirmed) {
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        await axios.delete(`${props.apiRoutes.index}/${tokenId}`);
        toast.success(t.value.token_revoked ?? 'Token revoked.');
        await loadTokens();
    } catch (error) {
        const message = error?.response?.data?.message ?? (t.value.failed_revoke_token ?? 'Failed to revoke token.');
        errorMessage.value = message;
        toast.error(message);
    } finally {
        loading.value = false;
    }
};

const copyToken = async () => {
    if (!createdToken.value) {
        return;
    }

    await navigator.clipboard.writeText(createdToken.value);
    toast.success(t.value.copied ?? 'Copied.');
};

onMounted(loadTokens);
</script>

<template>
    <AdminLayout>
        <UiPageHeader
            class="mb-4"
            :title="t.api_tokens ?? 'API tokens'"
            :subtitle="t.api_tokens_description ?? 'Create personal access tokens for admin API usage.'"
        />

        <UiAlert v-if="errorMessage" tone="danger" class="mb-3">
            {{ errorMessage }}
        </UiAlert>

        <UiAlert v-if="createdToken" tone="warning" class="mb-4 p-3">
            <p class="mb-2 text-sm font-medium text-amber-900">{{ t.copy_token_once ?? 'Copy this token now (shown one time only):' }}</p>
            <div class="flex items-center gap-2">
                <UiInput :model-value="createdToken" readonly class="text-xs" />
                <UiButton type="button" radius="lg" size="sm" @click="copyToken">{{ t.copy ?? 'Copy' }}</UiButton>
            </div>
        </UiAlert>

        <UiCard tag="form" class="mb-4 grid grid-cols-1 gap-2 md:grid-cols-4" @submit.prevent="createToken">
            <UiInput v-model="form.name" type="text" required :placeholder="t.token_name ?? 'Token name'" />
            <UiInput v-model="form.abilities" type="text" :placeholder="t.token_abilities ?? 'Abilities (* or comma-separated)'" />
            <UiInput v-model="form.expires_at" type="datetime-local" />
            <UiButton type="submit" radius="lg" :disabled="loading">{{ t.create_token ?? 'Create token' }}</UiButton>
        </UiCard>

        <UiTableShell table-class="min-w-full divide-y divide-[#ece8ff] text-sm" head-class="bg-[#f8f6ff]">
            <template #head>
                <tr>
                    <th class="px-3 py-2 text-left">{{ t.token_name ?? 'Name' }}</th>
                    <th class="px-3 py-2 text-left">{{ t.abilities ?? 'Abilities' }}</th>
                    <th class="px-3 py-2 text-left">{{ t.last_used ?? 'Last used' }}</th>
                    <th class="px-3 py-2 text-left">{{ t.expires ?? 'Expires' }}</th>
                    <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
                </tr>
            </template>

            <template #body>
                <tr v-for="token in tokens" :key="token.id">
                    <td class="px-3 py-2">{{ token.name }}</td>
                    <td class="px-3 py-2">{{ Array.isArray(token.abilities) ? token.abilities.join(', ') : '*' }}</td>
                    <td class="px-3 py-2">{{ token.last_used_at ?? '-' }}</td>
                    <td class="px-3 py-2">{{ token.expires_at ?? '-' }}</td>
                    <td class="px-3 py-2">
                        <UiButton type="button" tone="danger" size="xs" @click="revokeToken(token.id)">{{ t.revoke ?? 'Revoke' }}</UiButton>
                    </td>
                </tr>
                <tr v-if="tokens.length === 0">
                    <td colspan="5" class="px-3 py-6 text-center text-slate-500">{{ t.no_active_tokens ?? 'No active tokens.' }}</td>
                </tr>
            </template>
        </UiTableShell>
    </AdminLayout>
</template>
