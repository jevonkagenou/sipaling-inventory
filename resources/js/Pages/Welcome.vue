<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import Aurora from "@/Components/bits/Aurora.vue";
import SpotlightCard from "@/Components/bits/SpotlightCard.vue";
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
import Stepper from "@/Components/bits/Stepper.vue";
import {
    ArrowRight,
    ArrowUp,
    Sun,
    Moon,
    Boxes,
    TrendingUp,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Lock,
    Layers,
    Users,
    Building2,
    FileCheck,
    ChevronRight,
    SlidersHorizontal,
    Check,
    X,
    Cpu,
    UserCheck,
    Activity,
    Database,
    Code2,
    FileText,
    Sparkles,
    ExternalLink
} from "lucide-vue-next";

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

// Smooth scroll to section helper
function scrollToSection(id) {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "start" });
        window.history.pushState(null, "", `#${id}`);
    }
}

// Theme Management (Light / Dark Mode)
const isDark = ref(true);

function toggleTheme() {
    isDark.value = !isDark.value;
    if (typeof window !== "undefined") {
        localStorage.setItem("sipaling-theme", isDark.value ? "dark" : "light");
        if (isDark.value) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    }
}

// Scroll to Top State & Handlers
const showScrollTop = ref(false);

function handleScroll() {
    showScrollTop.value = window.scrollY > 280;
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
}

onMounted(() => {
    const savedTheme = localStorage.getItem("sipaling-theme");
    if (savedTheme) {
        isDark.value = savedTheme === "dark";
    } else {
        isDark.value = true;
    }
    if (isDark.value) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});

// Interactive Console Stepper State
const currentConsoleStep = ref(1);
const stepperRef = ref(null);
const consoleWorkflowCompleted = ref(false);

function handleStepChange(step) {
    currentConsoleStep.value = step;
}

function handleFinalStepCompleted() {
    consoleWorkflowCompleted.value = true;
}

function resetConsoleWorkflow() {
    consoleWorkflowCompleted.value = false;
    approvalStatus.value = "pending";
    currentConsoleStep.value = 1;
    if (stepperRef.value) {
        stepperRef.value.reset();
    }
}

// Forecasting Simulation State
const alphaParam = ref(0.4);
const betaParam = ref(0.3);
const restockSubmitted = ref(false);

const projectedUnits = computed(() => Math.round(90 + alphaParam.value * 60));
const projectedNextPeriod = computed(() => Math.round(projectedUnits.value + betaParam.value * 18));

function handleRestockSubmit() {
    restockSubmitted.value = true;
    setTimeout(() => {
        restockSubmitted.value = false;
    }, 3500);
}

// Approval Workflow State
const approvalStatus = ref("pending"); // 'pending' | 'approved' | 'rejected'

function updateApproval(status) {
    approvalStatus.value = status;
}

// Audit Trail Interactive Data & Filter
const auditFilter = ref("all"); // 'all' | 'inbound' | 'outbound' | 'approval'
const selectedAuditRow = ref(null);

const auditLogs = [
    {
        id: "LOG-8821",
        time: "2026-09-22 19:45:12",
        user: "Staf Gudang",
        userRole: "Warehouse Floor",
        type: "outbound",
        typeLabel: "Barang Keluar (Outbound)",
        oldVal: "Stok: 55 Unit",
        newVal: "Stok: 45 Unit (-10)",
        ip: "192.168.1.14",
        status: "Terkunci",
        sku: "ITM-001",
        item: "Laptop ThinkPad T14 Gen 4",
        hash: "sha256:7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069"
    },
    {
        id: "LOG-8820",
        time: "2026-09-22 17:10:04",
        user: "Staf Gudang",
        userRole: "Warehouse Floor",
        type: "inbound",
        typeLabel: "Barang Masuk (Inbound)",
        oldVal: "Stok: 25 Unit",
        newVal: "Stok: 55 Unit (+30)",
        ip: "192.168.1.18",
        status: "Terkunci",
        sku: "ITM-001",
        item: "Laptop ThinkPad T14 Gen 4",
        hash: "sha256:2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae"
    },
    {
        id: "LOG-8819",
        time: "2026-09-22 14:22:50",
        user: "Komisaris",
        userRole: "Executive Authority",
        type: "approval",
        typeLabel: "Persetujuan Restock",
        oldVal: "Status: Pending",
        newVal: "Status: Approved",
        ip: "192.168.1.2",
        status: "Terkunci",
        sku: "PO-2026-089",
        item: "Pengadaan Batch #89 (120 Unit)",
        hash: "sha256:fc4888642cda11802d327d9b71a361ee7d438832a4e414edd0f1665a78cda43f"
    },
];

const filteredAuditLogs = computed(() => {
    if (auditFilter.value === "all") return auditLogs;
    return auditLogs.filter(log => log.type === auditFilter.value);
});

// Stakeholder Roles with Distinct Permission Matrix (Enterprise RBAC)
const activeRoleIndex = ref(0);
const roles = [
    {
        title: "Komisaris",
        badge: "Otoritas Eksekutif",
        badgeColor: "border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950",
        iconBg: "bg-zinc-100 dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300",
        spotlightColor: "rgba(16, 185, 129, 0.12)",
        activeOutline: "border-emerald-500/80 ring-1 ring-emerald-500/40 shadow-[0_0_20px_rgba(16,185,129,0.15)]",
        activeText: "text-emerald-600 dark:text-emerald-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold shadow-sm",
        icon: Building2,
        desc: "Memvalidasi permohonan pengadaan barang dari sudut pandang ketersediaan anggaran dan kelayakan bisnis berdasarkan data analitik sistem.",
        permissions: [
            { name: "Persetujuan Akhir Restock (PO)", allowed: true },
            { name: "Evaluasi Analitik & Grafik Prediksi", allowed: true },
            { name: "Pencatatan Fisik Harian Gudang", allowed: false },
            { name: "Modifikasi Rekam Jejak Audit", allowed: false },
        ],
        responsibilities: [
            "Mengevaluasi usulan pengadaan barang dari Manajer Operasional",
            "Memvalidasi rekomendasi kuantitas dengan grafik tren prediktif",
            "Memberikan keputusan persetujuan akhir sebelum pesanan diproses",
        ],
    },
    {
        title: "Manajer Operasional",
        badge: "Manajerial & Strategi",
        badgeColor: "border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950",
        iconBg: "bg-zinc-100 dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300",
        spotlightColor: "rgba(16, 185, 129, 0.12)",
        activeOutline: "border-emerald-500/80 ring-1 ring-emerald-500/40 shadow-[0_0_20px_rgba(16,185,129,0.15)]",
        activeText: "text-emerald-600 dark:text-emerald-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold shadow-sm",
        icon: SlidersHorizontal,
        desc: "Menganalisis pergerakan persediaan, memantau peringatan stok kritis, dan merumuskan rencana pengadaan barang secara proaktif.",
        permissions: [
            { name: "Analisis Prediksi Stok (DES)", allowed: true },
            { name: "Pengajuan Draf Tiket Restock", allowed: true },
            { name: "Monitoring Peringatan Titik Kritis", allowed: true },
            { name: "Persetujuan Anggaran Finansial", allowed: false },
        ],
        responsibilities: [
            "Memantau indikator peringatan dini stok yang mendekati batas minimum",
            "Menganalisis ketepatan estimasi kebutuhan stok dan tren pengeluaran",
            "Menyusun draf pengajuan restock berbasis kalkulasi analitik riil",
        ],
    },
    {
        title: "Staf Gudang",
        badge: "Pelaksana Lapangan",
        badgeColor: "border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950",
        iconBg: "bg-zinc-100 dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300",
        spotlightColor: "rgba(16, 185, 129, 0.12)",
        activeOutline: "border-emerald-500/80 ring-1 ring-emerald-500/40 shadow-[0_0_20px_rgba(16,185,129,0.15)]",
        activeText: "text-emerald-600 dark:text-emerald-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold shadow-sm",
        icon: Boxes,
        desc: "Mencatat transaksi mutasi barang masuk dan keluar secara disiplin dan terlindungi oleh akuntabilitas rekam jejak sistem.",
        permissions: [
            { name: "Input Barang Masuk (Inbound)", allowed: true },
            { name: "Input Barang Keluar (Outbound)", allowed: true },
            { name: "Katalog Master Barang (SKU)", allowed: true },
            { name: "Akses Laporan Audit Internal", allowed: false },
        ],
        responsibilities: [
            "Pencatatan penerimaan barang dari pemasok secara terstruktur",
            "Pencatatan pengeluaran barang dengan pembaruan stok otomatis",
            "Memastikan kode barang (SKU) tervalidasi dan bebas duplikasi",
        ],
    },
    {
        title: "Auditor Internal",
        badge: "Pengawas Kepatuhan",
        badgeColor: "border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950",
        iconBg: "bg-zinc-100 dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300",
        spotlightColor: "rgba(16, 185, 129, 0.12)",
        activeOutline: "border-emerald-500/80 ring-1 ring-emerald-500/40 shadow-[0_0_20px_rgba(16,185,129,0.15)]",
        activeText: "text-emerald-600 dark:text-emerald-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold shadow-sm",
        icon: ShieldCheck,
        desc: "Memeriksa kesesuaian data inventaris dan menginvestigasi anomali stok melalui rekam jejak permanen yang tidak dapat dimanipulasi.",
        permissions: [
            { name: "Akses Audit Trail (Read-Only)", allowed: true },
            { name: "Penelusuran IP & Timestamp", allowed: true },
            { name: "Analisis Anomali Selisih (Shrinkage)", allowed: true },
            { name: "Edit / Hapus Baris Log (Diblokir)", allowed: false },
        ],
        responsibilities: [
            "Penelusuran log perubahan data lengkap (nilai lama vs nilai baru)",
            "Pemeriksaan timestamp transaksi dan alamat IP pengguna",
            "Pemanfaatan laporan audit yang berstatus read-only secara mutlak",
        ],
    },
];

// Engineering Team (Differentiated into Lead and Pengembang)
const engineeringTeam = [
    {
        name: "Felix Alberta Jevon",
        role: "Lead",
        initials: "FAJ",
    },
    {
        name: "Sultan Syarif Ubaidillah A. N.",
        role: "Pengembang",
        initials: "SSU",
    },
    {
        name: "Alvina Nur Fadilah",
        role: "Pengembang",
        initials: "ANF",
    },
    {
        name: "Nadila Kholifatus Sa'adah",
        role: "Pengembang",
        initials: "NKS",
    },
    {
        name: "Desy Dwi Puspita",
        role: "Pengembang",
        initials: "DDP",
    },
];
</script>

<template>

    <Head title="SIPALING - Sistem Manajemen Inventaris & Audit Terintegrasi" />

    <div
        :class="{ dark: isDark }"
        class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans antialiased selection:bg-emerald-500/20 selection:text-emerald-900 dark:selection:bg-zinc-800 dark:selection:text-white transition-colors duration-200">
        <!-- FLOATING PILL TOP NAVIGATION (STICKY / FIXED OVER HERO & CONTENT) -->
        <header class="fixed top-4 sm:top-5 inset-x-0 z-50 flex justify-center px-4 pointer-events-none">
            <nav
                class="pointer-events-auto flex items-center justify-between w-full max-w-4xl px-3 sm:px-4 py-2 rounded-full border border-zinc-200/80 dark:border-zinc-800/90 bg-white/90 dark:bg-zinc-950/85 backdrop-blur-md shadow-lg shadow-zinc-950/5 dark:shadow-black/40 transition-all duration-200">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2.5 font-semibold text-zinc-900 dark:text-white pl-1 sm:pl-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 shadow-xs">
                        <Boxes class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-bold tracking-tight">SIPALING</span>
                </Link>

                <!-- Menu Links with Smooth Auto-scroll -->
                <div class="hidden md:flex items-center gap-5 lg:gap-6 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                    <a href="#fitur" @click.prevent="scrollToSection('fitur')"
                        class="hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer">Fitur</a>
                    <a href="#simulasi" @click.prevent="scrollToSection('simulasi')"
                        class="hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer">Simulasi</a>
                    <a href="#peran" @click.prevent="scrollToSection('peran')"
                        class="hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer">Peran</a>
                    <a href="#arsitektur" @click.prevent="scrollToSection('arsitektur')"
                        class="hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer">Arsitektur</a>
                    <a href="#pengembang" @click.prevent="scrollToSection('pengembang')"
                        class="hover:text-zinc-950 dark:hover:text-white transition-colors cursor-pointer">Tim</a>
                </div>

                <!-- Auth Navigation -->
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <Link :href="route('inventory.index')"
                        class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900/80 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors">
                        <Boxes class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" />
                        Katalog
                    </Link>

                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth?.user" :href="route('dashboard')"
                            class="inline-flex items-center justify-center px-4 py-1.5 text-xs font-semibold rounded-full bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 transition-colors shadow-sm">
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')"
                                class="text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white px-2.5 py-1.5 transition-colors">
                                Masuk
                            </Link>
                            <Link v-if="canRegister" :href="route('register')"
                                class="inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-semibold rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                                Daftar Akun
                            </Link>
                        </template>
                    </template>
                </div>
            </nav>
        </header>

        <!-- HERO SECTION -->
        <section class="relative min-h-screen w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-800/80 bg-white dark:bg-zinc-950 flex items-center justify-center transition-colors">
            <!-- Official Vue Bits Aurora WebGL Background (Active in Dark Mode) -->
            <div v-if="isDark" class="absolute inset-0 pointer-events-none overflow-hidden z-0">
                <Aurora :color-stops="['#171D22', '#7cff67', '#171D22']" :blend="0.5" :amplitude="1.0" :speed="0.5" />
            </div>
            <!-- Clean Enterprise Ambient Glow (Active in Light Mode) -->
            <div v-else class="absolute inset-0 pointer-events-none overflow-hidden z-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_0%,rgba(16,185,129,0.15),rgba(255,255,255,0))]" />

            <!-- Subtle Gradient Overlay to blend with dark/light page background -->
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-b from-transparent via-transparent to-zinc-50/50 dark:to-zinc-950" />

            <!-- Hero Header Content -->
            <div class="relative z-20 mx-auto max-w-5xl px-4 py-32 sm:py-36 text-center space-y-7 w-full flex flex-col items-center justify-center">

                <!-- Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-zinc-900 dark:text-white leading-tight">
                    Pengelolaan Inventaris Proaktif <br class="hidden sm:inline" />
                    dengan Rekam Jejak Audit Permanen
                </h1>

                <!-- Professional Subtitle -->
                <p class="mx-auto max-w-2xl text-sm sm:text-base text-zinc-600 dark:text-zinc-300 leading-relaxed font-sans">
                    Sistem sentralisasi persediaan berbasis peramalan DES otomatis dan rekam jejak audit mutasi permanen anti-manipulasi.
                </p>

                <!-- Professional Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 sm:gap-4 pt-2 w-full sm:w-auto">
                    <Link :href="route('login')" class="w-full sm:w-auto">
                        <Button size="lg"
                            class="group w-full sm:w-auto h-12 px-7 text-sm font-semibold rounded-full cursor-pointer transition-all duration-200 bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 shadow-md shadow-emerald-600/15 dark:shadow-emerald-500/10 border border-emerald-500/40 dark:border-emerald-400/30 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                            <span>Buka Portal Aplikasi</span>
                            <ArrowRight class="h-4 w-4 ml-2 transition-transform duration-200 group-hover:translate-x-1" />
                        </Button>
                    </Link>
                    <Link :href="route('inventory.index')" class="w-full sm:w-auto">
                        <Button size="lg" variant="outline"
                            class="group w-full sm:w-auto h-12 px-6 text-sm font-medium rounded-full cursor-pointer transition-all duration-200 border border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/90 text-zinc-800 dark:text-zinc-200 hover:text-zinc-950 dark:hover:text-white hover:border-zinc-300 dark:hover:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 shadow-sm hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                            <Boxes class="h-4 w-4 mr-2 text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-800 dark:group-hover:text-zinc-200 transition-colors" />
                            <span>Katalog Inventaris</span>
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- MAIN BODY SECTIONS (WITH SCROLL REVEAL ANIMATIONS) -->
        <main class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-28 space-y-28">

            <!-- SECTION: CORE SYSTEM PILLARS -->
            <ScrollReveal :delay="50">
                <section id="fitur" class="scroll-mt-24 space-y-8">
                    <div class="text-center max-w-2xl mx-auto space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Keunggulan Fungsional
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Kepastian Stok Tanpa Manipulasi Data
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-lg mx-auto">
                            Tiga pilar operasional: ketepatan estimasi persediaan, keabsahan transaksi, dan tata kelola bertingkat.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Card 1 -->
                        <SpotlightCard spotlightColor="rgba(16, 185, 129, 0.12)"
                            class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <TrendingUp class="h-5 w-5" />
                                    </div>
                                    <span
                                        class="text-xs font-sans font-medium px-2.5 py-0.5 rounded-full border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950">
                                        Akurasi 93.18%
                                    </span>
                                </div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white tracking-tight">Peramalan Proaktif (DES)</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Kalkulasi Double Exponential Smoothing otomatis mengestimasi volume restock optimal sebelum persediaan menyentuh batas kritis.
                                </p>
                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400">
                                    Responsif terhadap fluktuasi histori permintaan
                                </div>
                            </div>
                        </SpotlightCard>

                        <!-- Card 2 -->
                        <SpotlightCard spotlightColor="rgba(16, 185, 129, 0.12)"
                            class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <ShieldCheck class="h-5 w-5" />
                                    </div>
                                    <span
                                        class="text-xs font-sans font-medium px-2.5 py-0.5 rounded-full border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950">
                                        SHA-256 Ledger
                                    </span>
                                </div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white tracking-tight">Audit Trail Terintegrasi</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Pencatatan mutasi otomatis pada tingkat sistem yang mengunci identitas staf, waktu transaksi, IP, dan rekonsiliasi permanen.
                                </p>
                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400">
                                    Akses terspesialisasi Auditor Internal (Read-Only)
                                </div>
                            </div>
                        </SpotlightCard>

                        <!-- Card 3 -->
                        <SpotlightCard spotlightColor="rgba(16, 185, 129, 0.12)"
                            class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <FileCheck class="h-5 w-5" />
                                    </div>
                                    <span
                                        class="text-xs font-sans font-medium px-2.5 py-0.5 rounded-full border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950">
                                        Segregation of Duties
                                    </span>
                                </div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white tracking-tight">Alur Otorisasi Bertingkat</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Pemisahan wewenang terstruktur: Manajer Operasional menyusun usulan restock, Komisaris memberikan validasi persetujuan akhir.
                                </p>
                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400">
                                    Mencegah overbudget & pembelian sepihak
                                </div>
                            </div>
                        </SpotlightCard>
                    </div>
                </section>
            </ScrollReveal>

            <!-- SECTION: INTERACTIVE SYSTEM DEMONSTRATION -->
            <ScrollReveal :delay="50">
                <section id="simulasi" class="scroll-mt-24 space-y-6">
                    <div class="text-center max-w-2xl mx-auto space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Simulasi Interaktif
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Eksplorasi Alur Kerja Sistem
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-lg mx-auto">
                            Uji simulasi peramalan stok secara langsung, telusuri bukti ledger audit, dan verifikasi alur otorisasi pengadaan.
                        </p>
                    </div>

                    <!-- Operational Console Frame -->
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 shadow-2xl overflow-hidden">
                        <!-- Console Header Bar -->
                        <div
                            class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100/90 dark:bg-zinc-950/80 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-zinc-300 dark:bg-zinc-700 inline-block"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-zinc-300 dark:bg-zinc-700 inline-block"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-zinc-300 dark:bg-zinc-700 inline-block"></span>
                                <span class="ml-2 text-xs font-semibold text-zinc-800 dark:text-zinc-200">Konsol Operasional SIPALING</span>
                            </div>
                        </div>

                        <!-- Completed Workflow State -->
                        <div v-if="consoleWorkflowCompleted" class="p-8 sm:p-12 text-center space-y-5 animate-in fade-in duration-300">
                            <div class="h-14 w-14 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-[0_0_25px_rgba(16,185,129,0.25)]">
                                <CheckCircle2 class="h-8 w-8" />
                            </div>
                            <div class="space-y-2 max-w-lg mx-auto">
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">Simulasi Alur Operasional Selesai</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed font-sans">
                                    Mulai dari peramalan kebutuhan stok proaktif (DES), perekaman mutasi ke audit trail ledger terenkripsi (SHA-256), hingga otorisasi persetujuan pengadaan telah berhasil diselesaikan.
                                </p>
                            </div>
                            <div class="pt-2">
                                <Button @click="resetConsoleWorkflow" variant="outline" class="border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium px-5 h-9 text-xs cursor-pointer">
                                    Ulangi Simulasi Alur Kerja
                                </Button>
                            </div>
                        </div>

                        <!-- Stepper Workflow Component -->
                        <Stepper
                            v-else
                            ref="stepperRef"
                            :initial-step="1"
                            :is-dark="isDark"
                            :step-labels="['Peramalan DES', 'Audit Trail', 'Otorisasi PO']"
                            back-button-text="Tahap Sebelumnya"
                            next-button-text="Lanjut ke Tahap Berikutnya"
                            step-circle-container-class-name="border-0 shadow-none bg-transparent rounded-none"
                            step-container-class-name="bg-zinc-50 dark:bg-zinc-950/70 border-b border-zinc-200 dark:border-zinc-800/80 px-4 sm:px-8 py-3.5"
                            content-class-name="p-0"
                            step-content-class-name="p-4 sm:p-6 lg:p-8"
                            footer-class-name="border-t border-zinc-200 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-950/60 px-4 sm:px-8 py-3.5"
                            @step-change="handleStepChange"
                            @final-step-completed="handleFinalStepCompleted"
                        >
                            <!-- STEP 1: FORECASTING DES -->
                            <div class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <!-- Parameters Control Panel -->
                                <div
                                    class="space-y-4 border-b lg:border-b-0 lg:border-r border-zinc-200 dark:border-zinc-800 pb-6 lg:pb-0 lg:pr-6">
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">
                                            Parameter Prediksi Kebutuhan
                                        </h3>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 leading-relaxed">
                                            Sesuaikan parameter kepekaan untuk melihat estimasi jumlah pengadaan stok secara real-time.
                                        </p>
                                    </div>

                                    <!-- Alpha Slider -->
                                    <div class="space-y-1.5 rounded-xl bg-zinc-50 dark:bg-zinc-950/80 p-3 border border-zinc-200 dark:border-zinc-800">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-zinc-700 dark:text-zinc-300">Sensitivitas Data Terkini (&alpha;)</span>
                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-xs">{{ alphaParam }}</span>
                                        </div>
                                        <input type="range" min="0.1" max="0.9" step="0.1" v-model.number="alphaParam"
                                            class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
                                        <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                            <span>0.1 (Stabil / Historis)</span>
                                            <span>0.9 (Responsif / Tren Baru)</span>
                                        </div>
                                    </div>

                                    <!-- Beta Slider -->
                                    <div class="space-y-1.5 rounded-xl bg-zinc-50 dark:bg-zinc-950/80 p-3 border border-zinc-200 dark:border-zinc-800">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-zinc-700 dark:text-zinc-300">Adaptasi Tren Permintaan (&beta;)</span>
                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-xs">{{ betaParam }}</span>
                                        </div>
                                        <input type="range" min="0.1" max="0.9" step="0.1" v-model.number="betaParam"
                                            class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-emerald-500" />
                                        <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                            <span>0.1 (Pertumbuhan Konstan)</span>
                                            <span>0.9 (Perubahan Cepat)</span>
                                        </div>
                                    </div>

                                    <!-- Target Item Info Card -->
                                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-950/60 p-3 border border-zinc-200 dark:border-zinc-800 text-xs space-y-1">
                                        <div class="text-zinc-500 dark:text-zinc-400 text-xs font-medium">Sampel Barang:</div>
                                        <div class="font-semibold text-zinc-900 dark:text-white flex items-center justify-between">
                                            <span>ThinkPad T14 (ITM-001)</span>
                                            <span class="text-zinc-700 dark:text-zinc-200 font-semibold text-xs">Sisa Stok: 45 Unit</span>
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 pt-1">
                                            Batas Minimum (ROP): <span
                                                class="text-zinc-700 dark:text-zinc-300 font-medium">Di bawah batas aman (&le; 50 Unit)</span>
                                        </div>
                                    </div>

                                    <Button @click="handleRestockSubmit"
                                        class="w-full font-semibold bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 h-9 text-xs transition-all cursor-pointer shadow-sm"
                                        :disabled="restockSubmitted">
                                        <span v-if="restockSubmitted" class="flex items-center gap-1.5">
                                            <CheckCircle2 class="h-4 w-4" /> Pengajuan Draf Restock Terkirim ke Komisaris!
                                        </span>
                                        <span v-else class="flex items-center gap-1.5">
                                            Ajukan Draf Pengadaan ({{ projectedUnits }} Unit)
                                        </span>
                                    </Button>
                                </div>

                                <!-- Forecast Visualizer (Enhanced with Y-Axis scale & unit markers) -->
                                <div class="lg:col-span-2 space-y-4">
                                    <div
                                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                        <div>
                                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                                Riwayat Pengeluaran Riil vs Estimasi Kebutuhan
                                            </h4>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                                Analisis pergerakan stok bulanan untuk menentukan volume pemesanan optimal
                                            </p>
                                        </div>
                                        <div class="flex gap-2 text-xs">
                                            <div
                                                class="rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 px-2.5 py-1 text-right shadow-xs">
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Tingkat Presisi</div>
                                                <div class="text-emerald-600 dark:text-emerald-400 font-semibold">93.2%</div>
                                            </div>
                                            <div
                                                class="rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 px-2.5 py-1 text-right shadow-xs">
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Toleransi Selisih</div>
                                                <div class="text-zinc-700 dark:text-zinc-200 font-semibold">&plusmn;4 Unit</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Chart Frame with Y-Axis Markers -->
                                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950 p-5 space-y-4 relative">
                                        <!-- Y-Axis Reference Guide Lines -->
                                        <div
                                            class="absolute inset-x-5 top-8 bottom-12 pointer-events-none flex flex-col justify-between opacity-30">
                                            <div class="border-b border-dashed border-zinc-300 dark:border-zinc-700 w-full"></div>
                                            <div class="border-b border-dashed border-zinc-300 dark:border-zinc-700 w-full"></div>
                                            <div class="border-b border-dashed border-zinc-300 dark:border-zinc-700 w-full"></div>
                                            <div class="border-b border-zinc-300 dark:border-zinc-700 w-full"></div>
                                        </div>

                                        <!-- Bars Graph -->
                                        <div
                                            class="flex items-end gap-2.5 sm:gap-3.5 h-48 w-full border-b border-zinc-200 dark:border-zinc-800 pb-2 relative z-10">
                                            <!-- Historical Transactions -->
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">55
                                                    Unit</span>
                                                <div class="w-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 transition-colors rounded-t-sm"
                                                    style="height: 55px"></div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Bln 1</span>
                                            </div>
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">70
                                                    Unit</span>
                                                <div class="w-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 transition-colors rounded-t-sm"
                                                    style="height: 70px"></div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Bln 2</span>
                                            </div>
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">60
                                                    Unit</span>
                                                <div class="w-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 transition-colors rounded-t-sm"
                                                    style="height: 60px"></div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Bln 3</span>
                                            </div>
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">85
                                                    Unit</span>
                                                <div class="w-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 transition-colors rounded-t-sm"
                                                    style="height: 85px"></div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Bln 4</span>
                                            </div>
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">98
                                                    Unit</span>
                                                <div class="w-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 transition-colors rounded-t-sm"
                                                    style="height: 98px"></div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Bln 5</span>
                                            </div>
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">110
                                                    Unit</span>
                                                <div class="w-full bg-zinc-400 dark:bg-zinc-600 rounded-t-sm" style="height: 110px">
                                                </div>
                                                <span
                                                    class="text-xs text-zinc-700 dark:text-zinc-200 font-semibold whitespace-nowrap">Bln 6</span>
                                            </div>

                                            <!-- DES Recommendation Bar -->
                                            <div class="flex-1 flex flex-col items-center gap-1">
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">{{
                                                    projectedUnits }}
                                                    Unit</span>
                                                <div class="w-full bg-emerald-500 rounded-t-sm transition-all duration-300 shadow-[0_0_12px_rgba(16,185,129,0.3)]"
                                                    :style="{ height: `${Math.min(160, Math.round(projectedUnits * 0.95))}px` }">
                                                </div>
                                                <span
                                                    class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold whitespace-nowrap">Bln +1</span>
                                            </div>

                                            <!-- Projected Next Period -->
                                            <div class="flex-1 flex flex-col items-center gap-1">
                                                <span class="text-xs text-zinc-700 dark:text-zinc-300 font-semibold">{{
                                                    projectedNextPeriod }} Unit</span>
                                                <div class="w-full bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-500 dark:border-emerald-400/80 border-dashed rounded-t-sm transition-all duration-300"
                                                    :style="{ height: `${Math.min(160, Math.round(projectedNextPeriod * 0.95))}px` }">
                                                </div>
                                                <span
                                                    class="text-xs text-zinc-500 dark:text-zinc-400 font-medium whitespace-nowrap">Bln +2</span>
                                            </div>
                                        </div>

                                        <!-- Legend & Formula Strip -->
                                        <div
                                            class="flex flex-wrap items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 pt-1 gap-2">
                                            <div class="flex items-center gap-4 text-xs">
                                                <span class="flex items-center gap-1.5"><span
                                                        class="h-2 w-2 rounded-xs bg-zinc-300 dark:bg-zinc-700"></span> Riwayat Riil (Bln 1–6)</span>
                                                <span class="flex items-center gap-1.5"><span
                                                        class="h-2 w-2 rounded-xs bg-emerald-500"></span> Estimasi Restock (Bln +1)</span>
                                                <span class="flex items-center gap-1.5"><span
                                                        class="h-2 w-2 rounded-xs bg-emerald-50 dark:bg-emerald-950/80 border border-dashed border-emerald-500 dark:border-emerald-400"></span>
                                                    Proyeksi Tren (Bln +2)</span>
                                            </div>
                                            <div
                                                class="text-xs text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-2.5 py-1 rounded-md border border-zinc-200 dark:border-zinc-800 shadow-xs">
                                                Metode: <span class="text-zinc-800 dark:text-zinc-200 font-medium">Double Exponential Smoothing (Holt)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                            <!-- STEP 2: AUDIT TRAIL IMMUTABLE WITH FILTER & PAYLOAD INSPECTION -->
                            <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white text-sm flex items-center gap-2">
                                        <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        Perekaman Aktivitas Audit Trail Otomatis (Read-Only)
                                    </h3>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        Setiap mutasi barang dan keputusan pengadaan terkunci secara kriptografis tanpa celah manipulasi.
                                    </p>
                                </div>

                                <!-- Transaction Filter Buttons -->
                                <div class="flex gap-1.5 p-1 rounded-lg bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800">
                                    <button @click="auditFilter = 'all'"
                                        :class="['px-2.5 py-1 rounded text-xs transition-colors cursor-pointer', auditFilter === 'all' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200']">
                                        Semua ({{ auditLogs.length }})
                                    </button>
                                    <button @click="auditFilter = 'outbound'"
                                        :class="['px-2.5 py-1 rounded text-xs transition-colors cursor-pointer', auditFilter === 'outbound' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200']">
                                        Outbound
                                    </button>
                                    <button @click="auditFilter = 'inbound'"
                                        :class="['px-2.5 py-1 rounded text-xs transition-colors cursor-pointer', auditFilter === 'inbound' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200']">
                                        Inbound
                                    </button>
                                    <button @click="auditFilter = 'approval'"
                                        :class="['px-2.5 py-1 rounded text-xs transition-colors cursor-pointer', auditFilter === 'approval' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200']">
                                        Otorisasi
                                    </button>
                                </div>
                            </div>

                            <!-- Ledger Table -->
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-x-auto bg-white dark:bg-zinc-950 shadow-xs">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 font-semibold border-b border-zinc-200 dark:border-zinc-800">
                                        <tr>
                                            <th class="p-3">Log ID</th>
                                            <th class="p-3">Waktu</th>
                                            <th class="p-3">Pengguna</th>
                                            <th class="p-3">Jenis Mutasi</th>
                                            <th class="p-3">Nilai Lama</th>
                                            <th class="p-3">Nilai Baru</th>
                                            <th class="p-3">IP Address</th>
                                            <th class="p-3 text-center">Integritas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-xs text-zinc-700 dark:text-zinc-300">
                                        <tr v-for="log in filteredAuditLogs" :key="log.id"
                                            @click="selectedAuditRow = log"
                                            class="hover:bg-zinc-50 dark:hover:bg-zinc-900/60 transition-colors cursor-pointer">
                                            <td class="p-3 font-medium text-zinc-500 dark:text-zinc-400">{{ log.id }}</td>
                                            <td class="p-3 text-zinc-500 dark:text-zinc-400">{{ log.time }}</td>
                                            <td class="p-3 text-zinc-900 dark:text-white font-medium">{{ log.user }}</td>
                                            <td class="p-3">
                                                <span :class="[
                                                    'px-2 py-0.5 rounded text-xs font-medium border',
                                                    log.type === 'outbound' ? 'border-red-500/30 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/20' :
                                                        log.type === 'inbound' ? 'border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20' :
                                                            'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-900'
                                                ]">
                                                    {{ log.typeLabel }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-zinc-500 dark:text-zinc-400">{{ log.oldVal }}</td>
                                            <td class="p-3 text-emerald-600 dark:text-emerald-400 font-semibold">{{ log.newVal }}</td>
                                            <td class="p-3 text-zinc-500 dark:text-zinc-400">{{ log.ip }}</td>
                                            <td
                                                class="p-3 text-center text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1 font-medium">
                                                <ShieldCheck class="h-3.5 w-3.5" /> Terkunci
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cryptographic Inspection Drawer -->
                            <div v-if="selectedAuditRow"
                                class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/90 p-4 space-y-2 text-xs animate-in fade-in duration-200">
                                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-2">
                                    <span class="text-zinc-600 dark:text-zinc-400 flex items-center gap-1.5">
                                        <Code2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        Inspeksi Signature Kriptografis: <strong class="text-zinc-900 dark:text-white">{{
                                            selectedAuditRow.id
                                            }}</strong>
                                    </span>
                                    <button @click="selectedAuditRow = null"
                                        class="text-zinc-400 hover:text-zinc-900 dark:hover:text-white cursor-pointer">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-1">
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block">Komoditas Terikat:</span>
                                        <span class="text-zinc-900 dark:text-white font-medium">{{ selectedAuditRow.item }} ({{ selectedAuditRow.sku
                                            }})</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block">Identitas Aktor:</span>
                                        <span class="text-zinc-900 dark:text-white font-medium">{{ selectedAuditRow.user }} • {{
                                            selectedAuditRow.userRole
                                            }}</span>
                                    </div>
                                </div>
                                <div
                                    class="bg-white dark:bg-zinc-900 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs break-all text-zinc-700 dark:text-zinc-300">
                                    <span class="text-zinc-500 dark:text-zinc-400 block mb-0.5">Payload Hash (SHA-256):</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-mono text-[11px]">{{ selectedAuditRow.hash }}</span>
                                </div>
                            </div>
                            <p v-else class="text-xs text-zinc-500 dark:text-zinc-400 italic">
                                *Klik salah satu baris log di atas untuk menginspeksi signature hash dan validasi
                                payload
                                integritas data.
                            </p>
                        </div>

                            <!-- STEP 3: APPROVAL WORKFLOW WITH PROGRESS PIPELINE -->
                            <div class="space-y-4">
                            <div class="max-w-xl mx-auto space-y-5">
                                <div class="text-center space-y-1">
                                    <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">Alur Otorisasi Pengadaan Barang
                                        (Multi-Tier PO)
                                    </h3>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        Pemisahan wewenang: Manajer mengajukan draf restock &rarr; Komisaris memvalidasi anggaran.
                                    </p>
                                </div>

                                <!-- Ticket Card -->
                                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-5 space-y-4 shadow-xs">
                                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                        <div>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Tiket Pengadaan #PO-2026-089</span>
                                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mt-0.5">Laptop ThinkPad T14
                                                (ITM-001)
                                            </h4>
                                        </div>
                                        <span :class="[
                                            'px-2.5 py-0.5 rounded-full text-xs font-medium border',
                                            approvalStatus === 'pending' ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-700' :
                                                approvalStatus === 'approved' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-600/40' :
                                                    'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 border-red-300 dark:border-red-600/40'
                                        ]">
                                            {{ approvalStatus === 'pending' ? 'Menunggu Otorisasi' : approvalStatus ===
                                                'approved' ? 'Disetujui Komisaris' : 'Ditolak Komisaris' }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800/80">
                                            <span class="text-zinc-500 dark:text-zinc-400 block text-[11px]">Sisa Stok:</span>
                                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">45 Unit</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800/80">
                                            <span class="text-zinc-500 dark:text-zinc-400 block text-[11px]">Kuantitas Usulan:</span>
                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">120 Unit</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800/80">
                                            <span class="text-zinc-500 dark:text-zinc-400 block text-[11px]">Tingkat Presisi:</span>
                                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">93.2% Akurat</span>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-2">
                                        <span class="text-xs text-zinc-600 dark:text-zinc-400">Keputusan Otoritas Eksekutif:</span>
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="updateApproval('rejected')"
                                                :class="['text-xs border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer', approvalStatus === 'rejected' ? 'border-red-500 text-red-500 dark:text-red-400' : '']">
                                                <XCircle class="h-3.5 w-3.5 mr-1" /> Tolak Usulan
                                            </Button>
                                            <Button size="sm" @click="updateApproval('approved')"
                                                :class="['text-xs bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold cursor-pointer shadow-sm', approvalStatus === 'approved' ? 'ring-2 ring-emerald-400/50' : '']">
                                                <CheckCircle2 class="h-3.5 w-3.5 mr-1" /> Beri Otorisasi (PO)
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center" v-if="approvalStatus !== 'pending'">
                                    <button @click="updateApproval('pending')"
                                        class="text-xs text-zinc-500 dark:text-zinc-400 underline hover:text-zinc-800 dark:hover:text-zinc-200 cursor-pointer">
                                        Reset Status Simulasi ke Menunggu Otorisasi
                                    </button>
                                </div>
                            </div>
                        </div>
                        </Stepper>
                    </div>
                </section>
            </ScrollReveal>

            <!-- SECTION: 4 USER ROLES (RBAC) WITH SPOTLIGHT CARDS & DYNAMIC COLOR OUTLINE -->
            <ScrollReveal :delay="100">
                <section id="peran" class="scroll-mt-24 space-y-8">
                    <div class="text-center max-w-2xl mx-auto space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Hak Akses & Peran
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Pembagian Peran Pengguna (RBAC)
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400">
                            Setiap peran dikunci oleh middleware dengan matriks wewenang operasional yang spesifik. Klik
                            kartu
                            peran untuk meninjau rincian wewenang dan alur kerjanya.
                        </p>
                    </div>

                    <!-- Spotlight Cards for RBAC (Styled consistently with Keunggulan Fungsional Sistem & Color-Matched Outlines) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <SpotlightCard v-for="(role, idx) in roles" :key="role.title"
                            :spotlightColor="role.spotlightColor" @click="activeRoleIndex = idx" :class="[
                                'cursor-pointer transition-all duration-200 text-left flex flex-col justify-between p-5 rounded-2xl border',
                                activeRoleIndex === idx
                                    ? ['bg-white dark:bg-zinc-900 shadow-xl border-emerald-500', role.activeOutline]
                                    : 'bg-white dark:bg-zinc-900/80 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-900 shadow-xs'
                            ]">
                            <!-- Card Header -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div
                                        :class="['h-9 w-9 rounded-lg flex items-center justify-center border', role.iconBg]">
                                        <component :is="role.icon" class="h-4 w-4" />
                                    </div>
                                    <span v-if="activeRoleIndex === idx"
                                        :class="['text-xs font-semibold flex items-center gap-1', role.activeText]">
                                        <UserCheck class="h-3.5 w-3.5" /> Terpilih
                                    </span>
                                    <span v-else class="text-[10px] text-zinc-400">
                                        Klik untuk memilih
                                    </span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span
                                            :class="['text-xs font-medium px-2.5 py-0.5 rounded-full border', role.badgeColor]">
                                            {{ role.badge }}
                                        </span>
                                    </div>
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white tracking-tight">{{ role.title }}</h3>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed mt-1">{{ role.desc }}</p>
                                </div>
                            </div>

                            <!-- Matrix Izin Akses (Functional Permission Checklist) -->
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 space-y-2 mt-4">
                                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 block">
                                    Matriks Wewenang
                                </span>
                                <ul class="text-xs space-y-1.5 font-sans">
                                    <li v-for="perm in role.permissions" :key="perm.name"
                                        class="flex items-center justify-between text-[11px]">
                                        <span :class="perm.allowed ? 'text-zinc-800 dark:text-zinc-200' : 'text-zinc-400 dark:text-zinc-500 line-through'">
                                             {{ perm.name }}
                                        </span>
                                        <span v-if="perm.allowed" class="text-emerald-500 dark:text-emerald-400 font-bold ml-2">
                                            <Check class="h-3 w-3 stroke-[2.5]" />
                                        </span>
                                        <span v-else class="text-zinc-400 dark:text-zinc-500 font-bold ml-2">
                                            <X class="h-3 w-3 stroke-[2]" />
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </SpotlightCard>
                    </div>

                    <!-- Active Role Details Callout (Color-Matched to Selected Role) -->
                    <div
                        :class="['rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 p-5 sm:p-6 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors shadow-xs', roles[activeRoleIndex].detailBorder]">
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Detail Peran Aktif:</span>
                                <span
                                    :class="['text-xs font-medium px-2.5 py-0.5 rounded-full border', roles[activeRoleIndex].badgeColor]">
                                    {{ roles[activeRoleIndex].title }} ({{ roles[activeRoleIndex].badge }})
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                <strong class="text-zinc-900 dark:text-white">Tanggung Jawab Utama:</strong> {{
                                    roles[activeRoleIndex].responsibilities.join(' • ') }}
                            </p>
                        </div>
                        <Link :href="route('login')">
                            <Button size="sm"
                                :class="['text-xs font-semibold shrink-0 gap-1.5 shadow-sm transition-colors cursor-pointer', roles[activeRoleIndex].buttonBg]">
                                Masuk sebagai {{ roles[activeRoleIndex].title }}
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </div>
                </section>
            </ScrollReveal>

            <!-- SECTION: PPT TRANSFORMATION MATRIX -->
            <ScrollReveal :delay="100">
                <section id="arsitektur"
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 sm:p-10 space-y-8 shadow-xs">
                    <div class="max-w-2xl space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Kerangka Kerja
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Harmonisasi People, Process, & Technology
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400">
                            Transformasi menyeluruh tata kelola gudang dari sistem konvensional menuju platform cerdas yang terstandarisasi.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Column 1: People -->
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/70 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Sumber Daya (People)</span>
                                <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 px-2 py-0.5 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">Akuntabilitas</span>
                            </div>
                            <div class="space-y-3 text-xs">
                                <div class="space-y-1">
                                    <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 block">Pola Konvensional</span>
                                    <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                        Staf rentan kesalahan catat fisik; manajer mengira-ngira kebutuhan restock tanpa
                                        bukti analitik.
                                    </p>
                                </div>
                                <div class="space-y-1 pt-2 border-t border-zinc-200 dark:border-zinc-800/80">
                                    <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 block">Peningkatan Sistem</span>
                                    <p class="text-zinc-800 dark:text-zinc-200 leading-relaxed font-medium">
                                        Pemisahan hak akses (RBAC) yang mengikat setiap aksi ke identitas pengguna;
                                        manajer dibekali rekomendasi data matematis.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Process -->
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/70 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Proses Bisnis (Process)</span>
                                <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 px-2 py-0.5 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">Proaktif</span>
                            </div>
                            <div class="space-y-3 text-xs">
                                <div class="space-y-1">
                                    <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 block">Pola Konvensional</span>
                                    <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                        Audit dilakukan reaktif berkala saat tutup buku; pengadaan baru berjalan setelah
                                        stok habis (stockout).
                                    </p>
                                </div>
                                <div class="space-y-1 pt-2 border-t border-zinc-200 dark:border-zinc-800/80">
                                    <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 block">Peningkatan Sistem</span>
                                    <p class="text-zinc-800 dark:text-zinc-200 leading-relaxed font-medium">
                                        Continuous real-time audit trail; pengadaan terotomasi sebelum stok menyentuh batas
                                        kritis (proaktif).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Column 3: Technology -->
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/70 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Teknologi (Technology)</span>
                                <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 px-2 py-0.5 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">Integritas</span>
                            </div>
                            <div class="space-y-3 text-xs">
                                <div class="space-y-1">
                                    <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 block">Pola Konvensional</span>
                                    <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                        Spreadsheet terpisah yang mudah diubah, terhapus, atau dimanipulasi tanpa rekam
                                        jejak versi.
                                    </p>
                                </div>
                                <div class="space-y-1 pt-2 border-t border-zinc-200 dark:border-zinc-800/80">
                                    <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 block">Peningkatan Sistem</span>
                                    <p class="text-zinc-800 dark:text-zinc-200 leading-relaxed font-medium">
                                        Arsitektur terpusat modern dengan tabel audit immutable berbasis ledger transaksi
                                        terenkripsi dan validasi server-side.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </ScrollReveal>

            <!-- SECTION: ENGINEERING TEAM (PROFESSIONAL ENGINEERING PRESENTATION) -->
            <ScrollReveal :delay="100">
                <section id="pengembang" class="scroll-mt-24 space-y-8">
                    <div class="text-center max-w-2xl mx-auto space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Tim Pengembang
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Tim Rekayasa Sistem Informasi
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400">
                            D-IV Sistem Informasi Bisnis, Jurusan Teknologi Informasi, Politeknik Negeri Malang
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div v-for="member in engineeringTeam" :key="member.name"
                            class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/80 p-5 text-center space-y-4 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors flex flex-col justify-between items-center shadow-xs">
                            <div class="space-y-3">
                                <div
                                    class="mx-auto h-12 w-12 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-200 shadow-sm">
                                    {{ member.initials }}
                                </div>
                                <h3 class="font-semibold text-zinc-900 dark:text-white text-xs leading-tight">{{ member.name }}</h3>
                            </div>
                            <div>
                                <span
                                    :class="[
                                        'text-xs font-sans font-medium px-3 py-0.5 rounded-full border',
                                        member.role === 'Lead'
                                            ? 'border-emerald-500/40 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 font-semibold shadow-xs'
                                            : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-950'
                                    ]">
                                    {{ member.role }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </ScrollReveal>

            <!-- BOTTOM BANNER -->
            <ScrollReveal :delay="100">
                <section
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-gradient-to-br from-zinc-100 via-white to-zinc-50 dark:from-zinc-900 dark:via-zinc-900/90 dark:to-zinc-950 p-8 sm:p-12 space-y-6 text-left relative overflow-hidden shadow-sm">
                    <div class="max-w-xl space-y-3 relative z-10">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mb-1">
                            Akses Sistem
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Akses Master Data & Operasional Inventaris
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed font-sans">
                            Gunakan portal SIPALING untuk meninjau master data barang, melakukan simulasi mutasi, dan
                            memeriksa
                            rekam jejak aktivitas operasional.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 relative z-10">
                        <Link :href="route('login')">
                            <Button size="lg"
                                class="bg-emerald-600 dark:bg-emerald-500 text-white dark:text-zinc-950 font-semibold px-6 h-10 text-xs shadow-sm cursor-pointer">
                                Masuk ke Sistem
                                <ArrowRight class="h-4 w-4 ml-1.5" />
                            </Button>
                        </Link>
                        <Link :href="route('inventory.index')">
                            <Button size="lg" variant="outline"
                                class="border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium px-5 h-10 text-xs cursor-pointer">
                                Katalog Inventaris
                            </Button>
                        </Link>
                    </div>
                </section>
            </ScrollReveal>
        </main>

        <!-- FOOTER -->
        <footer class="border-t border-zinc-200 dark:border-zinc-800/80 bg-white dark:bg-zinc-950 py-8 text-xs text-zinc-500 dark:text-zinc-400 transition-colors">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                        SP
                    </div>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-300">SIPALING</span>
                    <span>• Sistem Informasi Manajemen Inventaris & Audit</span>
                </div>
                <div class="text-zinc-500 dark:text-zinc-400">
                    Jurusan Teknologi Informasi, Politeknik Negeri Malang.
                </div>
                <div class="flex gap-4">
                    <Link :href="route('inventory.index')" class="hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">Master Data
                    </Link>
                    <Link :href="route('login')" class="hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">Masuk</Link>
                    <Link :href="route('register')" class="hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">Daftar</Link>
                </div>
            </div>
        </footer>

        <!-- FLOATING ACTION BUTTONS (BOTTOM RIGHT) -->
        <div class="fixed bottom-6 right-6 z-50 flex flex-col items-center gap-2.5">
            <!-- Scroll To Top Button (Appears when scrolled down) -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-3 scale-90"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-3 scale-90"
            >
                <button
                    v-if="showScrollTop"
                    @click="scrollToTop"
                    type="button"
                    aria-label="Kembali ke atas"
                    class="h-11 w-11 rounded-full flex items-center justify-center shadow-xl border cursor-pointer transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-950 dark:hover:text-white hover:scale-105 active:scale-95 group"
                    title="Kembali ke atas"
                >
                    <ArrowUp class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" />
                </button>
            </Transition>

            <!-- Theme Toggle Button (Light / Dark Mode) -->
            <button
                @click="toggleTheme"
                type="button"
                :aria-label="isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
                class="h-11 w-11 rounded-full flex items-center justify-center shadow-xl border cursor-pointer transition-all duration-200 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-950 dark:hover:text-white hover:scale-105 active:scale-95 group"
                :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
            >
                <!-- Sun icon when dark (click to switch to light) -->
                <Sun v-if="isDark" class="h-5 w-5 text-amber-400 transition-transform group-hover:rotate-45" />
                <!-- Moon icon when light (click to switch to dark) -->
                <Moon v-else class="h-5 w-5 text-emerald-600 transition-transform group-hover:-rotate-12" />
            </button>
        </div>
    </div>
</template>
