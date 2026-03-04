<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

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
        await loadTokens();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? (t.value.failed_create_token ?? 'Failed to create token.');
    } finally {
        loading.value = false;
    }
};

const revokeToken = async (tokenId) => {
    loading.value = true;
    errorMessage.value = '';

    try {
        await axios.delete(`${props.apiRoutes.index}/${tokenId}`);
        await loadTokens();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? (t.value.failed_revoke_token ?? 'Failed to revoke token.');
    } finally {
        loading.value = false;
    }
};

const copyToken = async () => {
    if (!createdToken.value) {
        return;
    }

    await navigator.clipboard.writeText(createdToken.value);
};

onMounted(loadTokens);
</script>

<template>
    <AdminLayout>
        <div class="mb-4">
            <h1 class="pf-section-title">{{ t.api_tokens ?? 'API tokens' }}</h1>
            <p class="pf-section-subtitle">{{ t.api_tokens_description ?? 'Create personal access tokens for admin API usage.' }}</p>
        </div>

        <div v-if="errorMessage" class="mb-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-if="createdToken" class="mb-4 rounded border border-amber-200 bg-amber-50 p-3">
            <p class="mb-2 text-sm font-medium text-amber-900">{{ t.copy_token_once ?? 'Copy this token now (shown one time only):' }}</p>
            <div class="flex items-center gap-2">
                <input :value="createdToken" readonly class="pf-input text-xs">
                <button type="button" class="pf-btn-primary !rounded-lg !px-3 !py-1 !text-xs" @click="copyToken">{{ t.copy ?? 'Copy' }}</button>
            </div>
        </div>

        <form class="pf-card mb-4 grid grid-cols-1 gap-2 md:grid-cols-4" @submit.prevent="createToken">
            <input v-model="form.name" type="text" required :placeholder="t.token_name ?? 'Token name'" class="pf-input">
            <input v-model="form.abilities" type="text" :placeholder="t.token_abilities ?? 'Abilities (* or comma-separated)'" class="pf-input">
            <input v-model="form.expires_at" type="datetime-local" class="pf-input">
            <button type="submit" :disabled="loading" class="pf-btn-primary !rounded-lg disabled:opacity-50">{{ t.create_token ?? 'Create token' }}</button>
        </form>

        <div class="pf-card overflow-hidden p-0">
            <table class="min-w-full divide-y divide-[#ece8ff] text-sm">
                <thead class="bg-[#f8f6ff]">
                    <tr>
                        <th class="px-3 py-2 text-left">{{ t.token_name ?? 'Name' }}</th>
                        <th class="px-3 py-2 text-left">{{ t.abilities ?? 'Abilities' }}</th>
                        <th class="px-3 py-2 text-left">{{ t.last_used ?? 'Last used' }}</th>
                        <th class="px-3 py-2 text-left">{{ t.expires ?? 'Expires' }}</th>
                        <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="token in tokens" :key="token.id">
                        <td class="px-3 py-2">{{ token.name }}</td>
                        <td class="px-3 py-2">{{ Array.isArray(token.abilities) ? token.abilities.join(', ') : '*' }}</td>
                        <td class="px-3 py-2">{{ token.last_used_at ?? '-' }}</td>
                        <td class="px-3 py-2">{{ token.expires_at ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <button type="button" class="rounded-full border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="revokeToken(token.id)">{{ t.revoke ?? 'Revoke' }}</button>
                        </td>
                    </tr>
                    <tr v-if="tokens.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">{{ t.no_active_tokens ?? 'No active tokens.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
