<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { toast } from 'vue3-toastify';
import UiCard from '../../../Components/UI/UiCard.vue';
import UiButton from '../../../Components/UI/UiButton.vue';
import UiInput from '../../../Components/UI/UiInput.vue';
import UiField from '../../../Components/UI/UiField.vue';
import UiPageHeader from '../../../Components/UI/UiPageHeader.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    apiRoutes: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const profileForm = reactive({
    name: props.profile?.name ?? '',
    nickname: props.profile?.nickname ?? '',
    bio: props.profile?.bio ?? '',
});

const passwordForm = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const savingProfile = ref(false);
const savingPassword = ref(false);
const uploadingAvatar = ref(false);
const profileError = ref('');
const passwordError = ref('');
const avatarError = ref('');
const profileSuccess = ref('');
const passwordSuccess = ref('');
const avatarSuccess = ref('');
const avatarUrl = ref(props.profile?.avatar_url ?? null);

const submitProfile = async () => {
    savingProfile.value = true;
    profileError.value = '';
    profileSuccess.value = '';

    try {
        const response = await axios.patch(props.apiRoutes.updateProfile, {
            name: profileForm.name,
            nickname: profileForm.nickname,
            bio: profileForm.bio,
        });

        const admin = response?.data?.data?.admin;

        if (admin) {
            profileForm.name = admin.name ?? profileForm.name;
            profileForm.nickname = admin.nickname ?? '';
            profileForm.bio = admin.bio ?? '';
            avatarUrl.value = admin.avatar_url ?? null;
        }

        toast.success(t.value.profile_saved ?? 'Profile updated.');
    } catch (error) {
        profileError.value = error?.response?.data?.message ?? (t.value.profile_save_failed ?? 'Failed to update profile.');
    } finally {
        savingProfile.value = false;
    }
};

const submitPassword = async () => {
    savingPassword.value = true;
    passwordError.value = '';
    passwordSuccess.value = '';

    try {
        await axios.put(props.apiRoutes.updatePassword, {
            current_password: passwordForm.current_password,
            new_password: passwordForm.new_password,
            new_password_confirmation: passwordForm.new_password_confirmation,
        });

        passwordForm.current_password = '';
        passwordForm.new_password = '';
        passwordForm.new_password_confirmation = '';

        toast.success(t.value.profile_password_saved ?? 'Password updated.');
    } catch (error) {
        passwordError.value = error?.response?.data?.message ?? (t.value.profile_password_failed ?? 'Failed to update password.');
    } finally {
        savingPassword.value = false;
    }
};

const uploadAvatar = async (event) => {
    const file = event?.target?.files?.[0] ?? null;

    if (!file) {
        return;
    }

    uploadingAvatar.value = true;
    avatarError.value = '';
    avatarSuccess.value = '';

    try {
        const formData = new FormData();
        formData.append('avatar', file);

        const response = await axios.post(props.apiRoutes.uploadAvatar, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        avatarUrl.value = response?.data?.data?.admin?.avatar_url ?? null;
        toast.success(t.value.profile_avatar_saved ?? 'Avatar updated.');
    } catch (error) {
        avatarError.value = error?.response?.data?.message ?? (t.value.profile_avatar_failed ?? 'Failed to update avatar.');
    } finally {
        uploadingAvatar.value = false;
        if (event?.target) {
            event.target.value = '';
        }
    }
};

const removeAvatar = async () => {
    const result = await Swal.fire({
        title: t.value.profile_remove_avatar ?? 'Remove avatar',
        text: t.value.profile_confirm_remove_avatar ?? 'Remove current avatar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t.value.profile_remove_avatar ?? 'Remove avatar',
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

    uploadingAvatar.value = true;
    avatarError.value = '';
    avatarSuccess.value = '';

    try {
        await axios.delete(props.apiRoutes.removeAvatar);
        avatarUrl.value = null;
        toast.success(t.value.profile_avatar_removed ?? 'Avatar removed.');
    } catch (error) {
        avatarError.value = error?.response?.data?.message ?? (t.value.profile_avatar_remove_failed ?? 'Failed to remove avatar.');
    } finally {
        uploadingAvatar.value = false;
    }
};
</script>

<template>
    <AdminLayout>
        <UiPageHeader
            class="mb-4"
            :title="t.profile_title ?? 'Profile'"
            :subtitle="t.profile_subtitle ?? 'Manage your account details, avatar, and password.'"
        />

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <UiCard class="text-center">
                <h2 class="mb-4 text-base font-semibold text-[#1e1b4b]">{{ t.profile_avatar_title ?? 'Avatar' }}</h2>

                <div class="mb-4 flex flex-col items-center gap-4">
                    <img
                        v-if="avatarUrl"
                        :src="avatarUrl"
                        :alt="t.profile_avatar_alt ?? 'Admin avatar'"
                        class="h-28 w-28 rounded-full border border-[#e5deff] object-cover"
                    >
                    <div v-else class="flex h-28 w-28 items-center justify-center rounded-full border border-dashed border-[#d9d2ff] px-2 text-xs text-[#6b7280]">
                        {{ t.profile_no_avatar ?? 'No avatar' }}
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <UiButton tag="label" radius="lg" size="sm" class="cursor-pointer">
                            {{ uploadingAvatar ? (t.profile_uploading_avatar ?? 'Uploading...') : (t.profile_upload_avatar ?? 'Upload avatar') }}
                            <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" :disabled="uploadingAvatar" @change="uploadAvatar">
                        </UiButton>
                        <UiButton
                            type="button"
                            tone="danger"
                            radius="lg"
                            size="sm"
                            :disabled="uploadingAvatar || !avatarUrl"
                            @click="removeAvatar"
                        >
                            {{ t.profile_remove_avatar ?? 'Remove avatar' }}
                        </UiButton>
                    </div>
                </div>

                <p v-if="avatarError" class="mb-2 text-sm text-rose-700">{{ avatarError }}</p>
                <p v-if="avatarSuccess" class="text-sm text-emerald-700">{{ avatarSuccess }}</p>
            </UiCard>

            <UiCard>
                <h2 class="mb-3 text-base font-semibold text-[#1e1b4b]">{{ t.profile_info_title ?? 'Profile information' }}</h2>
                <form class="space-y-3" @submit.prevent="submitProfile">
                    <UiField :label="t.name ?? 'Name'">
                        <UiInput v-model="profileForm.name" type="text" required />
                    </UiField>
                    <UiField :label="t.profile_nickname ?? 'Nickname'">
                        <UiInput v-model="profileForm.nickname" type="text" />
                    </UiField>
                    <UiField :label="t.profile_bio ?? 'Bio'">
                        <UiInput v-model="profileForm.bio" tag="textarea" :rows="4" />
                    </UiField>
                    <p v-if="profileError" class="text-sm text-rose-700">{{ profileError }}</p>
                    <p v-if="profileSuccess" class="text-sm text-emerald-700">{{ profileSuccess }}</p>
                    <UiButton type="submit" radius="lg" :disabled="savingProfile">
                        {{ savingProfile ? (t.loading ?? 'Loading...') : (t.save ?? 'Save') }}
                    </UiButton>
                </form>
            </UiCard>

            <UiCard class="lg:col-span-2">
                <h2 class="mb-3 text-base font-semibold text-[#1e1b4b]">{{ t.profile_password_title ?? 'Change password' }}</h2>
                <form class="grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="submitPassword">
                    <UiField :label="t.profile_current_password ?? 'Current password'">
                        <UiInput v-model="passwordForm.current_password" type="password" required />
                    </UiField>
                    <UiField :label="t.profile_new_password ?? 'New password'">
                        <UiInput v-model="passwordForm.new_password" type="password" required />
                    </UiField>
                    <UiField :label="t.profile_new_password_confirmation ?? 'Confirm new password'">
                        <UiInput v-model="passwordForm.new_password_confirmation" type="password" required />
                    </UiField>

                    <div class="md:col-span-3">
                        <p v-if="passwordError" class="text-sm text-rose-700">{{ passwordError }}</p>
                        <p v-if="passwordSuccess" class="text-sm text-emerald-700">{{ passwordSuccess }}</p>
                    </div>

                    <div class="md:col-span-3">
                        <UiButton type="submit" radius="lg" :disabled="savingPassword">
                            {{ savingPassword ? (t.loading ?? 'Loading...') : (t.profile_change_password ?? 'Change password') }}
                        </UiButton>
                    </div>
                </form>
            </UiCard>
        </div>
    </AdminLayout>
</template>
