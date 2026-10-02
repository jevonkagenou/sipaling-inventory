<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Button } from '@/Components/ui/button';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { UserCog, Mail, Phone } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone ?? '',
});
</script>

<template>
    <section>
        <header class="flex items-start gap-3">
            <div class="shrink-0 h-9 w-9 rounded-lg bg-[#2563EB]/10 dark:bg-blue-500/10 flex items-center justify-center">
                <UserCog class="w-4.5 h-4.5 text-[#2563EB] dark:text-blue-400" />
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                    Informasi Profil
                </h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Perbarui informasi profil akun, alamat email, dan nomor telepon Anda.
                </p>
            </div>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-5"
        >
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm mt" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1.5 block w-full text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class ms-12>
                    <InputLabel for="email" value="Alamat Email" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />

                    <div class="relative mt-1.5">
                        <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full pl-9 text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                            v-model="form.email"
                            required
                            autocomplete="username"
                        />
                    </div>

                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="phone" value="Nomor Telepon" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />

                    <div class="relative mt-1.5">
                        <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                        <TextInput
                            id="phone"
                            type="text"
                            class="block w-full pl-9 text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                            v-model="form.phone"
                            placeholder="Contoh: 081234567890"
                            autocomplete="tel"
                        />
                    </div>

                    <InputError class="mt-1.5" :message="form.errors.phone" />
                </div>
            </div>

            <div
                v-if="mustVerifyEmail && user.email_verified_at === null"
                class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 px-4 py-3"
            >
                <p class="text-xs sm:text-sm text-amber-800 dark:text-amber-300">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md font-medium underline hover:text-amber-900 dark:hover:text-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs sm:text-sm font-medium text-[#10B981] dark:text-emerald-400"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

                        <div class="flex items-center justify-end gap-4 pt-1">
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs sm:text-sm text-slate-500 dark:text-slate-400"
                    >
                        Tersimpan.
                    </p>
                </Transition>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-[#2563EB] hover:bg-blue-700 text-white font-medium shadow-sm transition-all text-xs h-9 px-3.5 cursor-pointer"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </section>
</template>