<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Briefcase,
    Boxes,
    FileSearch,
    UserCheck,
    Lock,
    Unlock,
    CheckCircle2,
    Database,
    BarChart3,
    ClipboardCheck,
    History,
    Key,
    Users,
    Fingerprint,
    Info,
    ArrowUpRight
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user || {});
const roles = computed(() => page.props.auth.roles || []);
const permissions = computed(() => page.props.auth.permissions || []);

const primaryRole = computed(() => {
    if (roles.value.includes('komisaris')) return 'komisaris';
    if (roles.value.includes('manajer-operasional')) return 'manajer-operasional';
    if (roles.value.includes('staf-gudang')) return 'staf-gudang';
    if (roles.value.includes('auditor-internal')) return 'auditor-internal';
    return roles.value[0] || 'Unassigned';
});

const roleConfig = {
    'komisaris': {
        label: 'Komisaris',
        badgeClass: 'bg-indigo-600/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
        desc: 'Memegang wewenang otorisasi tertinggi untuk persetujuan usulan restock dan pengawasan tata kelola.',
        icon: ShieldCheck,
    },
    'manajer-operasional': {
        label: 'Manajer Operasional',
        badgeClass: 'bg-blue-600/10 text-blue-700 dark:text-blue-300 border-blue-500/30',
        desc: 'Mengelola analitik peramalan DES, pemantauan stok menipis, draf restock, dan manajemen pengguna.',
        icon: Briefcase,
    },
    'staf-gudang': {
        label: 'Staf Gudang',
        badgeClass: 'bg-amber-600/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
        desc: 'Bertanggung jawab mencatat transaksi fisik barang masuk (Inbound) dan barang keluar (Outbound).',
        icon: Boxes,
    },
    'auditor-internal': {
        label: 'Auditor Internal',
        badgeClass: 'bg-emerald-600/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        desc: 'Menginspeksi catatan log audit trail transaksi dan mengekspor bukti ke format berkas resmi.',
        icon: FileSearch,
    },
};

const currentRoleInfo = computed(() => {
    return roleConfig[primaryRole.value] || {
        label: primaryRole.value,
        badgeClass: 'bg-zinc-600/10 text-zinc-700 dark:text-zinc-300 border-zinc-500/30',
        desc: 'Pengguna terdaftar dengan akses standar.',
        icon: UserCheck,
    };
});

// Matriks Hak Akses Modul untuk Keperluan Testing Tim
const modules = computed(() => [
    {
        name: 'Katalog Master Inventaris',
        desc: 'Katalog master produk retail, stok fisik aktual, ambang batas safety stock, dan kategori barang.',
        url: '/inventory',
        allowedRoles: ['Semua Peran'],
        icon: Database,
        isAllowed: true,
        actionLabel: 'Buka Katalog',
    },
    {
        name: 'Operasional Mutasi Gudang',
        desc: 'Pencatatan mutasi masuk (Inbound) dan mutasi keluar (Outbound) dengan validasi stok riil.',
        url: '/transactions',
        allowedRoles: ['Staf Gudang', 'Manajer Operasional'],
        icon: History,
        isAllowed: roles.value.some(r => ['staf-gudang', 'manajer-operasional'].includes(r)),
        actionLabel: 'Buka Mutasi',
    },
    {
        name: 'Mesin Analitik DES Holt\'s Linear',
        desc: 'Visualisasi kurva aktual vs proyeksi peramalan, parameter alpha/beta, dan kalkulasi MAPE & RMSE.',
        url: '/analytics',
        allowedRoles: ['Manajer Operasional', 'Komisaris'],
        icon: BarChart3,
        isAllowed: roles.value.some(r => ['manajer-operasional', 'komisaris'].includes(r)),
        actionLabel: 'Buka Analitik',
    },
    {
        name: 'Otorisasi Restock Pengadaan',
        desc: 'Portal evaluasi usulan pengadaan barang kritis dari manajer operasional untuk keputusan persetujuan.',
        url: '/restock/approval',
        allowedRoles: ['Komisaris'],
        icon: ClipboardCheck,
        isAllowed: roles.value.includes('komisaris'),
        actionLabel: 'Buka Otorisasi',
    },
    {
        name: 'Jejak Audit & Forensik Log',
        desc: 'Penelusuran histori perubahan data append-only, metadata IP/User-Agent, dan ekspor laporan CSV/PDF.',
        url: '/audit',
        allowedRoles: ['Auditor Internal', 'Komisaris'],
        icon: FileSearch,
        isAllowed: roles.value.some(r => ['auditor-internal', 'komisaris'].includes(r)),
        actionLabel: 'Buka Audit Log',
    },
    {
        name: 'Manajemen Pengguna & Aktivasi',
        desc: 'Manajemen status aktivasi akun staf gudang dan pengaturan hak akses pengguna sistem.',
        url: '/users',
        allowedRoles: ['Manajer Operasional', 'Komisaris'],
        icon: Users,
        isAllowed: roles.value.some(r => ['manajer-operasional', 'komisaris'].includes(r)),
        actionLabel: 'Kelola Pengguna',
    },
]);

const testAccounts = [
    { role: 'Komisaris', email: 'komisaris@sipaling.com', badgeColor: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-500/20' },
    { role: 'Manajer Operasional', email: 'manajer@sipaling.com', badgeColor: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-500/20' },
    { role: 'Staf Gudang', email: 'staf@sipaling.com', badgeColor: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-500/20' },
    { role: 'Auditor Internal', email: 'auditor@sipaling.com', badgeColor: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-500/20' },
];
</script>

<template>
    <Head title="Control Center & RBAC Testing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="space-y-1">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Pusat Kendali Sistem SIPALING
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                        Sistem Inventaris Prediktif & Audit Log Terintegrasi • Sesi Aktif Pengguna
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border" :class="currentRoleInfo.badgeClass">
                        <component :is="currentRoleInfo.icon" class="h-3.5 w-3.5" />
                        {{ currentRoleInfo.label }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sesi Aktif
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- SECTION 1: PROFIL IDENTITAS PENGGUNA & STATUS SPATIE -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-xs">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                        <div class="lg:col-span-2 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 rounded-xl bg-blue-600/10 border border-blue-600/20 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-lg">
                                    {{ (user.name || 'U').charAt(0) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                                        Selamat datang, {{ user.name }}
                                    </h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ user.email }} • Telp: {{ user.phone || '-' }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                {{ currentRoleInfo.desc }}
                            </p>
                        </div>

                        <!-- ID & Status Card -->
                        <div class="rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-950/50 p-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                                <span class="flex items-center gap-1 font-medium">
                                    <Fingerprint class="h-3.5 w-3.5" /> UUID Pengguna:
                                </span>
                                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Valid UUID v4</span>
                            </div>
                            <div class="p-2 rounded-md bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 font-mono text-[11px] text-zinc-700 dark:text-zinc-300 break-all select-all">
                                {{ user.id || 'N/A' }}
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-zinc-500">Status Akun:</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Terverifikasi & Aktif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Granted Pill List -->
                    <div v-if="permissions.length > 0" class="mt-5 pt-5 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                                <Key class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                Hak Akses Fungsional (Permissions Granted via Spatie):
                            </span>
                            <span class="text-xs text-zinc-500">{{ permissions.length }} izin aktif</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="perm in permissions" :key="perm"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-mono bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                {{ perm }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: MATRIKS UJI AKSES MODUL (RBAC TESTING GRID) -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">
                                Matriks Pengujian Hak Akses Modul (RBAC Tester)
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Uji apakah modul terbuka atau ditolak (HTTP 403) sesuai wewenang peran aktif Anda
                            </p>
                        </div>
                        <span class="text-xs text-zinc-500 flex items-center gap-1">
                            <Info class="h-3.5 w-3.5 text-blue-500" /> Klik tombol untuk menguji respon middleware
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="mod in modules" :key="mod.name"
                            :class="[
                                'rounded-2xl border p-5 transition-all duration-200 flex flex-col justify-between space-y-4 shadow-xs',
                                mod.isAllowed
                                    ? 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-blue-400 dark:hover:border-blue-500'
                                    : 'bg-zinc-50/60 dark:bg-zinc-950/40 border-zinc-200/60 dark:border-zinc-800/60 opacity-90'
                            ]">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="p-2.5 rounded-xl border"
                                        :class="mod.isAllowed ? 'bg-blue-600/10 text-blue-600 dark:text-blue-400 border-blue-600/20' : 'bg-zinc-200/50 dark:bg-zinc-800 text-zinc-500 border-zinc-300 dark:border-zinc-700'">
                                        <component :is="mod.icon" class="h-5 w-5" />
                                    </div>
                                    <span v-if="mod.isAllowed"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                        <Unlock class="h-3 w-3" /> Akses Diberikan
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-200/60 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700">
                                        <Lock class="h-3 w-3" /> Akses Terbatas
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <h4 class="font-bold text-sm text-zinc-900 dark:text-white leading-snug">
                                        {{ mod.name }}
                                    </h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                        {{ mod.desc }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80 text-xs">
                                <div class="flex items-center justify-between text-[11px] text-zinc-500">
                                    <span>Hak Akses:</span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300 text-right">
                                        {{ mod.allowedRoles.join(', ') }}
                                    </span>
                                </div>

                                <div>
                                    <Link :href="mod.url"
                                        :class="[
                                            'w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors',
                                            mod.isAllowed
                                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs'
                                                : 'bg-zinc-200 dark:bg-zinc-800 hover:bg-rose-600 hover:text-white text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-700'
                                        ]">
                                        <span>{{ mod.actionLabel }}</span>
                                        <span v-if="!mod.isAllowed" class="text-[10px] opacity-80">(Uji 403)</span>
                                        <ArrowUpRight class="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: PANDUAN PENGUJIAN AKUN SEEDER TIM -->
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-xs space-y-4">
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white flex items-center gap-2">
                            <Info class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            Panduan Uji Coba Tim (Akun Bawaan Seeder)
                        </h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Gunakan kredensial berikut untuk menguji perbedaan tampilan dan wewenang saat berdiskusi bersama tim:
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        <div v-for="acc in testAccounts" :key="acc.email"
                            class="p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border" :class="acc.badgeColor">
                                {{ acc.role }}
                            </span>
                            <div class="font-mono text-zinc-800 dark:text-zinc-200 select-all font-medium text-[11px] truncate" :title="acc.email">
                                {{ acc.email }}
                            </div>
                            <div class="text-[11px] text-zinc-500">
                                Password: <code class="font-mono text-zinc-700 dark:text-zinc-300">password</code>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
