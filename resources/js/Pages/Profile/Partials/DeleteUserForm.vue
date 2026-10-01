<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold text-[#0F172A]">
                Hapus Akun
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.
            </p>
        </header>

        <DangerButton 
            @click="confirmUserDeletion"
            class="bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg"
        >
            Hapus Akun
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-bold text-[#0F172A]"
                >
                    Apakah Anda yakin ingin menghapus akun Anda?
                </h2>

                <p class="mt-2 text-sm text-slate-600">
                    Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Harap masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Kata Sandi"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4 border-slate-300 focus:border-[#2563EB] focus:ring-[#2563EB] rounded-lg shadow-sm"
                        placeholder="Kata Sandi"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal" class="rounded-lg">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Hapus Akun
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>