<script setup>
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";

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
    <!-- SECTION: ENGINEERING TEAM -->
    <ScrollReveal :delay="100">
        <section id="pengembang" class="scroll-mt-24 space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-600/20 mb-1">
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
                                    ? 'border-blue-500/40 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/40 font-semibold shadow-xs'
                                    : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-950'
                            ]">
                            {{ member.role }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </ScrollReveal>
</template>
