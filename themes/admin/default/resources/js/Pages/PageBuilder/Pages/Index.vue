<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import UiStatusBadge from '@admin-theme/Components/UI/UiStatusBadge.vue';
import UiTableShell from '@admin-theme/Components/UI/UiTableShell.vue';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { toast } from 'vue3-toastify';

defineProps({
    pages: { type: Object, required: true },
    routes: { type: Object, default: () => ({}) },
});

const deletingPageId = ref(null);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const resolveItems = (pages) => {
    if (Array.isArray(pages?.data)) {
        return pages.data;
    }

    if (Array.isArray(pages)) {
        return pages;
    }

    return [];
};

const statusTone = (status) => {
    if (status === 'published') {
        return 'success';
    }

    if (status === 'scheduled') {
        return 'warning';
    }

    return 'neutral';
};

const confirmDelete = async (page) => {
    if (!page?.routes?.destroy || deletingPageId.value !== null) {
        return;
    }

    const result = await Swal.fire({
        title: label('pb_confirm_delete_title', 'Delete this page?'),
        text: label('pb_confirm_delete_text', `"${page.title}" will be permanently removed.`).replace(':title', page.title),
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
    });

    if (!result.isConfirmed) {
        return;
    }

    deletingPageId.value = page.id;

    router.delete(page.routes.destroy, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(label('pb_page_deleted', 'Page deleted.'));
        },
        onError: () => {
            toast.error(label('pb_delete_failed', 'Delete failed. Please try again.'));
        },
        onFinish: () => {
            deletingPageId.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <UiPageHeader :title="label('pb_pages_title', 'Pages')" :subtitle="label('pb_pages_subtitle', 'Manage visual pages and publishing state.')" />
                <UiButton tag="a" :href="routes.create" tone="primary" radius="lg">{{ label('pb_create_page', 'Create page') }}</UiButton>
            </div>

            <UiTableShell>
                <template #head>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label('title', 'Title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label('slug', 'Slug') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label('status', 'Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label('updated_at', 'Updated') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label('action', 'Action') }}</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="page in resolveItems(pages)" :key="page.id" class="bg-white">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ page.title }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ page.slug }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <UiStatusBadge :tone="statusTone(page.status)">{{ page.status }}</UiStatusBadge>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ page.updated_at }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <UiButton tag="a" :href="page.routes.edit" tone="outline" size="xs" radius="lg">{{ label('edit', 'Edit') }}</UiButton>
                                <UiButton
                                    v-if="page.routes?.destroy"
                                    tone="danger"
                                    size="xs"
                                    radius="lg"
                                    :disabled="deletingPageId !== null"
                                    @click="confirmDelete(page)"
                                >
                                    {{ deletingPageId === page.id ? label('pb_deleting', 'Deleting...') : label('delete', 'Delete') }}
                                </UiButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="resolveItems(pages).length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">{{ label('pb_no_pages', 'No pages yet.') }}</td>
                    </tr>
                </template>
            </UiTableShell>
        </div>
    </AdminLayout>
</template>
