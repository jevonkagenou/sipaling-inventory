<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Boxes,
    Eye,
    EyeOff,
    Sun,
    Moon,
    CheckCircle2,
    Shield,
    Briefcase,
    FileSearch,
    Sparkles,
    Check,
} from 'lucide-vue-next';
import AuthErrorToast from '@/Components/Auth/AuthErrorToast.vue';
defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

// Theme State (persisted in localStorage, defaults to Light)
const isDark = ref(false);

function toggleTheme() {
    isDark.value = !isDark.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('sipaling-theme', isDark.value ? 'dark' : 'light');
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}

onMounted(() => {
    const savedTheme = localStorage.getItem('sipaling-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    } else {
        isDark.value = false;
    }
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
const errorToast = ref(null);
let toastCounter = 0;

watch(() => form.errors.email, (newError) => {
    if (newError && newError.includes('non-aktif')) {
        toastCounter++;
        errorToast.value = { id: toastCounter, message: newError };
        setTimeout(() => {
            if (errorToast.value?.id === toastCounter) {
                errorToast.value = null;
            }
        }, 5000);
    }
});

function closeErrorToast() {
    errorToast.value = null;
}
const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const selectedRoleName = ref('');

const demoAccounts = [
    {
        role: 'komisaris',
        name: 'Komisaris',
        email: 'komisaris@sipaling.com',
        password: 'password',
        icon: Shield,
        badgeCls: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60',
    },
    {
        role: 'manajer-operasional',
        name: 'Manajer Ops',
        email: 'manajer@sipaling.com',
        password: 'password',
        icon: Briefcase,
        badgeCls: 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800/60',
    },
    {
        role: 'staf-gudang',
        name: 'Staf Gudang',
        email: 'staf@sipaling.com',
        password: 'password',
        icon: Boxes,
        badgeCls: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800/60',
    },
    {
        role: 'auditor-internal',
        name: 'Auditor Internal',
        email: 'sultansyarif630@gmail.com',
        password: 'password',
        icon: FileSearch,
        badgeCls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
    },
];

function fillDemoAccount(acc, autoSubmit = false) {
    form.email = acc.email;
    form.password = acc.password;
    form.clearErrors();
    selectedRoleName.value = acc.name;
    if (autoSubmit) {
        submit();
    }
}
</script>

<template>
    <Head title="Masuk" />

    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-[#0F172A] text-slate-900 dark:text-slate-100 transition-colors duration-200">
        
        <!-- Namecard Container -->
        <div class="relative w-full max-w-[430px] pt-12">
            
            <!-- Circular Medallion Logo at Top (Blends with Light/Dark Theme) -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 z-20">
                <div class="h-20 w-20 rounded-full border-4 border-zinc-50 dark:border-[#0F172A] bg-white dark:bg-slate-900 shadow-xl flex items-center justify-center p-3 transition-transform hover:scale-105">
                    <img src="/logo-sipaling-transparent.png" alt="SIPALING Logo" class="h-12 w-12 object-contain select-none" />
                </div>
            </div>

            <!-- Main Namecard Body (Shadcn Card) -->
            <Card class="relative z-10 rounded-2xl border border-zinc-200/90 dark:border-slate-800 bg-white dark:bg-slate-900/95 shadow-xl shadow-slate-950/5 dark:shadow-black/60 pt-16 pb-7 px-6 sm:px-8 gap-0">
                
                <!-- Formal Header with Plus Jakarta Sans -->
                <div class="text-center space-y-1 mb-6">
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white font-sans">
                        Masuk ke SIPALING
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Sistem Inventaris & Audit Log Terintegrasi
                    </p>
                </div>

                <!-- Flash Status Alert if Available (Tertiary Emerald #10B981) -->
                <div
                    v-if="status"
                    class="mb-4 p-2.5 rounded-lg border border-emerald-500/20 bg-emerald-500/10 text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-2"
                >
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-500" />
                    <span>{{ status }}</span>
                </div>

                <CardContent class="p-0">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email Input -->
                        <div class="space-y-1.5">
                            <Label for="email" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Email Operasional
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="nama@perusahaan.com"
                                required
                                autofocus
                                autocomplete="username"
                                class="h-10 text-sm bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus-visible:ring-2 focus-visible:ring-[#2563EB]/15 focus-visible:border-[#2563EB] rounded-xl"
                                :class="{ 'border-rose-500 focus-visible:ring-rose-500/20': form.errors.email }"
                            />
                            <p v-if="form.errors.email && !form.errors.email.includes('non-aktif')" class="text-[11px] text-rose-500 font-medium">
                                    {{ form.errors.email }}
                                </p>
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Kata Sandi
                                </Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs text-slate-500 dark:text-slate-400 hover:text-[#2563EB] dark:hover:text-[#4F46E5] hover:underline transition-colors"
                                >
                                    Lupa kata sandi?
                                </Link>
                            </div>
                            <div class="relative">
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="h-10 pr-10 text-sm bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus-visible:ring-2 focus-visible:ring-[#2563EB]/15 focus-visible:border-[#2563EB] rounded-xl"
                                    :class="{ 'border-rose-500 focus-visible:ring-rose-500/20': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showPassword" class="w-3.5 h-3.5" />
                                    <Eye v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-[11px] text-red-500 font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Remember Device -->
                        <div class="flex items-center gap-2 pt-0.5">
                            <input
                                type="checkbox"
                                id="remember"
                                v-model="form.remember"
                                class="h-3.5 w-3.5 rounded border-slate-300 dark:border-slate-700 text-[#2563EB] focus:ring-[#2563EB] focus:ring-offset-0 cursor-pointer"
                            />
                            <Label
                                for="remember"
                                class="text-xs text-slate-600 dark:text-slate-400 font-normal cursor-pointer select-none"
                            >
                                Ingat saya
                            </Label>
                        </div>

                        <!-- Button Masuk (Stitch AI Primary #2563EB with robust height h-11) -->
                        <Button
                            type="submit"
                            class="w-full h-11 text-sm font-semibold rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white shadow-sm transition-colors mt-2 disabled:opacity-50 cursor-pointer"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                <span>Memproses...</span>
                            </span>
                            <span v-else>
                                Masuk
                            </span>
                        </Button>
                    </form>

                    <!-- Quick Demo / Seeder Accounts Panel -->
                    <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Sparkles class="w-3.5 h-3.5 text-[#2563EB]" />
                                Akun Pengujian (Seeder)
                            </span>
                            <span v-if="selectedRoleName" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <Check class="w-3 h-3" /> {{ selectedRoleName }} Terisi
                            </span>
                            <span v-else class="text-[10px] text-slate-400">
                                Klik untuk isi otomatis
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="acc in demoAccounts"
                                :key="acc.role"
                                type="button"
                                @click="fillDemoAccount(acc)"
                                class="p-2 sm:p-2.5 rounded-xl border text-left transition-all cursor-pointer group hover:-translate-y-0.5 hover:shadow-2xs relative overflow-hidden"
                                :class="form.email === acc.email
                                    ? 'border-[#2563EB] bg-blue-50/60 dark:bg-blue-950/40 ring-1 ring-[#2563EB]/40'
                                    : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 hover:border-slate-300 dark:hover:border-slate-700'"
                                :title="`Klik untuk isi otomatis akun ${acc.name}`"
                            >
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div
                                            class="h-6 w-6 rounded-lg flex items-center justify-center shrink-0 border text-xs"
                                            :class="acc.badgeCls"
                                        >
                                            <component :is="acc.icon" class="w-3.5 h-3.5" />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-xs text-slate-900 dark:text-white truncate block">
                                                {{ acc.name }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 truncate block font-mono">
                                                {{ acc.email.split('@')[0] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="form.email === acc.email" class="h-4 w-4 rounded-full bg-[#2563EB] text-white flex items-center justify-center shrink-0">
                                        <Check class="w-2.5 h-2.5" />
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Navigasi Bersih ke Beranda -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                        <Link
                            href="/"
                            class="text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                        >
                            &larr; Kembali ke Beranda
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Floating Theme Toggle (Bottom Right Corner) -->
        <div class="fixed bottom-6 right-6 z-50">
            <button
                type="button"
                @click="toggleTheme"
                :title="isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
                class="h-9 w-9 rounded-full border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white shadow-sm flex items-center justify-center transition-colors cursor-pointer"
            >
                <Sun v-if="isDark" class="h-4 w-4 text-amber-400" />
                <Moon v-else class="h-4 w-4 text-slate-400" />
            </button>
        </div>
        <AuthErrorToast :toast="errorToast" @close="closeErrorToast" />
    </div>
</template>
