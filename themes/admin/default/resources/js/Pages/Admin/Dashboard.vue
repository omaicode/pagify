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
        <div class="space-y-4">
            <div>
                <h1 class="pf-section-title">Dashboard</h1>
                <p class="pf-section-subtitle">{{ message }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <article class="pf-card-highlight">
                    <p class="text-xs uppercase tracking-wide text-white/80">Total admins</p>
                    <p class="mt-2 text-3xl font-semibold">1</p>
                </article>
                <article class="pf-card">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">Current admin</p>
                    <p class="mt-2 text-base font-semibold text-[#1e1b4b]">{{ admin?.name ?? '-' }}</p>
                    <p class="text-sm text-[#6b7280]">{{ admin?.username ?? '-' }}</p>
                </article>
                <article class="pf-card">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">Role</p>
                    <p class="mt-2 text-base font-semibold text-[#1e1b4b]">System Administrator</p>
                </article>
            </div>

            <div class="pf-card">
                <p class="text-sm text-[#6b7280]">Logged in as</p>
                <p class="text-base font-medium text-[#1e1b4b]">{{ admin?.name }} ({{ admin?.username }})</p>

                <div v-if="canManageTokens" class="mt-3">
                    <a href="/admin/api-tokens" class="pf-btn-primary inline-block">
                    Manage API tokens
                </a>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
