<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Button } from '@/Components/ui/button';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { UserCog, Mail, Phone, KeyRound } from 'lucide-vue-next';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const profileForm = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isChangingPassword = computed(() => {
    return !!(passwordForm.current_password || passwordForm.password || passwordForm.password_confirmation);
});

const isSaving = computed(() => profileForm.processing || passwordForm.processing);
const savedSuccessfully = ref(false);

function submitAll() {
    savedSuccessfully.value = false;

    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            if (!isChangingPassword.value) {
                savedSuccessfully.value = true;
                return;
            }

            passwordForm.put(route('password.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    passwordForm.reset();
                    savedSuccessfully.value = true;
                },
                onError: () => {
                    if (passwordForm.errors.password) {
                        passwordForm.reset('password', 'password_confirmation');
                        passwordInput.value?.focus();
                    }
                    if (passwordForm.errors.current_password) {
                        passwordForm.reset('current_password');
                        currentPasswordInput.value?.focus();
                    }
                },
            });
        },
    });
}
</script>

<template>
    <section>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- KOLOM KIRI: INFORMASI PROFIL -->
            <div>
                <header class="flex items-start gap-3">
                    <div class="shrink-0 h-9 w-9 rounded-lg bg-[#2563EB]/10 dark:bg-blue-500/10 flex items-center justify-center">
                        <UserCog class="w-4.5 h-4.5 text-[#2563EB] dark:text-blue-400" />
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                            Informasi Profil
                        </h2>
                        <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Perbarui nama, email, dan nomor telepon Anda.
                        </p>
                    </div>
                </header>

                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1.5 block w-full text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs"
                            v-model="profileForm.name"
                            required
                            autocomplete="name"
                        />
                        <InputError class="mt-1.5" :message="profileForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Alamat Email" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <div class="relative mt-1.5">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full pl-9 text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs"
                                v-model="profileForm.email"
                                required
                                autocomplete="username"
                            />
                        </div>
                        <InputError class="mt-1.5" :message="profileForm.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="phone" value="Nomor Telepon" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <div class="relative mt-1.5">
                            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                            <TextInput
                                id="phone"
                                type="text"
                                class="block w-full pl-9 text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs"
                                v-model="profileForm.phone"
                                placeholder="Contoh: 081234567890"
                                autocomplete="tel"
                            />
                        </div>
                        <InputError class="mt-1.5" :message="profileForm.errors.phone" />
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
                </div>
            </div>

            <!-- KOLOM KANAN: PEMBARUAN KATA SANDI -->
            <div class="lg:border-l lg:border-slate-100 dark:lg:border-slate-800 lg:pl-8">
                <header class="flex items-start gap-3">
                    <div class="shrink-0 h-9 w-9 rounded-lg bg-[#2563EB]/10 dark:bg-blue-500/10 flex items-center justify-center">
                        <KeyRound class="w-4.5 h-4.5 text-[#2563EB] dark:text-blue-400" />
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                            Pembaruan Kata Sandi
                        </h2>
                        <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Kosongkan jika tidak ingin mengganti kata sandi.
                        </p>
                    </div>
                </header>

                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="current_password" value="Kata Sandi Saat Ini" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <div class="relative mt-1.5">
                            <TextInput
                                id="current_password"
                                ref="currentPasswordInput"
                                v-model="passwordForm.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                class="block w-full text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs pr-10"
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showCurrentPassword = !showCurrentPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none z-10"
                            >
                                <svg v-if="showCurrentPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1.5" :message="passwordForm.errors.current_password" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Kata Sandi Baru" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <div class="relative mt-1.5">
                            <TextInput
                                id="password"
                                ref="passwordInput"
                                v-model="passwordForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs pr-10"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none z-10"
                            >
                                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1.5" :message="passwordForm.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm" />
                        <div class="relative mt-1.5">
                            <TextInput
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                class="block w-full text-xs sm:text-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs pr-10"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none z-10"
                            >
                                <svg v-if="showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1.5" :message="passwordForm.errors.password_confirmation" />
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL SIMPAN TUNGGAL -->
        <div class="flex items-center justify-end gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-if="savedSuccessfully" class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Tersimpan.
                </p>
            </Transition>

            <Button
                type="button"
                :disabled="isSaving"
                class="bg-[#2563EB] hover:bg-blue-700 text-white font-medium shadow-sm transition-all text-xs h-9 px-4 cursor-pointer"
                @click="submitAll"
            >
                {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </Button>
        </div>
    </section>
</template>