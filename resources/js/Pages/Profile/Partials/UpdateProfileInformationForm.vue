<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

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
        <header>
            <h2 class="text-lg font-bold text-[#0F172A]">
                Informasi Profil
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Perbarui informasi profil akun, alamat email, dan nomor telepon Anda.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="text-[#0F172A] font-medium" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full border-slate-300 focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" class="text-[#0F172A] font-medium" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-slate-300 focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="phone" value="Nomor Telepon" class="text-[#0F172A] font-medium" />

                <TextInput
                    id="phone"
                    type="text"
                    class="mt-1 block w-full border-slate-300 focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                    v-model="form.phone"
                    placeholder="Contoh: 081234567890"
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-[#0F172A]">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-[#2563EB] underline hover:text-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-[#2563EB]"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-[#10B981]"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton 
                    :disabled="form.processing"
                    class="bg-[#2563EB] hover:bg-[#1D4ED8] active:bg-[#1E40AF] focus:ring-[#2563EB] font-medium rounded-lg"
                >
                    Simpan
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-slate-600"
                    >
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>