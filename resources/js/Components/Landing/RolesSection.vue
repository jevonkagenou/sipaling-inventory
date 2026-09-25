<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import SpotlightCard from "@/Components/bits/SpotlightCard.vue";
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
import {
    Building2,
    SlidersHorizontal,
    Boxes,
    ShieldCheck,
    UserCheck,
    Check,
    X,
    ArrowRight
} from "lucide-vue-next";

const activeRoleIndex = ref(0);

const roles = [
    {
        title: "Komisaris",
        badge: "Otoritas Eksekutif",
        badgeColor: "border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-950",
        iconBg: "bg-zinc-100 dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300",
        spotlightColor: "rgba(37, 99, 235, 0.12)",
        activeOutline: "border-blue-600/80 ring-1 ring-blue-600/40 shadow-[0_0_20px_rgba(37,99,235,0.15)]",
        activeText: "text-blue-600 dark:text-blue-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm",
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
        spotlightColor: "rgba(37, 99, 235, 0.12)",
        activeOutline: "border-blue-600/80 ring-1 ring-blue-600/40 shadow-[0_0_20px_rgba(37,99,235,0.15)]",
        activeText: "text-blue-600 dark:text-blue-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm",
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
        spotlightColor: "rgba(37, 99, 235, 0.12)",
        activeOutline: "border-blue-600/80 ring-1 ring-blue-600/40 shadow-[0_0_20px_rgba(37,99,235,0.15)]",
        activeText: "text-blue-600 dark:text-blue-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm",
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
        spotlightColor: "rgba(37, 99, 235, 0.12)",
        activeOutline: "border-blue-600/80 ring-1 ring-blue-600/40 shadow-[0_0_20px_rgba(37,99,235,0.15)]",
        activeText: "text-blue-600 dark:text-blue-400",
        detailBorder: "border-zinc-200 dark:border-zinc-800",
        buttonBg: "bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm",
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
</script>

<template>
    <!-- SECTION: 4 USER ROLES (RBAC) WITH SPOTLIGHT CARDS & DYNAMIC COLOR OUTLINE -->
    <ScrollReveal :delay="100">
        <section id="peran" class="scroll-mt-24 space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-600/20 mb-1">
                    Hak Akses & Peran
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">
                    Pembagian Peran Pengguna (RBAC)
                </h2>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400">
                    Setiap peran dikunci oleh middleware dengan matriks wewenang operasional yang spesifik. Klik
                    kartu peran untuk meninjau rincian wewenang dan alur kerjanya.
                </p>
            </div>

            <!-- Spotlight Cards for RBAC -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <SpotlightCard v-for="(role, idx) in roles" :key="role.title"
                    :spotlightColor="role.spotlightColor" @click="activeRoleIndex = idx" :class="[
                        'cursor-pointer transition-all duration-200 text-left flex flex-col justify-between p-5 rounded-2xl border',
                        activeRoleIndex === idx
                            ? ['bg-white dark:bg-zinc-900 shadow-xl border-blue-600', role.activeOutline]
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
</template>
