<script setup>
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
</script>

<template>
    <!-- SECTION: PPT TRANSFORMATION MATRIX -->
    <ScrollReveal :delay="100">
        <section id="arsitektur"
            class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 sm:p-10 space-y-8 shadow-xs">
            <div class="max-w-2xl space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-600/20 mb-1">
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
                            <span class="text-[11px] font-medium text-blue-600 dark:text-blue-400 block">Peningkatan Sistem</span>
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
                            <span class="text-[11px] font-medium text-blue-600 dark:text-blue-400 block">Peningkatan Sistem</span>
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
                            <span class="text-[11px] font-medium text-blue-600 dark:text-blue-400 block">Peningkatan Sistem</span>
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
</template>
