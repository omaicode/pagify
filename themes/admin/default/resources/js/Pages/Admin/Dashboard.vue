<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import UiCard from '../../Components/UI/UiCard.vue';
import UiButton from '../../Components/UI/UiButton.vue';
import UiPageHeader from '../../Components/UI/UiPageHeader.vue';

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

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <UiPageHeader :title="t.dashboard ?? 'Dashboard'" :subtitle="message" />

            <div class="grid gap-3 md:grid-cols-3">
                <article class="pf-card-highlight">
                    <p class="text-xs uppercase tracking-wide text-white/80">{{ t.total_admins ?? 'Total admins' }}</p>
                    <p class="mt-2 text-3xl font-semibold">1</p>
                </article>
                <UiCard tag="article">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">{{ t.current_admin ?? 'Current admin' }}</p>
                    <p class="mt-2 text-base font-semibold text-[#1e1b4b]">{{ admin?.name ?? '-' }}</p>
                    <p class="text-sm text-[#6b7280]">{{ admin?.username ?? '-' }}</p>
                </UiCard>
                <UiCard tag="article">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">{{ t.role ?? 'Role' }}</p>
                    <p class="mt-2 text-base font-semibold text-[#1e1b4b]">{{ t.system_administrator ?? 'System Administrator' }}</p>
                </UiCard>
            </div>

            <UiCard>
                <p class="text-sm text-[#6b7280]">{{ t.logged_in_as ?? 'Logged in as' }}</p>
                <p class="text-base font-medium text-[#1e1b4b]">{{ admin?.name }} ({{ admin?.username }})</p>

                <div v-if="canManageTokens" class="mt-3">
                    <UiButton tag="a" href="/admin/api-tokens" class="inline-block">
                        {{ t.manage_api_tokens ?? 'Manage API tokens' }}
                    </UiButton>
                </div>
            </UiCard>
        </div>
    </AdminLayout>
</template>
