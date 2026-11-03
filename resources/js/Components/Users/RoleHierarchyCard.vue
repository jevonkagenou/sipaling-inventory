<script setup lang="ts">
import { ref } from 'vue'
import {
  Shield,
  Briefcase,
  Boxes,
  FileSearch,
  ArrowRight,
  ChevronDown,
  ChevronUp,
  Layers,
  Workflow,
  CheckCircle2,
  Users,
} from 'lucide-vue-next'

const isOpen = ref(false)
const activeTab = ref<'hierarchy' | 'workflow'>('hierarchy')

const tiers = [
  {
    tierName: 'Tingkat Eksekutif & Manajerial (Tingkat Alpha)',
    tierBadge: 'Kepemimpinan & Otorisasi',
    tierCls: 'border-blue-200/80 dark:border-blue-900/60 bg-blue-50/30 dark:bg-blue-950/20',
    roles: [
      {
        id: 'komisaris',
        name: 'Komisaris',
        badge: 'Otoritas Tertinggi (Eksekutif)',
        icon: Shield,
        badgeCls: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800',
        dotCls: 'bg-indigo-500',
        responsibilities: [
          'Pengesahan akhir permohonan restock multi-item (PO) dari Manajer.',
          'Pemeriksaan kepatuhan finansial & ketersediaan anggaran perusahaan.',
          'Akses audit eksekutif atas seluruh histori mutasi inventaris.',
        ],
        reportingFlow: 'Menerima draf pengajuan restock dari Manajer Operasional & memberikan keputusan mutlak.',
      },
      {
        id: 'manajer-operasional',
        name: 'Manajer Operasional',
        badge: 'Manajerial & Strategi Lapangan',
        icon: Briefcase,
        badgeCls: 'bg-blue-50 text-[#2563EB] border-blue-200 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800',
        dotCls: 'bg-[#2563EB]',
        responsibilities: [
          'Menganalisis proyeksi permintaan sistem cerdas & indikator safety stock.',
          'Menyusun draf pengajuan tiket restock multi-item resmi ke Komisaris.',
          'Supervisi pembagian tugas staf gudang dan memantau pelaporan transaksi.',
        ],
        reportingFlow: 'Mengompilasi laporan stok fisik dari Staf Gudang, lalu menyusun rekomendasi restock ke Komisaris.',
      },
    ],
  },
  {
    tierName: 'Tingkat Operasional & Pengawasan (Tingkat Beta)',
    tierBadge: 'Eksekusi & Verifikasi Lapangan',
    tierCls: 'border-amber-200/80 dark:border-amber-900/60 bg-amber-50/20 dark:bg-amber-950/10',
    roles: [
      {
        id: 'staf-gudang',
        name: 'Staf Gudang',
        badge: 'Pelaksana Lapangan',
        icon: Boxes,
        badgeCls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800',
        dotCls: 'bg-amber-500',
        responsibilities: [
          'Mencatat mutasi fisik barang masuk (Inbound) dan barang keluar (Outbound).',
          'Menerima barang restock dari draf Inbound pasca-persetujuan Komisaris.',
          'Memastikan kesesuaian fisik stok di rak gudang dengan data sistem.',
        ],
        reportingFlow: 'Melaporkan kondisi stok kritis dan rekapitulasi mutasi fisik harian ke Manajer Operasional.',
      },
      {
        id: 'auditor-internal',
        name: 'Auditor Internal',
        badge: 'Pengawas Kepatuhan Independen',
        icon: FileSearch,
        badgeCls: 'bg-emerald-50 text-[#10B981] border-emerald-200 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800',
        dotCls: 'bg-[#10B981]',
        responsibilities: [
          'Investigasi seluruh rekam jejak mutasi tanpa hak memodifikasi data.',
          'Verifikasi integritas log ledger kriptografis anti-manipulasi (SHA-256).',
          'Mengekspor laporan audit berkala untuk kepatuhan tata kelola.',
        ],
        reportingFlow: 'Melaporkan temuan investigasi dan integritas data langsung ke jajaran pimpinan.',
      },
    ],
  },
]

const workflowSteps = [
  {
    step: '1',
    actor: 'Staf Gudang',
    action: 'Pencatatan Mutasi & Laporan Stok',
    desc: 'Staf mencatat transaksi mutasi barang masuk/keluar. Jika ada barang kritis di bawah safety stock, staf melaporkan kebutuhan ke Manajer.',
    icon: Boxes,
    color: 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-950/50 dark:border-amber-800',
  },
  {
    step: '2',
    actor: 'Manajer Operasional',
    action: 'Analisis Proyeksi & Pengajuan Restock',
    desc: 'Manajer meninjau tren permintaan sistem cerdas, menyusun draf Restock Multi-Item, dan mengajukannya ke meja Komisaris.',
    icon: Briefcase,
    color: 'text-[#2563EB] bg-blue-50 border-blue-200 dark:bg-blue-950/50 dark:border-blue-800',
  },
  {
    step: '3',
    actor: 'Komisaris',
    action: 'Keputusan Otorisasi Pengadaan',
    desc: 'Komisaris meninjau kelayakan finansial & memvalidasi volume item. Memberikan keputusan Setuju (Approve) atau Tolak (Reject).',
    icon: Shield,
    color: 'text-indigo-600 bg-indigo-50 border-indigo-200 dark:bg-indigo-950/50 dark:border-indigo-800',
  },
  {
    step: '4',
    actor: 'Sistem & Staf Gudang',
    action: 'Auto-Generate Draf Inbound & Eksekusi',
    desc: 'Pasca-persetujuan Komisaris, sistem otomatis menerbitkan draf transaksi Inbound. Staf gudang mengecek barang fisik saat tiba.',
    icon: CheckCircle2,
    color: 'text-[#10B981] bg-emerald-50 border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800',
  },
  {
    step: '5',
    actor: 'Auditor Internal',
    action: 'Verifikasi Independen Audit Ledger',
    desc: 'Seluruh rantai aktivitas tercatat otomatis dalam Audit Trail SHA-256 dan diawasi secara independen oleh Auditor Internal.',
    icon: FileSearch,
    color: 'text-slate-700 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300',
  },
]
</script>

<template>
  <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0F172A] shadow-2xs overflow-hidden transition-all">
    <!-- Header Collapsible -->
    <div
      class="p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40"
      :class="isOpen ? 'border-b border-slate-100 dark:border-slate-800' : ''"
      @click="isOpen = !isOpen"
    >
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-[#2563EB] dark:text-blue-400 flex items-center justify-center border border-blue-200/60 dark:border-blue-800/60 shrink-0">
          <Workflow class="h-4.5 w-4.5" />
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
              Struktur Hierarki Peran &amp; Alur Pelaporan Tim
            </h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-[#2563EB] dark:bg-blue-950 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800">
              Tata Kelola Terpadu
            </span>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
            Pembagian tanggung jawab berjenjang: Eksekutif (Komisaris &amp; Manajer) dan Operasional (Staf Gudang &amp; Auditor).
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 self-end sm:self-auto shrink-0" @click.stop>
        <!-- Toggle Mode Tab (Hanya tampil saat terbuka) -->
        <div v-if="isOpen" class="flex items-center rounded-xl bg-slate-100 dark:bg-slate-800/80 p-0.5 text-xs">
          <button
            type="button"
            @click="activeTab = 'hierarchy'"
            class="px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer"
            :class="activeTab === 'hierarchy' ? 'bg-white dark:bg-slate-900 text-[#2563EB] shadow-2xs font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
          >
            Tingkatan Peran
          </button>
          <button
            type="button"
            @click="activeTab = 'workflow'"
            class="px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer"
            :class="activeTab === 'workflow' ? 'bg-white dark:bg-slate-900 text-[#2563EB] shadow-2xs font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
          >
            Alur Tugas
          </button>
        </div>

        <button
          type="button"
          @click="isOpen = !isOpen"
          class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 inline-flex items-center gap-1.5 transition-colors cursor-pointer"
        >
          <span>{{ isOpen ? 'Tutup Panduan' : 'Lihat Detail Peran' }}</span>
          <ChevronUp v-if="isOpen" class="h-3.5 w-3.5 text-slate-400" />
          <ChevronDown v-else class="h-3.5 w-3.5 text-slate-400" />
        </button>
      </div>
    </div>

    <!-- Body View -->
    <div v-show="isOpen" class="p-4 sm:p-5 space-y-5">
      <!-- TAB 1: TAMPILAN TIER HIERARKI PERAN (ALPHA & BETA) -->
      <div v-if="activeTab === 'hierarchy'" class="space-y-4">
        <div
          v-for="tier in tiers"
          :key="tier.tierName"
          class="rounded-xl border p-4 space-y-3"
          :class="tier.tierCls"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Layers class="h-4 w-4 text-slate-700 dark:text-slate-300" />
              <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ tier.tierName }}
              </h4>
            </div>
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
              {{ tier.tierBadge }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div
              v-for="role in tier.roles"
              :key="role.id"
              class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-4 space-y-2.5 shadow-2xs hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="h-8 w-8 rounded-lg flex items-center justify-center border" :class="role.badgeCls">
                    <component :is="role.icon" class="h-4 w-4" />
                  </div>
                  <div>
                    <h5 class="text-xs font-bold text-slate-900 dark:text-white">
                      {{ role.name }}
                    </h5>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">
                      {{ role.badge }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Tanggung Jawab Utama -->
              <div class="space-y-1 pt-1 border-t border-slate-100 dark:border-slate-800/80">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Tugas &amp; Tanggung Jawab:</p>
                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                  <li v-for="(resp, i) in role.responsibilities" :key="i" class="flex items-start gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mt-1.5 shrink-0" />
                    <span>{{ resp }}</span>
                  </li>
                </ul>
              </div>

              <!-- Alur Pelaporan -->
              <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400 flex items-start gap-1.5">
                <ArrowRight class="h-3.5 w-3.5 text-[#2563EB] shrink-0 mt-0.5" />
                <span><strong class="text-slate-700 dark:text-slate-300">Alur Koordinasi:</strong> {{ role.reportingFlow }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: ALUR KERJA TIM & PELAPORAN BERJENJANG -->
      <div v-else class="space-y-3">
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Siklus kolaborasi terstruktur dari pelaporan lapangan di gudang hingga pengesahan manajerial:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-1">
          <div
            v-for="(wf, idx) in workflowSteps"
            :key="wf.step"
            class="relative rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 space-y-2 shadow-2xs flex flex-col justify-between"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="h-6 w-6 rounded-full flex items-center justify-center text-xs font-bold border" :class="wf.color">
                  {{ wf.step }}
                </span>
                <component :is="wf.icon" class="h-4 w-4 text-slate-400" />
              </div>

              <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">
                  {{ wf.actor }}
                </span>
                <h5 class="text-xs font-bold text-slate-900 dark:text-white leading-snug">
                  {{ wf.action }}
                </h5>
              </div>

              <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ wf.desc }}
              </p>
            </div>

            <div v-if="idx < workflowSteps.length - 1" class="hidden lg:block absolute -right-3 top-1/2 -translate-y-1/2 z-10">
              <div class="h-6 w-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center border border-slate-200 dark:border-slate-700 shadow-2xs">
                <ArrowRight class="h-3 w-3" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
