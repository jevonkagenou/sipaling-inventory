<script setup>
import { ref, computed } from "vue";
import { Button } from "@/Components/ui/button";
import ScrollReveal from "@/Components/bits/ScrollReveal.vue";
import Stepper from "@/Components/bits/Stepper.vue";
import {
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Code2,
    X
} from "lucide-vue-next";

defineProps({
    isDark: {
        type: Boolean,
        default: true,
    },
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
</script>

<template>
    <!-- SECTION: INTERACTIVE SYSTEM DEMONSTRATION -->
    <ScrollReveal :delay="50">
        <section id="simulasi" class="scroll-mt-24 space-y-6">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 mb-1">
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
                    <div class="h-14 w-14 rounded-full bg-blue-600/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto shadow-[0_0_25px_rgba(37,99,235,0.25)]">
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
                                        <span class="font-semibold text-blue-600 dark:text-blue-400 text-xs">{{ alphaParam }}</span>
                                    </div>
                                    <input type="range" min="0.1" max="0.9" step="0.1" v-model.number="alphaParam"
                                        class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                                    <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                        <span>0.1 (Stabil / Historis)</span>
                                        <span>0.9 (Responsif / Tren Baru)</span>
                                    </div>
                                </div>

                                <!-- Beta Slider -->
                                <div class="space-y-1.5 rounded-xl bg-zinc-50 dark:bg-zinc-950/80 p-3 border border-zinc-200 dark:border-zinc-800">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-zinc-700 dark:text-zinc-300">Adaptasi Tren Permintaan (&beta;)</span>
                                        <span class="font-semibold text-blue-600 dark:text-blue-400 text-xs">{{ betaParam }}</span>
                                    </div>
                                    <input type="range" min="0.1" max="0.9" step="0.1" v-model.number="betaParam"
                                        class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-800 rounded-lg appearance-none cursor-pointer accent-blue-600" />
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
                                    class="w-full font-semibold bg-blue-600 hover:bg-blue-700 text-white h-9 text-xs transition-all cursor-pointer shadow-sm"
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
                                        <div class="border-b border-dashed border-zinc-300 dark:border-zinc-700 w-full"></div>
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
                                            <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">110
                                                Unit</span>
                                            <div class="w-full bg-zinc-400 dark:bg-zinc-600 rounded-t-sm" style="height: 110px">
                                            </div>
                                            <span
                                                class="text-xs text-zinc-700 dark:text-zinc-200 font-semibold whitespace-nowrap">Bln 6</span>
                                        </div>

                                        <!-- DES Recommendation Bar (Stitch AI Primary Blue #2563EB) -->
                                        <div class="flex-1 flex flex-col items-center gap-1">
                                            <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">{{
                                                projectedUnits }}
                                                Unit</span>
                                            <div class="w-full bg-blue-600 rounded-t-sm transition-all duration-300 shadow-[0_0_12px_rgba(37,99,235,0.3)]"
                                                :style="{ height: `${Math.min(160, Math.round(projectedUnits * 0.95))}px` }">
                                            </div>
                                            <span
                                                class="text-xs text-blue-600 dark:text-blue-400 font-semibold whitespace-nowrap">Bln +1</span>
                                        </div>

                                        <!-- Projected Next Period (Stitch AI Secondary Indigo #4F46E5) -->
                                        <div class="flex-1 flex flex-col items-center gap-1">
                                            <span class="text-xs text-zinc-700 dark:text-zinc-300 font-semibold">{{
                                                projectedNextPeriod }} Unit</span>
                                            <div class="w-full bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-500 dark:border-indigo-400/80 border-dashed rounded-t-sm transition-all duration-300"
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
                                                    class="h-2 w-2 rounded-xs bg-blue-600"></span> Estimasi Restock (Bln +1)</span>
                                            <span class="flex items-center gap-1.5"><span
                                                    class="h-2 w-2 rounded-xs bg-indigo-50 dark:bg-indigo-950/80 border border-dashed border-indigo-500 dark:border-indigo-400"></span>
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
                            payload integritas data.
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
                                        <span class="font-semibold text-blue-600 dark:text-blue-400">120 Unit</span>
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
                                             :class="['text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer shadow-sm', approvalStatus === 'approved' ? 'ring-2 ring-blue-400/50' : '']">
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
</template>
