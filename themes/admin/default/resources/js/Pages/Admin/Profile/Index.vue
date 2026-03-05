<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

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

        profileSuccess.value = t.value.profile_saved ?? 'Profile updated.';
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

        passwordSuccess.value = t.value.profile_password_saved ?? 'Password updated.';
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
        avatarSuccess.value = t.value.profile_avatar_saved ?? 'Avatar updated.';
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
    uploadingAvatar.value = true;
    avatarError.value = '';
    avatarSuccess.value = '';

    try {
        await axios.delete(props.apiRoutes.removeAvatar);
        avatarUrl.value = null;
        avatarSuccess.value = t.value.profile_avatar_removed ?? 'Avatar removed.';
    } catch (error) {
        avatarError.value = error?.response?.data?.message ?? (t.value.profile_avatar_remove_failed ?? 'Failed to remove avatar.');
    } finally {
        uploadingAvatar.value = false;
    }
};
</script>

<template>
    <AdminLayout>
        <div class="mb-4">
            <h1 class="pf-section-title">{{ t.profile_title ?? 'Profile' }}</h1>
            <p class="pf-section-subtitle">{{ t.profile_subtitle ?? 'Manage your account details, avatar, and password.' }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="pf-card text-center">
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
                        <label class="pf-btn-primary !rounded-lg !px-3 !py-2 !text-xs cursor-pointer">
                            {{ uploadingAvatar ? (t.profile_uploading_avatar ?? 'Uploading...') : (t.profile_upload_avatar ?? 'Upload avatar') }}
                            <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" :disabled="uploadingAvatar" @change="uploadAvatar">
                        </label>
                        <button
                            type="button"
                            class="rounded-lg border border-rose-300 px-3 py-2 text-xs text-rose-700 disabled:opacity-50"
                            :disabled="uploadingAvatar || !avatarUrl"
                            @click="removeAvatar"
                        >
                            {{ t.profile_remove_avatar ?? 'Remove avatar' }}
                        </button>
                    </div>
                </div>

                <p v-if="avatarError" class="mb-2 text-sm text-rose-700">{{ avatarError }}</p>
                <p v-if="avatarSuccess" class="text-sm text-emerald-700">{{ avatarSuccess }}</p>
            </div>

            <div class="pf-card">
                <h2 class="mb-3 text-base font-semibold text-[#1e1b4b]">{{ t.profile_info_title ?? 'Profile information' }}</h2>
                <form class="space-y-3" @submit.prevent="submitProfile">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.name ?? 'Name' }}</label>
                        <input v-model="profileForm.name" type="text" required class="pf-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.profile_nickname ?? 'Nickname' }}</label>
                        <input v-model="profileForm.nickname" type="text" class="pf-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.profile_bio ?? 'Bio' }}</label>
                        <textarea v-model="profileForm.bio" rows="4" class="pf-input"></textarea>
                    </div>
                    <p v-if="profileError" class="text-sm text-rose-700">{{ profileError }}</p>
                    <p v-if="profileSuccess" class="text-sm text-emerald-700">{{ profileSuccess }}</p>
                    <button type="submit" class="pf-btn-primary !rounded-lg" :disabled="savingProfile">
                        {{ savingProfile ? (t.loading ?? 'Loading...') : (t.save ?? 'Save') }}
                    </button>
                </form>
            </div>

            <div class="pf-card lg:col-span-2">
                <h2 class="mb-3 text-base font-semibold text-[#1e1b4b]">{{ t.profile_password_title ?? 'Change password' }}</h2>
                <form class="grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="submitPassword">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.profile_current_password ?? 'Current password' }}</label>
                        <input v-model="passwordForm.current_password" type="password" required class="pf-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.profile_new_password ?? 'New password' }}</label>
                        <input v-model="passwordForm.new_password" type="password" required class="pf-input">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-[#1e1b4b]">{{ t.profile_new_password_confirmation ?? 'Confirm new password' }}</label>
                        <input v-model="passwordForm.new_password_confirmation" type="password" required class="pf-input">
                    </div>

                    <div class="md:col-span-3">
                        <p v-if="passwordError" class="text-sm text-rose-700">{{ passwordError }}</p>
                        <p v-if="passwordSuccess" class="text-sm text-emerald-700">{{ passwordSuccess }}</p>
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit" class="pf-btn-primary !rounded-lg" :disabled="savingPassword">
                            {{ savingPassword ? (t.loading ?? 'Loading...') : (t.profile_change_password ?? 'Change password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
