<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { Button } from '@/Components/ui/button';
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
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                Hapus Akun
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.
            </p>
        </header>

        <div class="flex justify-end">
            <Button
                variant="destructive"
                @click="confirmUserDeletion"
                class="font-medium shadow-sm transition-all text-xs h-9 px-3.5 cursor-pointer"
            >
                Hapus Akun
            </Button>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    Apakah Anda yakin ingin menghapus akun Anda?
                </h2>

                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
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
                        class="mt-1 block w-3/4 border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/15 rounded-xl shadow-2xs text-xs sm:text-sm"
                        placeholder="Kata Sandi"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button
                        variant="outline"
                        @click="closeModal"
                        class="text-xs h-9 px-3.5 rounded-xl cursor-pointer"
                    >
                        Batal
                    </Button>

                    <Button
                        variant="destructive"
                        :disabled="form.processing"
                        :class="{ 'opacity-25': form.processing }"
                        class="text-xs h-9 px-3.5 cursor-pointer"
                        @click="deleteUser"
                    >
                        Hapus Akun
                    </Button>
                </div>
            </div>
        </Modal>
    </section>
</template>