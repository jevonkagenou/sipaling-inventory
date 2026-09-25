<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import Aurora from "@/Components/bits/Aurora.vue";
import { ArrowRight, Boxes } from "lucide-vue-next";

defineProps({
    isDark: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <!-- HERO SECTION -->
    <section class="relative min-h-screen w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-800/80 bg-white dark:bg-[#0F172A] flex items-center justify-center transition-colors">
        <!-- Official Vue Bits Aurora WebGL Background (Active in Dark Mode: Blue & Indigo Stitch AI) -->
        <div v-if="isDark" class="absolute inset-0 pointer-events-none overflow-hidden z-0">
            <Aurora :color-stops="['#0F172A', '#2563EB', '#4F46E5']" :blend="0.5" :amplitude="1.0" :speed="0.5" />
        </div>
        <!-- Clean Enterprise Ambient Glow (Active in Light Mode) -->
        <div v-else class="absolute inset-0 pointer-events-none overflow-hidden z-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_0%,rgba(37,99,235,0.12),rgba(255,255,255,0))]" />

        <!-- Subtle Gradient Overlay to blend with dark/light page background -->
        <div
            class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-b from-transparent via-transparent to-zinc-50/50 dark:to-[#0F172A]" />

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
                        class="group w-full sm:w-auto h-12 px-7 text-sm font-semibold rounded-full cursor-pointer transition-all duration-200 bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/20 border border-blue-500/40 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
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
</template>
