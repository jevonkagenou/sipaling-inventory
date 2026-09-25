<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
import HeroSection from "@/Components/Landing/HeroSection.vue";
import FeaturesSection from "@/Components/Landing/FeaturesSection.vue";
import SimulationSection from "@/Components/Landing/SimulationSection.vue";
import RolesSection from "@/Components/Landing/RolesSection.vue";
import ArchitectureSection from "@/Components/Landing/ArchitectureSection.vue";
import TeamSection from "@/Components/Landing/TeamSection.vue";
import {
    ArrowRight,
    ArrowUp,
    Sun,
    Moon,
    Boxes
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

// Theme Management (Light / Dark Mode persisted in localStorage)
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
</script>

<template>
    <Head title="SIPALING" />

    <div
        :class="{ dark: isDark }"
        class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans antialiased selection:bg-blue-600/20 selection:text-blue-900 dark:selection:bg-zinc-800 dark:selection:text-white transition-colors duration-200">
        
        <!-- FLOATING PILL TOP NAVIGATION (STICKY / FIXED OVER HERO & CONTENT) -->
        <header class="fixed top-4 sm:top-5 inset-x-0 z-50 flex justify-center px-4 pointer-events-none">
            <nav
                class="pointer-events-auto flex items-center justify-between w-full max-w-4xl px-3 sm:px-4 py-2 rounded-full border border-zinc-200/80 dark:border-zinc-800/90 bg-white/90 dark:bg-zinc-950/85 backdrop-blur-md shadow-lg shadow-zinc-950/5 dark:shadow-black/40 transition-all duration-200">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white pl-1 sm:pl-2">
                    <Boxes class="h-5 w-5 text-blue-600 dark:text-blue-400" />
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
                            class="inline-flex items-center justify-center px-4 py-1.5 text-xs font-semibold rounded-full bg-blue-600 dark:bg-blue-500 text-white dark:text-slate-950 transition-colors shadow-sm">
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')"
                                class="inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-semibold rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                                Masuk
                            </Link>
                        </template>
                    </template>
                </div>
            </nav>
        </header>

        <!-- 1. HERO SECTION -->
        <HeroSection :is-dark="isDark" />

        <!-- MAIN CONTENT CONTAINER -->
        <main class="mx-auto max-w-6xl px-4 sm:px-6 py-16 sm:py-24 space-y-24 sm:space-y-32">
            <!-- 2. FEATURES / 3 CORE PILLARS SECTION -->
            <FeaturesSection />

            <!-- 3. SIMULATION SECTION -->
            <SimulationSection :is-dark="isDark" />

            <!-- 4. ROLES / RBAC SECTION -->
            <RolesSection />

            <!-- 5. ARCHITECTURE / PPT TRANSFORMATION MATRIX SECTION -->
            <ArchitectureSection />

            <!-- 6. TEAM SECTION -->
            <TeamSection />

            <!-- BOTTOM BANNER -->
            <ScrollReveal :delay="100">
                <section
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-gradient-to-br from-zinc-100 via-white to-zinc-50 dark:from-zinc-900 dark:via-zinc-900/90 dark:to-zinc-950 p-8 sm:p-12 space-y-6 text-left relative overflow-hidden shadow-sm">
                    <div class="max-w-xl space-y-3 relative z-10">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-600/20 mb-1">
                            Akses Sistem
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Akses Master Data & Operasional Inventaris
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed font-sans">
                            Gunakan portal SIPALING untuk meninjau master data barang, melakukan simulasi mutasi, dan
                            memeriksa rekam jejak aktivitas operasional.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 relative z-10">
                        <Link :href="route('login')">
                            <Button size="lg"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 h-10 text-xs shadow-sm cursor-pointer">
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
                    <Boxes class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <span class="font-semibold text-zinc-800 dark:text-zinc-300">SIPALING</span>
                    <span>• Sistem Informasi Manajemen Inventaris & Audit</span>
                </div>
                <div class="text-zinc-500 dark:text-zinc-400">
                    Jurusan Teknologi Informasi, Politeknik Negeri Malang.
                </div>
                <div class="flex gap-4">
                    <Link :href="route('inventory.index')" class="hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">Master Data
                    </Link>
                    <Link :href="route('login')" class="hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">Masuk Sistem</Link>
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
                <Moon v-else class="h-5 w-5 text-blue-600 transition-transform group-hover:-rotate-12" />
            </button>
        </div>
    </div>
</template>
