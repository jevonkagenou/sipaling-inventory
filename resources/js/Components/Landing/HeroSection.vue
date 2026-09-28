<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import Antigravity from "@/Components/bits/Antigravity.vue";
import { ArrowRight, Boxes } from "lucide-vue-next";

defineProps({
    isDark: {
        type: Boolean,
        default: false,
    },
});

</script>

<template>
    <!-- HERO SECTION (CLEAN, 100% ABOVE-THE-FOLD, ZERO CLUTTER) -->
    <section
        class="relative min-h-screen min-h-[100dvh] w-full flex flex-col items-center justify-center pt-24 pb-16 px-4 bg-transparent"
    >
        <!-- Soft Static Center Ambient Bloom -->
        <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[650px] h-[360px] bg-blue-500/10 dark:bg-blue-600/15 blur-[140px] rounded-full z-0" />

        <!-- Interactive Antigravity Floating Particles (Glistening zero-gravity nodes reacting to cursor) -->
        <div class="absolute inset-0 pointer-events-auto overflow-hidden z-0 [mask-image:linear-gradient(to_bottom,black_80%,transparent_100%)]">
            <Antigravity
                :count="110"
                :magnet-radius="9"
                :wave-speed="0.32"
                :particle-size="0.8"
                :lerp-speed="0.075"
                :color="isDark ? '#38BDF8' : '#2563EB'"
                :is-dark="isDark"
                :auto-animate="true"
                :particle-variance="0.35"
                :field-strength="4.8"
                particle-shape="cube"
            />
        </div>

        <!-- Hero Header Content (Pure, Confident, Clean Typography) -->
        <div class="relative z-20 mx-auto max-w-4xl text-center space-y-8 w-full flex flex-col items-center justify-center pointer-events-none">

            <!-- Clean, Authoritative Headline -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-zinc-900 dark:text-white leading-tight font-sans">
                Pengelolaan Inventaris Proaktif <br class="hidden sm:inline" />
                dengan Rekam Jejak Audit Permanen
            </h1>

            <!-- Professional Subtitle -->
            <p class="mx-auto max-w-2xl text-sm sm:text-base text-zinc-600 dark:text-zinc-300 leading-relaxed font-sans">
                Sistem sentralisasi persediaan berbasis peramalan DES otomatis dan rekam jejak audit mutasi permanen anti-manipulasi.
            </p>

            <!-- Professional Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 sm:gap-4 w-full sm:w-auto pointer-events-auto pt-2">
                <Link :href="route('login')" class="w-full sm:w-auto">
                    <Button size="lg"
                        class="group w-full sm:w-auto h-12 px-7 text-sm font-semibold rounded-full cursor-pointer transition-all duration-200 bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/20 border border-blue-500/40 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                        <span>Buka Portal Aplikasi</span>
                        <ArrowRight class="h-4 w-4 ml-2 transition-transform duration-200 group-hover:translate-x-1" />
                    </Button>
                </Link>
                <Link :href="route('inventory.index')" class="w-full sm:w-auto">
                    <Button size="lg" variant="outline"
                        class="group w-full sm:w-auto h-12 px-6 text-sm font-medium rounded-full cursor-pointer transition-all duration-200 border border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/90 text-zinc-800 dark:text-zinc-200 hover:text-zinc-950 dark:hover:text-white hover:border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 shadow-sm hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]">
                        <Boxes class="h-4 w-4 mr-2 text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-800 dark:group-hover:text-zinc-200 transition-colors" />
                        <span>Katalog Inventaris</span>
                    </Button>
                </Link>
            </div>

        </div>
    </section>
</template>
