<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogFooter,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import {
  Shield,
  Briefcase,
  Boxes,
  FileSearch,
  Eye,
  EyeOff,
  AlertCircle,
  CheckCircle2,
  Lock,
  Mail,
  User as UserIcon,
  Phone,
  Power,
} from 'lucide-vue-next'

export interface UserItem {
  id?: string
  name: string
  email: string
  phone?: string | null
  primary_role: string
  is_active: boolean
}

export interface RoleOption {
  id: string
  name: string
  label: string
}

const props = defineProps<{
  open: boolean
  mode: 'create' | 'edit'
  user?: UserItem | null
  roles: RoleOption[]
  currentAuthUserId?: string
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved', message?: string): void
}>()

const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const clientErrors = ref<Record<string, string>>({})

const form = useForm({
  name: '',
  email: '',
  phone: '',
  role: 'staf-gudang',
  password: '',
  password_confirmation: '',
  is_active: true,
})

const isEditingSelf = computed(() => {
  return props.mode === 'edit' && props.user?.id === props.currentAuthUserId
})

const roleOptionsWithMeta = [
  {
    name: 'komisaris',
    label: 'Komisaris',
    desc: 'Otorisasi tertinggi persetujuan restock & monitoring jejak audit.',
    icon: Shield,
    badgeCls: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800',
    dotCls: 'bg-indigo-500',
  },
  {
    name: 'manajer-operasional',
    label: 'Manajer Operasional',
    desc: 'Manajemen master katalog, pengajuan restock, & analitik DES.',
    icon: Briefcase,
    badgeCls: 'bg-blue-50 text-[#2563EB] border-blue-200 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800',
    dotCls: 'bg-[#2563EB]',
  },
  {
    name: 'staf-gudang',
    label: 'Staf Gudang',
    desc: 'Pencatatan mutasi barang masuk & keluar serta verifikasi stok fisik.',
    icon: Boxes,
    badgeCls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800',
    dotCls: 'bg-amber-500',
  },
  {
    name: 'auditor-internal',
    label: 'Auditor Internal',
    desc: 'Investigasi log aktivitas, verifikasi integritas data, & ekspor laporan.',
    icon: FileSearch,
    badgeCls: 'bg-emerald-50 text-[#10B981] border-emerald-200 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800',
    dotCls: 'bg-[#10B981]',
  },
]

function resetForm() {
  clientErrors.value = {}
  showPassword.value = false
  showPasswordConfirm.value = false

  if (props.mode === 'edit' && props.user) {
    form.name = props.user.name || ''
    form.email = props.user.email || ''
    form.phone = props.user.phone || ''
    form.role = props.user.primary_role || 'staf-gudang'
    form.password = ''
    form.password_confirmation = ''
    form.is_active = props.user.is_active
  } else {
    form.reset()
    form.role = 'staf-gudang'
    form.is_active = true
  }
  form.clearErrors()
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) resetForm()
  },
)

const title = computed(() => (props.mode === 'create' ? 'Tambah Pengguna Baru' : 'Edit Data Pengguna'))
const submitLabel = computed(() => (props.mode === 'create' ? 'Daftarkan Pengguna' : 'Simpan Perubahan'))

// Client-Side Validation
function validateClientSide(): boolean {
  const errors: Record<string, string> = {}

  if (!form.name.trim()) {
    errors.name = 'Nama lengkap wajib diisi.'
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!form.email.trim()) {
    errors.email = 'Alamat email wajib diisi.'
  } else if (!emailRegex.test(form.email)) {
    errors.email = 'Format alamat email tidak valid (contoh: nama@sipaling.com).'
  }

  if (form.phone && form.phone.trim()) {
    const phoneRegex = /^[0-9+\-\s()]{8,20}$/
    if (!phoneRegex.test(form.phone)) {
      errors.phone = 'Nomor telepon harus berupa angka (8-20 digit).'
    }
  }

  if (!form.role) {
    errors.role = 'Pilih salah satu peran pengguna.'
  }

  if (props.mode === 'create') {
    if (!form.password) {
      errors.password = 'Kata sandi wajib diisi.'
    } else if (form.password.length < 8) {
      errors.password = 'Kata sandi minimal terdiri dari 8 karakter.'
    }

    if (form.password !== form.password_confirmation) {
      errors.password_confirmation = 'Konfirmasi kata sandi tidak cocok.'
    }
  } else {
    // Edit mode: Password optional
    if (form.password) {
      if (form.password.length < 8) {
        errors.password = 'Kata sandi baru minimal 8 karakter.'
      }
      if (form.password !== form.password_confirmation) {
        errors.password_confirmation = 'Konfirmasi kata sandi baru tidak cocok.'
      }
    }
  }

  clientErrors.value = errors
  return Object.keys(errors).length === 0
}

function handleClose(value: boolean) {
  if (!form.processing) emit('update:open', value)
}

function submit() {
  if (!validateClientSide()) return

  if (props.mode === 'create') {
    form.post(route('users.store'), {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('saved', `Pengguna ${form.name} berhasil didaftarkan.`)
      },
    })
  } else if (props.user?.id) {
    form.put(route('users.update', props.user.id), {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        emit('saved', `Data pengguna ${form.name} berhasil diperbarui.`)
      },
    })
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-2xl max-h-[90vh] flex flex-col p-0 overflow-hidden bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800">
      <!-- Modal Header -->
      <DialogHeader class="p-5 pb-4 border-b border-slate-100 dark:border-slate-800/80">
        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400">
            <UserIcon class="h-4 w-4" />
          </div>
          {{ title }}
        </DialogTitle>
        <DialogDescription class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Konfigurasikan profil pengguna, hak akses peran Spatie, dan status aktivasi akun sistem.
        </DialogDescription>
      </DialogHeader>

      <!-- Modal Body (Scrollable for Mobile) -->
      <div class="flex-1 overflow-y-auto p-5 space-y-5 custom-scrollbar">
        <!-- Global Form Errors from Backend -->
        <div
          v-if="form.errors.general || form.errors.error"
          class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2"
        >
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold">Terjadi Kesalahan</p>
            <p>{{ form.errors.general || form.errors.error }}</p>
          </div>
        </div>

        <form id="userForm" class="space-y-4" @submit.prevent="submit">
          <!-- Section 1: Data Identitas Dasar -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
              Identitas Pengguna
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <!-- Nama Lengkap -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                  <UserIcon class="w-3.5 h-3.5 text-slate-400" />
                  Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <Input
                  v-model="form.name"
                  placeholder="Contoh: Budi Pratama"
                  class="text-xs rounded-xl h-9.5 bg-slate-50/70 dark:bg-slate-900"
                  :class="{ 'border-rose-500 focus-visible:ring-rose-500': clientErrors.name || form.errors.name }"
                />
                <p v-if="clientErrors.name || form.errors.name" class="text-[11px] text-rose-500 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3 shrink-0" />
                  {{ clientErrors.name || form.errors.name }}
                </p>
              </div>

              <!-- Alamat Email -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                  <Mail class="w-3.5 h-3.5 text-slate-400" />
                  Alamat Email <span class="text-rose-500">*</span>
                </label>
                <Input
                  v-model="form.email"
                  type="email"
                  placeholder="nama@sipaling.com"
                  class="text-xs rounded-xl h-9.5 bg-slate-50/70 dark:bg-slate-900"
                  :class="{ 'border-rose-500 focus-visible:ring-rose-500': clientErrors.email || form.errors.email }"
                />
                <p v-if="clientErrors.email || form.errors.email" class="text-[11px] text-rose-500 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3 shrink-0" />
                  {{ clientErrors.email || form.errors.email }}
                </p>
              </div>
            </div>

            <!-- Nomor Telepon / WA -->
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                <Phone class="w-3.5 h-3.5 text-slate-400" />
                Nomor Telepon / WhatsApp <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <Input
                v-model="form.phone"
                placeholder="Contoh: 081234567890"
                class="text-xs rounded-xl h-9.5 bg-slate-50/70 dark:bg-slate-900"
                :class="{ 'border-rose-500 focus-visible:ring-rose-500': clientErrors.phone || form.errors.phone }"
              />
              <p v-if="clientErrors.phone || form.errors.phone" class="text-[11px] text-rose-500 flex items-center gap-1">
                <AlertCircle class="w-3 h-3 shrink-0" />
                {{ clientErrors.phone || form.errors.phone }}
              </p>
            </div>
          </div>

          <!-- Section 2: Pemilihan Peran & Hak Akses (Stitch AI Color Badges) -->
          <div class="space-y-2.5 pt-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                Peran Pengguna (Spatie RBAC) <span class="text-rose-500">*</span>
              </h4>
              <span class="text-[11px] text-slate-400">Pilih satu hak akses</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div
                v-for="r in roleOptionsWithMeta"
                :key="r.name"
                @click="form.role = r.name"
                :class="[
                  'p-3 rounded-xl border transition-all cursor-pointer flex flex-col justify-between space-y-1.5 relative select-none',
                  form.role === r.name
                    ? 'border-[#2563EB] bg-blue-50/40 dark:bg-blue-950/30 ring-2 ring-[#2563EB]/40 shadow-xs'
                    : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 hover:border-slate-300 dark:hover:border-slate-700',
                ]"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold border uppercase tracking-wider', r.badgeCls]">
                      <span :class="['w-1.5 h-1.5 rounded-full', r.dotCls]" />
                      {{ r.label }}
                    </span>
                  </div>
                  <div
                    :class="[
                      'w-4 h-4 rounded-full border flex items-center justify-center transition-colors',
                      form.role === r.name
                        ? 'border-[#2563EB] bg-[#2563EB] text-white'
                        : 'border-slate-300 dark:border-slate-700',
                    ]"
                  >
                    <CheckCircle2 v-if="form.role === r.name" class="w-3.5 h-3.5" />
                  </div>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                  {{ r.desc }}
                </p>
              </div>
            </div>
            <p v-if="clientErrors.role || form.errors.role" class="text-[11px] text-rose-500 flex items-center gap-1">
              <AlertCircle class="w-3 h-3 shrink-0" />
              {{ clientErrors.role || form.errors.role }}
            </p>
          </div>

          <!-- Section 3: Keamanan & Kata Sandi -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                Keamanan &amp; Autentikasi
              </h4>
              <span v-if="mode === 'edit'" class="text-[11px] text-slate-400 italic">
                Kosongkan jika tidak ingin mengubah kata sandi
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <!-- Kata Sandi -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                  <Lock class="w-3.5 h-3.5 text-slate-400" />
                  Kata Sandi
                  <span v-if="mode === 'create'" class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <Input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Minimal 8 karakter"
                    class="text-xs rounded-xl h-9.5 pr-8.5 bg-slate-50/70 dark:bg-slate-900"
                    :class="{ 'border-rose-500 focus-visible:ring-rose-500': clientErrors.password || form.errors.password }"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                  >
                    <EyeOff v-if="showPassword" class="h-3.5 w-3.5" />
                    <Eye v-else class="h-3.5 w-3.5" />
                  </button>
                </div>
                <p v-if="clientErrors.password || form.errors.password" class="text-[11px] text-rose-500 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3 shrink-0" />
                  {{ clientErrors.password || form.errors.password }}
                </p>
              </div>

              <!-- Konfirmasi Kata Sandi -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                  <Lock class="w-3.5 h-3.5 text-slate-400" />
                  Konfirmasi Kata Sandi
                  <span v-if="mode === 'create' || form.password" class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <Input
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    placeholder="Ulangi kata sandi"
                    class="text-xs rounded-xl h-9.5 pr-8.5 bg-slate-50/70 dark:bg-slate-900"
                    :class="{ 'border-rose-500 focus-visible:ring-rose-500': clientErrors.password_confirmation || form.errors.password_confirmation }"
                  />
                  <button
                    type="button"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                  >
                    <EyeOff v-if="showPasswordConfirm" class="h-3.5 w-3.5" />
                    <Eye v-else class="h-3.5 w-3.5" />
                  </button>
                </div>
                <p v-if="clientErrors.password_confirmation || form.errors.password_confirmation" class="text-[11px] text-rose-500 flex items-center gap-1">
                  <AlertCircle class="w-3 h-3 shrink-0" />
                  {{ clientErrors.password_confirmation || form.errors.password_confirmation }}
                </p>
              </div>
            </div>
          </div>

          <!-- Section 4: Pengaturan Status Aktivasi Akun (Target Output Deliverable) -->
          <div class="pt-2">
            <div
              :class="[
                'p-3.5 rounded-2xl border transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                form.is_active
                  ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-200/80 dark:border-emerald-800/60'
                  : 'bg-rose-50/40 dark:bg-rose-950/20 border-rose-200/80 dark:border-rose-800/60',
              ]"
            >
              <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                  <Power class="w-4 h-4" :class="form.is_active ? 'text-[#10B981]' : 'text-rose-500'" />
                  <span class="text-xs font-bold text-slate-900 dark:text-white">
                    Status Aktivasi Akun
                  </span>
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-bold border uppercase tracking-wider',
                      form.is_active
                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/60 dark:text-emerald-200 dark:border-emerald-700'
                        : 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/60 dark:text-rose-200 dark:border-rose-700',
                    ]"
                  >
                    <span :class="['w-1.5 h-1.5 rounded-full', form.is_active ? 'bg-[#10B981]' : 'bg-rose-500']" />
                    {{ form.is_active ? 'Aktif' : 'Dinonaktifkan' }}
                  </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                  {{
                    form.is_active
                      ? 'Pengguna dapat masuk (login) dan menjalankan fungsi operasional sesuai peran.'
                      : 'Akses masuk pengguna diblokir oleh sistem hingga status diaktifkan kembali.'
                  }}
                </p>
                <p v-if="isEditingSelf && !form.is_active" class="text-[11px] text-rose-600 font-semibold mt-1">
                  * Peringatan: Anda tidak dapat menonaktifkan akun sendiri yang sedang aktif digunakan.
                </p>
              </div>

              <!-- Interactive Toggle Switch -->
              <button
                type="button"
                :disabled="isEditingSelf"
                @click="form.is_active = !form.is_active"
                :class="[
                  'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#2563EB] disabled:opacity-50 disabled:cursor-not-allowed',
                  form.is_active ? 'bg-[#10B981]' : 'bg-slate-300 dark:bg-slate-700',
                ]"
              >
                <span
                  aria-hidden="true"
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                    form.is_active ? 'translate-x-5' : 'translate-x-0',
                  ]"
                />
              </button>
            </div>
            <p v-if="form.errors.is_active" class="text-[11px] text-rose-500 mt-1 flex items-center gap-1">
              <AlertCircle class="w-3 h-3 shrink-0" />
              {{ form.errors.is_active }}
            </p>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <DialogFooter class="p-4 bg-slate-50/70 dark:bg-slate-900/70 border-t border-slate-100 dark:border-slate-800/80 flex sm:justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          :disabled="form.processing"
          @click="handleClose(false)"
          class="text-xs rounded-xl border-slate-200 dark:border-slate-800 cursor-pointer"
        >
          Batal
        </Button>
        <Button
          type="submit"
          form="userForm"
          :disabled="form.processing"
          class="text-xs rounded-xl bg-gradient-to-r from-[#2563EB] to-[#1D4ED8] hover:from-[#1D4ED8] hover:to-[#1E40AF] text-white shadow-md shadow-blue-500/20 cursor-pointer transition-all active:scale-95"
        >
          {{ form.processing ? 'Memproses...' : submitLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
