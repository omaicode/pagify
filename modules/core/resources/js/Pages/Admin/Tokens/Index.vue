<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    apiRoutes: {
        type: Object,
        required: true,
    },
});

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
        errorMessage.value = error?.response?.data?.message ?? 'Failed to load tokens.';
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
        errorMessage.value = error?.response?.data?.message ?? 'Failed to create token.';
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
        errorMessage.value = error?.response?.data?.message ?? 'Failed to revoke token.';
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
            <h1 class="text-xl font-semibold">API tokens</h1>
            <p class="text-sm text-slate-600">Create personal access tokens for admin API usage.</p>
        </div>

        <div v-if="errorMessage" class="mb-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ errorMessage }}
        </div>

        <div v-if="createdToken" class="mb-4 rounded border border-amber-200 bg-amber-50 p-3">
            <p class="mb-2 text-sm font-medium text-amber-900">Copy this token now (shown one time only):</p>
            <div class="flex items-center gap-2">
                <input :value="createdToken" readonly class="w-full rounded border border-amber-300 bg-white px-2 py-1 text-xs">
                <button type="button" class="rounded bg-slate-900 px-3 py-1 text-xs text-white" @click="copyToken">Copy</button>
            </div>
        </div>

        <form class="mb-4 grid grid-cols-1 gap-2 rounded border border-slate-200 bg-white p-4 md:grid-cols-4" @submit.prevent="createToken">
            <input v-model="form.name" type="text" required placeholder="Token name" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <input v-model="form.abilities" type="text" placeholder="Abilities (* or comma-separated)" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <input v-model="form.expires_at" type="datetime-local" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <button type="submit" :disabled="loading" class="rounded bg-slate-900 px-3 py-1 text-sm text-white disabled:opacity-50">Create token</button>
        </form>

        <div class="overflow-hidden rounded border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Abilities</th>
                        <th class="px-3 py-2 text-left">Last used</th>
                        <th class="px-3 py-2 text-left">Expires</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="token in tokens" :key="token.id">
                        <td class="px-3 py-2">{{ token.name }}</td>
                        <td class="px-3 py-2">{{ Array.isArray(token.abilities) ? token.abilities.join(', ') : '*' }}</td>
                        <td class="px-3 py-2">{{ token.last_used_at ?? '-' }}</td>
                        <td class="px-3 py-2">{{ token.expires_at ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="revokeToken(token.id)">Revoke</button>
                        </td>
                    </tr>
                    <tr v-if="tokens.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">No active tokens.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
