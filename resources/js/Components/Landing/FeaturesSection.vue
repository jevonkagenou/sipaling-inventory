<script setup>
import SpotlightCard from "@/Components/bits/SpotlightCard.vue";
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
import { TrendingUp, ShieldCheck, FileCheck } from "lucide-vue-next";
</script>

<template>
    <!-- SECTION: 3 CORE SYSTEM PILLARS -->
    <ScrollReveal :delay="100">
        <section id="fitur" class="scroll-mt-24 space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 mb-1">
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
                <!-- Card 1: Peramalan Proaktif -->
                <SpotlightCard spotlightColor="rgba(37, 99, 235, 0.12)"
                    class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div
                                class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-blue-600 dark:text-blue-400 flex items-center justify-center">
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

                <!-- Card 2: Audit Trail Terintegrasi (Unified to Royal Blue #2563EB to eliminate purplish tint) -->
                <SpotlightCard spotlightColor="rgba(37, 99, 235, 0.12)"
                    class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div
                                class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-blue-600 dark:text-blue-400 flex items-center justify-center">
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

                <!-- Card 3: Alur Otorisasi Bertingkat -->
                <SpotlightCard spotlightColor="rgba(37, 99, 235, 0.12)"
                    class="bg-white dark:bg-zinc-900/80 border border-zinc-200 dark:border-zinc-800 text-left hover:border-zinc-300 dark:hover:border-zinc-700 shadow-sm hover:shadow-md transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div
                                class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-blue-600 dark:text-blue-400 flex items-center justify-center">
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
</template>
