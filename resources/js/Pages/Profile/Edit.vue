<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AccountSettingsForm from './Partials/AccountSettingsForm.vue';
import { Head } from '@inertiajs/vue3';
import IdentityCard from '@/Components/Profile/IdentityCard.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-400">Pengaturan</span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                    Profil & Keamanan Akun
                </h1>
            </div>
        </template>

        <div class="space-y-6">
        <div class="max-w-7xl space-y-6">
        <IdentityCard />

        <div
    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-2xs sm:rounded-xl sm:p-8"
>
    <AccountSettingsForm
        :must-verify-email="mustVerifyEmail"
        :status="status"
    />
</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
