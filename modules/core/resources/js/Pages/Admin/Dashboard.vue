<script setup>
import AdminLayout from '../../Layouts/AdminLayout.vue';

defineProps({
    message: {
        type: String,
        default: '',
    },
    admin: {
        type: Object,
        default: null,
    },
    canManageTokens: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <AdminLayout>
        <h1 class="mb-2 text-xl font-semibold">Dashboard</h1>
        <p class="mb-4 text-sm text-slate-600">{{ message }}</p>

        <div class="rounded border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Logged in as</p>
            <p class="text-base font-medium">{{ admin?.name }} ({{ admin?.username }})</p>

            <div v-if="canManageTokens" class="mt-3">
                <a href="/admin/api-tokens" class="inline-block rounded border border-slate-300 px-3 py-1 text-sm text-slate-700">
                    Manage API tokens
                </a>
            </div>
        </div>
    </AdminLayout>
</template>
