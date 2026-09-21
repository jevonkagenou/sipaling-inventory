<script setup>
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Button } from '@/Components/ui/button'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Package2, ShieldCheck, TrendingUp } from 'lucide-vue-next'

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.processing = true;
    setTimeout(() => {
        router.get(route('inventory.index'));
    }, 800);
};
</script>

<template>

    <Head title="Login - SIPALING" />

    <div class="w-full lg:grid lg:grid-cols-2 min-h-screen">
        <!-- Bagian Kiri: Form Login -->
        <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="mx-auto w-full max-w-[400px] space-y-8">
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <div class="bg-zinc-900 p-3 rounded-xl shadow-lg">
                            <Package2 class="w-8 h-8 text-white" />
                        </div>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Masuk ke SIPALING</h1>
                    <p class="mt-2 text-sm text-zinc-500">
                        Sistem Inventaris Prediktif & Audit Log Terintegrasi
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="email">Email Operasional</Label>
                            <Input id="email" type="email" v-model="form.email" placeholder="nama@perusahaan.com"
                                required autofocus
                                :class="{ 'border-red-500 focus-visible:ring-red-500': form.errors.email }" />
                            <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">Kata Sandi</Label>
                            </div>
                            <Input id="password" type="password" v-model="form.password" required
                                :class="{ 'border-red-500 focus-visible:ring-red-500': form.errors.password }" />
                            <span v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password
                            }}</span>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="remember" v-model="form.remember"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900" />
                            <Label for="remember" class="font-normal text-zinc-600 cursor-pointer">Ingat sesi
                                saya</Label>
                        </div>
                    </div>

                    <Button type="submit"
                        class="w-full h-11 text-base bg-zinc-900 text-white transition-all duration-300 ease-out hover:scale-[1.02] hover:shadow-lg hover:shadow-zinc-900/20 hover:bg-zinc-800 hover:text-white active:scale-95"
                        :disabled="form.processing">
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <span
                                class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                            Mengautentikasi...
                        </span>
                        <span v-else class="transition-transform duration-300">Masuk ke Dasbor</span>
                    </Button>
                </form>
            </div>
        </div>

        <!-- Bagian Kanan: Branding & Proposisi Nilai -->
        <div class="hidden bg-zinc-900 lg:flex flex-col justify-between p-12 text-white border-l border-zinc-800">
            <div class="flex items-center gap-2 font-semibold text-lg">
                <Package2 class="w-6 h-6" />
                <span>SIPALING Enterprise</span>
            </div>

            <div class="space-y-6 max-w-lg">
                <blockquote class="space-y-2">
                    <p class="text-2xl font-medium leading-relaxed">
                        "Mengubah manajemen inventaris dari reaktif menjadi proaktif. Melindungi aset bisnis dengan
                        rekam jejak yang absolut."
                    </p>
                    <footer class="text-sm text-zinc-400 pt-4">Dikembangkan untuk PBL Kelompok 1 - SIB 3C</footer>
                </blockquote>

                <div class="grid grid-cols-2 gap-4 border-t border-zinc-800 pt-8 mt-8">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-zinc-800 rounded-lg shadow-inner">
                            <TrendingUp class="w-6 h-6 text-zinc-300" />
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-zinc-200">Analitik Prediktif</p>
                            <p class="text-zinc-500">Forecasting DES</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-zinc-800 rounded-lg shadow-inner">
                            <ShieldCheck class="w-6 h-6 text-zinc-300" />
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-zinc-200">Audit Trail</p>
                            <p class="text-zinc-500">Keamanan Log Immutable</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
