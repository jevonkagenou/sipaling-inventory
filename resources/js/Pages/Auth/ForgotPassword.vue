<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    PinInput,
    PinInputGroup,
    PinInputInput,
    PinInputSeparator,
} from '@/Components/ui/pin-input';
import {
    ShieldCheck,
    Mail,
    KeyRound,
    Lock,
    Eye,
    EyeOff,
    CheckCircle2,
    ArrowRight,
    ArrowLeft,
    RefreshCw,
    Sun,
    Moon,
    Info,
    Check,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

// Step state: 1 = Email, 2 = 2-Step Verification (OTP), 3 = New Password, 4 = Success
const currentStep = ref(1);

// Interactive state for 2-step verification & reset
const userEmail = ref('');
const emailError = ref('');
const otpCode = ref(['', '', '', '', '', '']);
const otpError = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isVerifyingOtp = ref(false);
const isSubmittingPassword = ref(false);

// Resend OTP countdown timer
const resendTimer = ref(45);
let timerInterval = null;

function startResendTimer() {
    resendTimer.value = 45;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (resendTimer.value > 0) {
            resendTimer.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
}

function handleResendOtp() {
    if (resendTimer.value > 0) return;
    otpCode.value = ['', '', '', '', '', ''];
    otpError.value = '';
    startResendTimer();
    showToastNotification('Kode OTP 2FA baru telah dikirimkan ke email Anda.');
}

// Toast notification for simulated feedback
const toastMessage = ref('');
const showToast = ref(false);
let toastTimeout = null;

function showToastNotification(msg) {
    toastMessage.value = msg;
    showToast.value = true;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        showToast.value = false;
    }, 3500);
}

// Step 1: Proceed to OTP
function proceedToOtp() {
    emailError.value = '';
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!userEmail.value.trim()) {
        emailError.value = 'Email operasional wajib diisi.';
        return;
    }
    if (!emailRegex.test(userEmail.value.trim())) {
        emailError.value = 'Format alamat email tidak valid.';
        return;
    }

    currentStep.value = 2;
    startResendTimer();
}

// Step 2: Verify OTP
function verifyOtp() {
    const fullCode = otpCode.value.join('');
    if (fullCode.length < 6) {
        otpError.value = 'Harap masukkan 6 digit kode keamanan dengan lengkap.';
        return;
    }
    otpError.value = '';
    isVerifyingOtp.value = true;

    // Simulate verification delay
    setTimeout(() => {
        isVerifyingOtp.value = false;
        currentStep.value = 3;
    }, 600);
}

// Step 3: Password Strength calculation
const passwordCriteria = computed(() => {
    const pwd = newPassword.value;
    return {
        minLength: pwd.length >= 8,
        hasUpperLower: /[a-z]/.test(pwd) && /[A-Z]/.test(pwd),
        hasNumberOrSpecial: /[0-9]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd),
    };
});

const passwordStrengthScore = computed(() => {
    if (!newPassword.value) return 0;
    let score = 0;
    if (passwordCriteria.value.minLength) score += 1;
    if (passwordCriteria.value.hasUpperLower) score += 1;
    if (passwordCriteria.value.hasNumberOrSpecial) score += 1;
    if (newPassword.value.length >= 12) score += 1;
    return score;
});

const passwordStrengthLabel = computed(() => {
    switch (passwordStrengthScore.value) {
        case 0:
        case 1:
            return { text: 'Lemah', color: 'text-red-500', bg: 'bg-red-500', width: 'w-1/4' };
        case 2:
            return { text: 'Cukup', color: 'text-amber-500', bg: 'bg-amber-500', width: 'w-2/4' };
        case 3:
            return { text: 'Kuat', color: 'text-blue-500', bg: 'bg-[#2563EB]', width: 'w-3/4' };
        case 4:
            return { text: 'Sangat Kuat', color: 'text-emerald-500', bg: 'bg-[#10B981]', width: 'w-full' };
        default:
            return { text: 'Lemah', color: 'text-slate-400', bg: 'bg-slate-300', width: 'w-0' };
    }
});

const isPasswordMatch = computed(() => {
    if (!confirmPassword.value) return null;
    return newPassword.value === confirmPassword.value;
});

const passwordError = ref('');

// Step 3: Submit New Password
function submitNewPassword() {
    passwordError.value = '';
    if (!passwordCriteria.value.minLength) {
        passwordError.value = 'Kata sandi minimal 8 karakter.';
        return;
    }
    if (newPassword.value !== confirmPassword.value) {
        passwordError.value = 'Konfirmasi kata sandi tidak cocok.';
        return;
    }

    isSubmittingPassword.value = true;
    setTimeout(() => {
        isSubmittingPassword.value = false;
        currentStep.value = 4;
    }, 700);
}

// Reset flow for demo testing
function restartDemo() {
    currentStep.value = 1;
    otpCode.value = ['', '', '', '', '', ''];
    newPassword.value = '';
    confirmPassword.value = '';
    emailError.value = '';
    otpError.value = '';
    passwordError.value = '';
}

// Dark / Light Theme Toggle (Synchronized with Login.vue & App)
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

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (toastTimeout) clearTimeout(toastTimeout);
});
</script>

<template>
    <Head title="Pemulihan Akun & Lupa Kata Sandi" />

    <div class="min-h-screen w-full flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-[#0F172A] text-slate-900 dark:text-slate-100 transition-colors duration-200 selection:bg-blue-500/20">
        
        <!-- Toast Notification Alert -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showToast"
                class="fixed top-5 right-5 z-50 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-white/95 dark:bg-slate-900/95 px-4 py-3 text-xs text-slate-800 dark:text-slate-200 shadow-xl backdrop-blur-md"
            >
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/10 text-[#10B981]">
                    <CheckCircle2 class="h-4 w-4" />
                </div>
                <span class="font-medium">{{ toastMessage }}</span>
            </div>
        </transition>

        <!-- Main Card Container -->
        <div class="relative w-full max-w-[460px] pt-12">
            
            <!-- Circular Medallion Logo at Top (Synchronous with Login.vue) -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 z-20">
                <Link href="/" title="Kembali ke Beranda SIPALING">
                    <div class="h-20 w-20 rounded-full border-4 border-zinc-50 dark:border-[#0F172A] bg-white dark:bg-slate-900 shadow-xl flex items-center justify-center p-3 transition-transform hover:scale-105 cursor-pointer">
                        <img src="/logo-sipaling-transparent.png" alt="SIPALING Logo" class="h-12 w-12 object-contain select-none" />
                    </div>
                </Link>
            </div>

            <!-- Shadcn Card Surface -->
            <Card class="relative z-10 rounded-2xl border border-zinc-200/90 dark:border-slate-800 bg-white dark:bg-slate-900/95 shadow-xl shadow-slate-950/5 dark:shadow-black/60 pt-14 pb-7 px-6 sm:px-8 gap-0">
                
                <!-- Header with Badge (matching image requested) -->
                <div class="text-center space-y-2 mb-6">
                    <div class="flex items-center justify-center">
                        <Badge variant="outline" class="border-[#2563EB]/30 bg-blue-50/50 dark:bg-blue-950/30 text-[#2563EB] dark:text-blue-400 font-semibold gap-1 text-[11px] py-0.5 px-2.5 rounded-full">
                            <ShieldCheck class="w-3.5 h-3.5 text-[#2563EB]" />
                            <span>Pemulihan Kata Sandi</span>
                        </Badge>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Sistem Inventaris Prediktif & Audit Log Terintegrasi
                    </p>
                </div>

                <!-- Step Progress Stepper (Clickable for presentation/demo) -->
                <div v-if="currentStep < 4" class="mb-6 pt-1">
                    <div class="flex items-center justify-between relative">
                        <!-- Connecting Line Track -->
                        <div class="absolute top-1/2 left-0 right-0 -translate-y-1/2 h-0.5 bg-slate-200 dark:bg-slate-800 -z-0"></div>
                        <!-- Connecting Active Bar -->
                        <div
                            class="absolute top-1/2 left-0 -translate-y-1/2 h-0.5 bg-gradient-to-r from-[#2563EB] to-[#4F46E5] transition-all duration-300 -z-0"
                            :style="{ width: currentStep === 1 ? '15%' : currentStep === 2 ? '50%' : '100%' }"
                        ></div>

                        <!-- Step 1 Button/Pill -->
                        <button
                            type="button"
                            @click="currentStep = 1"
                            class="relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none"
                            title="Langkah 1: Identifikasi Akun"
                        >
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-all shadow-sm"
                                :class="[
                                    currentStep >= 1
                                        ? 'bg-[#2563EB] text-white ring-4 ring-blue-100 dark:ring-blue-950/50'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-300 dark:border-slate-700'
                                ]"
                            >
                                <Check v-if="currentStep > 1" class="w-4 h-4" />
                                <span v-else>1</span>
                            </div>
                            <span
                                class="text-[11px] font-medium mt-1 transition-colors"
                                :class="currentStep === 1 ? 'text-[#2563EB] dark:text-blue-400 font-semibold' : 'text-slate-400'"
                            >
                                Email
                            </span>
                        </button>

                        <!-- Step 2 Button/Pill (2-Step Verification) -->
                        <button
                            type="button"
                            @click="currentStep = 2"
                            class="relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none"
                            title="Langkah 2: Verifikasi 2 Langkah (OTP)"
                        >
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-all shadow-sm"
                                :class="[
                                    currentStep >= 2
                                        ? 'bg-[#2563EB] text-white ring-4 ring-blue-100 dark:ring-blue-950/50'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-300 dark:border-slate-700'
                                ]"
                            >
                                <Check v-if="currentStep > 2" class="w-4 h-4" />
                                <span v-else>2</span>
                            </div>
                            <span
                                class="text-[11px] font-medium mt-1 transition-colors"
                                :class="currentStep === 2 ? 'text-[#2563EB] dark:text-blue-400 font-semibold' : 'text-slate-400'"
                            >
                                2FA (OTP)
                            </span>
                        </button>

                        <!-- Step 3 Button/Pill -->
                        <button
                            type="button"
                            @click="currentStep = 3"
                            class="relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none"
                            title="Langkah 3: Kata Sandi Baru"
                        >
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-all shadow-sm"
                                :class="[
                                    currentStep === 3
                                        ? 'bg-[#2563EB] text-white ring-4 ring-blue-100 dark:ring-blue-950/50'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-300 dark:border-slate-700'
                                ]"
                            >
                                <span>3</span>
                            </div>
                            <span
                                class="text-[11px] font-medium mt-1 transition-colors"
                                :class="currentStep === 3 ? 'text-[#2563EB] dark:text-blue-400 font-semibold' : 'text-slate-400'"
                            >
                                Sandi Baru
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Flash Alert from Backend if present -->
                <div
                    v-if="status"
                    class="mb-4 p-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-2"
                >
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-[#10B981]" />
                    <span>{{ status }}</span>
                </div>

                <CardContent class="p-0">
                    <!-- ============================================== -->
                    <!-- LANGKAH 1: IDENTIFIKASI & INPUT EMAIL OPERASIONAL -->
                    <!-- ============================================== -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 p-3.5 text-xs text-slate-600 dark:text-slate-300 flex items-start gap-2.5">
                            <Info class="w-4 h-4 text-[#2563EB] shrink-0 mt-0.5" />
                            <p class="leading-relaxed">
                                Masukkan email operasional akun Anda. Kami akan mengirimkan 6 digit kode keamanan untuk verifikasi 2 langkah.
                            </p>
                        </div>

                        <!-- Email Input Field -->
                        <div class="space-y-1.5">
                            <Label for="email" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Email Operasional Terdaftar
                            </Label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Mail class="w-4 h-4" />
                                </div>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="userEmail"
                                    placeholder="nama@perusahaan.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="h-10 pl-9 text-sm bg-transparent border-slate-300 dark:border-slate-700 focus-visible:ring-1 focus-visible:ring-[#2563EB] focus-visible:border-[#2563EB] rounded-lg"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': emailError }"
                                    @keydown.enter.prevent="proceedToOtp"
                                />
                            </div>
                            <p v-if="emailError" class="text-[11px] text-red-500 font-medium flex items-center gap-1">
                                <AlertCircle class="w-3 h-3" />
                                <span>{{ emailError }}</span>
                            </p>
                        </div>

                        <!-- Action Button: Lanjut ke OTP -->
                        <Button
                            type="button"
                            @click="proceedToOtp"
                            class="w-full h-11 text-sm font-semibold rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white shadow-sm transition-colors mt-1 cursor-pointer flex items-center justify-center gap-2"
                        >
                            <span>Lanjut ke Verifikasi (OTP)</span>
                            <ArrowRight class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- ============================================== -->
                    <!-- LANGKAH 2: VERIFIKASI 2-LANGKAH (2FA OTP)       -->
                    <!-- ============================================== -->
                    <div v-else-if="currentStep === 2" class="space-y-4">
                        <div class="text-center space-y-1">
                            <div class="inline-flex p-3 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#2563EB] dark:text-blue-400 mb-1 border border-blue-200 dark:border-blue-900/50">
                                <ShieldCheck class="w-6 h-6" />
                            </div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Masukkan Kode Verifikasi
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                                6 digit kode keamanan 2FA telah dikirimkan ke:
                                <br />
                                <span class="font-medium text-slate-800 dark:text-slate-200 break-all">{{ userEmail || 'user@perusahaan.com' }}</span>
                            </p>
                        </div>

                        <!-- Shadcn PinInput (6 digits, split into 3-3) -->
                        <div class="flex flex-col items-center justify-center py-2">
                            <PinInput
                                id="pin-input"
                                v-model="otpCode"
                                placeholder="○"
                                type="text"
                                @complete="verifyOtp"
                            >
                                <PinInputGroup>
                                    <PinInputInput :index="0" />
                                    <PinInputInput :index="1" />
                                    <PinInputInput :index="2" />
                                </PinInputGroup>
                                <PinInputSeparator />
                                <PinInputGroup>
                                    <PinInputInput :index="3" />
                                    <PinInputInput :index="4" />
                                    <PinInputInput :index="5" />
                                </PinInputGroup>
                            </PinInput>

                            <p v-if="otpError" class="text-[11px] text-red-500 font-medium mt-2 flex items-center gap-1">
                                <AlertCircle class="w-3 h-3" />
                                <span>{{ otpError }}</span>
                            </p>
                        </div>

                        <!-- Countdown & Kirim Ulang -->
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-1 px-1">
                            <div class="flex items-center gap-1.5">
                                <span>Kirim ulang kode dalam:</span>
                                <span class="font-mono font-medium text-slate-700 dark:text-slate-300">
                                    00:{{ resendTimer < 10 ? '0' + resendTimer : resendTimer }}
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="handleResendOtp"
                                :disabled="resendTimer > 0"
                                class="font-medium text-[#2563EB] dark:text-blue-400 hover:underline disabled:opacity-40 disabled:hover:no-underline cursor-pointer disabled:cursor-not-allowed flex items-center gap-1"
                            >
                                <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': resendTimer === 45 }" />
                                <span>Kirim Ulang</span>
                            </button>
                        </div>

                        <!-- Button Verifikasi -->
                        <div class="space-y-2 pt-2">
                            <Button
                                type="button"
                                @click="verifyOtp"
                                :disabled="isVerifyingOtp"
                                class="w-full h-11 text-sm font-semibold rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2"
                            >
                                <span v-if="isVerifyingOtp" class="flex items-center gap-2">
                                    <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    <span>Memverifikasi 2FA...</span>
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <span>Verifikasi & Lanjutkan</span>
                                    <ArrowRight class="w-4 h-4" />
                                </span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                @click="currentStep = 1"
                                class="w-full h-9 text-xs font-medium rounded-lg border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                            >
                                Ubah Alamat Email
                            </Button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- LANGKAH 3: ATUR KATA SANDI BARU                 -->
                    <!-- ============================================== -->
                    <div v-else-if="currentStep === 3" class="space-y-4">
                        <div class="text-center space-y-1">
                            <div class="inline-flex p-3 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-[#10B981] mb-1 border border-emerald-200 dark:border-emerald-900/50">
                                <KeyRound class="w-6 h-6" />
                            </div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Atur Kata Sandi Baru
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Identitas 2FA terverifikasi. Masukkan kata sandi baru untuk akun Anda.
                            </p>
                        </div>

                        <!-- Input Kata Sandi Baru -->
                        <div class="space-y-1.5">
                            <Label for="new-password" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Kata Sandi Baru
                            </Label>
                            <div class="relative">
                                <Input
                                    id="new-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="newPassword"
                                    placeholder="••••••••"
                                    required
                                    class="h-10 pr-10 text-sm bg-transparent border-slate-300 dark:border-slate-700 focus-visible:ring-1 focus-visible:ring-[#2563EB] focus-visible:border-[#2563EB] rounded-lg"
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

                            <!-- Password Strength Indicator Bar -->
                            <div class="space-y-1 pt-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500">Kekuatan Sandi:</span>
                                    <span :class="passwordStrengthLabel.color" class="font-semibold">
                                        {{ passwordStrengthLabel.text }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div
                                        class="h-full transition-all duration-300 rounded-full"
                                        :class="[passwordStrengthLabel.bg, passwordStrengthLabel.width]"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Konfirmasi Kata Sandi -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="confirm-password" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Konfirmasi Kata Sandi
                                </Label>
                                <span
                                    v-if="isPasswordMatch !== null"
                                    class="text-[11px] font-semibold"
                                    :class="isPasswordMatch ? 'text-emerald-500' : 'text-red-500'"
                                >
                                    {{ isPasswordMatch ? '✓ Cocok' : '✗ Tidak Cocok' }}
                                </span>
                            </div>
                            <div class="relative">
                                <Input
                                    id="confirm-password"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="confirmPassword"
                                    placeholder="••••••••"
                                    required
                                    class="h-10 pr-10 text-sm bg-transparent border-slate-300 dark:border-slate-700 focus-visible:ring-1 focus-visible:ring-[#2563EB] focus-visible:border-[#2563EB] rounded-lg"
                                    :class="{
                                        'border-red-500 focus-visible:ring-red-500': isPasswordMatch === false,
                                        'border-emerald-500 focus-visible:ring-emerald-500': isPasswordMatch === true
                                    }"
                                    @keydown.enter.prevent="submitNewPassword"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showConfirmPassword" class="w-3.5 h-3.5" />
                                    <Eye v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <p v-if="passwordError" class="text-[11px] text-red-500 font-medium flex items-center gap-1">
                                <AlertCircle class="w-3 h-3" />
                                <span>{{ passwordError }}</span>
                            </p>
                        </div>

                        <!-- Requirements Checklist -->
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 p-3 space-y-1.5 text-[11px] text-slate-600 dark:text-slate-400">
                            <p class="font-medium text-slate-800 dark:text-slate-300">Ketentuan Keamanan Sandi:</p>
                            <div class="grid grid-cols-1 gap-1">
                                <div class="flex items-center gap-1.5" :class="passwordCriteria.minLength ? 'text-emerald-600 dark:text-emerald-400' : ''">
                                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" :class="passwordCriteria.minLength ? 'text-emerald-500' : 'text-slate-400'" />
                                    <span>Minimal 8 karakter</span>
                                </div>
                                <div class="flex items-center gap-1.5" :class="passwordCriteria.hasUpperLower ? 'text-emerald-600 dark:text-emerald-400' : ''">
                                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" :class="passwordCriteria.hasUpperLower ? 'text-emerald-500' : 'text-slate-400'" />
                                    <span>Kombinasi huruf besar (A-Z) & huruf kecil (a-z)</span>
                                </div>
                                <div class="flex items-center gap-1.5" :class="passwordCriteria.hasNumberOrSpecial ? 'text-emerald-600 dark:text-emerald-400' : ''">
                                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" :class="passwordCriteria.hasNumberOrSpecial ? 'text-emerald-500' : 'text-slate-400'" />
                                    <span>Mengandung angka (0-9) atau karakter simbol</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="space-y-2 pt-2">
                            <Button
                                type="button"
                                @click="submitNewPassword"
                                :disabled="isSubmittingPassword || !passwordCriteria.minLength || !isPasswordMatch"
                                class="w-full h-11 text-sm font-semibold rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <span v-if="isSubmittingPassword" class="flex items-center gap-2">
                                    <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    <span>Menyimpan Kata Sandi...</span>
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <span>Simpan Kata Sandi Baru</span>
                                    <ArrowRight class="w-4 h-4" />
                                </span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                @click="currentStep = 2"
                                class="w-full h-9 text-xs font-medium rounded-lg border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                            >
                                Kembali ke Verifikasi OTP
                            </Button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- LANGKAH 4: STATUS SUKSES                        -->
                    <!-- ============================================== -->
                    <div v-else-if="currentStep === 4" class="text-center space-y-4 py-2">
                        <div class="mx-auto w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/50 border-2 border-emerald-500/30 flex items-center justify-center text-[#10B981] shadow-lg shadow-emerald-500/10">
                            <CheckCircle2 class="w-9 h-9" />
                        </div>

                        <div class="space-y-1.5">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                                Kata Sandi Berhasil Diperbarui!
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                                Kredensial akun SIPALING Anda telah diamankan kembali. Log audit keamanan telah mencatat aktivitas pembaruan ini.
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 text-left text-xs space-y-1">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Akun Terverifikasi:</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ userEmail }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Status Keamanan:</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">2-Step Verified ✓</span>
                            </div>
                        </div>

                        <!-- Masuk Sekarang CTA -->
                        <div class="space-y-2 pt-2">
                            <Link :href="route('login')" class="block w-full">
                                <Button
                                    type="button"
                                    class="w-full h-11 text-sm font-semibold rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <span>Masuk ke SIPALING Sekarang</span>
                                    <ArrowRight class="w-4 h-4" />
                                </Button>
                            </Link>

                            <button
                                type="button"
                                @click="restartDemo"
                                class="text-xs text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition-colors cursor-pointer pt-1"
                            >
                                Ulangi Pratinjau Alur (Mode Demo)
                            </button>
                        </div>
                    </div>

                    <!-- Clean Navigation Back to Login / Beranda -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <Link
                            :href="route('login')"
                            class="text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors flex items-center gap-1.5"
                        >
                            <ArrowLeft class="w-3.5 h-3.5" />
                            <span>Kembali ke Halaman Masuk</span>
                        </Link>

                        <Link
                            href="/"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                        >
                            Beranda
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Floating Theme Toggle (Bottom Right Corner - Exactly matching Login.vue) -->
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
    </div>
</template>
