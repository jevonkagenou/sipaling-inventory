<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    status: String,
});

const form = useForm({
    email: props.email,
    otp: '',
});

const submit = () => {
    form.post(route('password.verify.store'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi OTP" />

        <div class="mb-4 text-sm text-gray-600">
            Masukkan 6-digit kode OTP yang telah dikirimkan ke email Anda ({{ email }}).
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="otp" value="Kode OTP" />

                <TextInput
                    id="otp"
                    type="text"
                    class="mt-1 block w-full tracking-widest text-center"
                    v-model="form.otp"
                    required
                    autofocus
                    maxlength="6"
                    placeholder="123456"
                />

                <InputError class="mt-2" :message="form.errors.otp" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Verifikasi OTP
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
