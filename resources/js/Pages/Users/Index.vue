<script setup lang="ts">
import { h, ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  columnFilteringFeature,
  columnVisibilityFeature,
  createColumnHelper,
  createExpandedRowModel,
  createFilteredRowModel,
  createPaginatedRowModel,
  createSortedRowModel,
  filterFn_includesString,
  FlexRender,
  rowExpandingFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  sortFn_alphanumeric,
  sortFn_text,
  tableFeatures,
  useTable,
} from '@tanstack/vue-table'
import { createReusableTemplate } from '@vueuse/core'
import { cn } from '@/lib/utils'

// Shadcn UI Components
import { Button } from '@/Components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'

// Modular User Management Components
import UserStatsCards, { type StatusFilterKey } from '@/Components/Users/UserStatsCards.vue'
import UserToolbar, { type RoleOption } from '@/Components/Users/UserToolbar.vue'
import UserFormDialog, { type UserItem } from '@/Components/Users/UserFormDialog.vue'
import UserStatusDialog from '@/Components/Users/UserStatusDialog.vue'
import UserDeleteDialog from '@/Components/Users/UserDeleteDialog.vue'
import UserToast, { type ToastState } from '@/Components/Users/UserToast.vue'

// Lucide Icons
import {
  ArrowUpDown,
  MoreHorizontal,
  Pencil,
  Trash2,
  Copy,
  Check,
  Power,
  Shield,
  Briefcase,
  Boxes,
  FileSearch,
  User as UserIcon,
  Phone,
  Mail,
  Calendar,
  Users,
  Inbox,
} from 'lucide-vue-next'

export interface UserRowData {
  id: string
  name: string
  email: string
  phone?: string | null
  is_active: boolean
  email_verified_at?: string | null
  created_at?: string
  roles: string[]
  primary_role: string
}

const props = defineProps<{
  users: UserRowData[]
  roles: RoleOption[]
  filters: {
    search?: string
    role?: string
    status?: string
  }
  stats: {
    total_users: number
    active_users: number
    inactive_users: number
    role_counts?: {
      komisaris: number
      'manajer-operasional': number
      'staf-gudang': number
      'auditor-internal': number
    }
  }
}>()

const page = usePage()
const currentAuthUserId = computed(() => page.props.auth?.user?.id)

// Toast Notification Management
const toast = ref<ToastState | null>(null)
let toastTimer: any = null

function showToast(title: string, description?: string, type: 'success' | 'error' = 'success') {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { id: Date.now(), title, description, type }
  toastTimer = setTimeout(() => {
    toast.value = null
  }, 3500)
}

// Watch for flash messages from backend
watch(
  () => page.props.flash,
  (flash: any) => {
    if (flash?.success) {
      showToast('Berhasil', flash.success, 'success')
    } else if (flash?.error) {
      showToast('Gagal', flash.error, 'error')
    }
  },
  { deep: true }
)

// Copy UUID Helper
const copiedId = ref<string | null>(null)
function copyUuid(user: UserRowData) {
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(user.id)
    copiedId.value = user.id
    showToast(
      'Kode UUID Disalin!',
      `ID pengguna untuk "${user.name}" (${user.email}) telah tersimpan di clipboard.`
    )
    setTimeout(() => {
      copiedId.value = null
    }, 2000)
  }
}

// Format Date
function formatDate(dateStr?: string) {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(d)
  } catch {
    return dateStr
  }
}

// Role Badge Helper (Stitch AI Color Consistency)
function getRoleBadge(role: string) {
  switch (role) {
    case 'komisaris':
      return {
        label: 'Komisaris',
        icon: Shield,
        cls: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60',
        dot: 'bg-indigo-500',
      }
    case 'manajer-operasional':
      return {
        label: 'Manajer Ops',
        icon: Briefcase,
        cls: 'bg-blue-50 text-[#2563EB] border-blue-200/80 dark:bg-blue-950/70 dark:text-blue-300 dark:border-blue-800/60',
        dot: 'bg-[#2563EB]',
      }
    case 'staf-gudang':
      return {
        label: 'Staf Gudang',
        icon: Boxes,
        cls: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800/60',
        dot: 'bg-amber-500',
      }
    case 'auditor-internal':
      return {
        label: 'Auditor Internal',
        icon: FileSearch,
        cls: 'bg-emerald-50 text-[#10B981] border-emerald-200/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800/60',
        dot: 'bg-[#10B981]',
      }
    default:
      return {
        label: role,
        icon: UserIcon,
        cls: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        dot: 'bg-slate-400',
      }
  }
}

// Dialog States
const isFormDialogOpen = ref(false)
const formDialogMode = ref<'create' | 'edit'>('create')
const selectedUser = ref<UserRowData | null>(null)

function openCreateDialog() {
  formDialogMode.value = 'create'
  selectedUser.value = null
  isFormDialogOpen.value = true
}

function openEditDialog(user: UserRowData) {
  formDialogMode.value = 'edit'
  selectedUser.value = user
  isFormDialogOpen.value = true
}

const isStatusDialogOpen = ref(false)
const statusTargetUser = ref<UserRowData | null>(null)

function openStatusDialog(user: UserRowData) {
  statusTargetUser.value = user
  isStatusDialogOpen.value = true
}

const isDeleteDialogOpen = ref(false)
const deleteTargetUser = ref<UserRowData | null>(null)

function openDeleteDialog(user: UserRowData) {
  deleteTargetUser.value = user
  isDeleteDialogOpen.value = true
}

function onUserSaved(msg?: string) {
  router.reload({ only: ['users', 'stats'] })
  showToast('Data Pengguna Tersimpan', msg || 'Perubahan akun pengguna berhasil diterapkan.')
}

function onStatusChanged() {
  router.reload({ only: ['users', 'stats'] })
  showToast('Status Diperbarui', 'Status aktivasi akun berhasil diubah.')
}

function onUserDeleted() {
  router.reload({ only: ['users', 'stats'] })
  showToast('Pengguna Dihapus', 'Akun pengguna berhasil dihapus dari sistem.')
}

// Filter States
const searchQuery = ref(props.filters.search || '')
const activeRoleFilter = ref(props.filters.role || 'all')
const activeStatusFilter = ref<StatusFilterKey>((props.filters.status as StatusFilterKey) || 'all')

// Reusable Action Template
const [DefineActionTemplate, ReuseActionTemplate] = createReusableTemplate<{
  user: UserRowData
}>()

// TanStack Table Features & Setup
const features = tableFeatures({
  columnFilteringFeature,
  columnVisibilityFeature,
  rowExpandingFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  expandedRowModel: createExpandedRowModel(),
  filteredRowModel: createFilteredRowModel(),
  paginatedRowModel: createPaginatedRowModel(),
  sortedRowModel: createSortedRowModel(),
  filterFns: { includesString: filterFn_includesString },
  sortFns: { alphanumeric: sortFn_alphanumeric, text: sortFn_text },
})

const columnHelper = createColumnHelper<typeof features, UserRowData>()

const columns = columnHelper.columns([
  // 1. Profil Pengguna (Avatar + Nama + Email + UUID)
  columnHelper.accessor('name', {
    header: ({ column }) =>
      h(
        Button,
        {
          variant: 'ghost',
          class:
            '-ml-2 h-7 px-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['Pengguna', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
      ),
    cell: ({ row }) => {
      const u = row.original
      const initials = (u.name || 'U').slice(0, 2).toUpperCase()
      const isSelf = u.id === currentAuthUserId.value

      return h('div', { class: 'flex items-center gap-3 min-w-[180px] max-w-[260px]' }, [
        h(
          'div',
          {
            class:
              'h-8 w-8 rounded-lg bg-gradient-to-tr from-[#2563EB] to-[#4F46E5] text-white flex items-center justify-center font-bold text-[11px] shadow-xs shrink-0',
          },
          initials
        ),
        h('div', { class: 'min-w-0 flex-1' }, [
          h('div', { class: 'flex items-center gap-1.5' }, [
            h(
              'span',
              { class: 'font-semibold text-xs text-slate-900 dark:text-slate-100 truncate', title: u.name },
              u.name
            ),
            isSelf
              ? h(
                  'span',
                  {
                    class:
                      'px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 uppercase tracking-wider',
                  },
                  'Anda'
                )
              : null,
          ]),
          h(
            'p',
            { class: 'text-[11px] text-slate-500 dark:text-slate-400 font-mono truncate', title: u.email },
            u.email
          ),
        ]),
      ])
    },
  }),

  // 2. Peran / Hak Akses (Role Badge)
  columnHelper.accessor('primary_role', {
    header: () =>
      h(
        'div',
        { class: 'text-left font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Peran / Akses'
      ),
    cell: ({ row }) => {
      const badge = getRoleBadge(row.getValue('primary_role'))
      return h(
        'span',
        {
          class: cn(
            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border uppercase tracking-wider whitespace-nowrap',
            badge.cls
          ),
        },
        [
          h(badge.icon, { class: 'w-3 h-3 shrink-0' }),
          badge.label,
        ]
      )
    },
  }),

  // 3. Kontak / Nomor HP
  columnHelper.accessor('phone', {
    header: () =>
      h(
        'div',
        { class: 'text-left font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Kontak'
      ),
    cell: ({ row }) => {
      const phone = row.getValue('phone') as string | null
      if (!phone) {
        return h('span', { class: 'text-slate-400 text-xs italic' }, 'Belum diatur')
      }
      return h('div', { class: 'flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 font-mono whitespace-nowrap' }, [
        h(Phone, { class: 'w-3 h-3 text-slate-400' }),
        phone,
      ])
    },
  }),

  // 4. Tanggal Terdaftar
  columnHelper.accessor('created_at', {
    header: ({ column }) =>
      h(
        Button,
        {
          variant: 'ghost',
          class:
            '-ml-2 h-7 px-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        () => ['Terdaftar', h(ArrowUpDown, { class: 'ml-1 h-3 w-3 text-slate-400' })],
      ),
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap' }, [
        h(Calendar, { class: 'w-3.5 h-3.5 text-slate-400' }),
        formatDate(row.getValue('created_at')),
      ])
    },
  }),

  // 5. Status Aktivasi Akun (Target Output Deliverable)
  columnHelper.accessor('is_active', {
    header: () =>
      h(
        'div',
        { class: 'text-center font-semibold text-slate-700 dark:text-slate-300 text-xs' },
        'Aktivasi Akun'
      ),
    cell: ({ row }) => {
      const isActive = row.getValue('is_active') as boolean
      const user = row.original
      const isSelf = user.id === currentAuthUserId.value

      return h('div', { class: 'flex items-center justify-center' }, [
        h(
          'button',
          {
            type: 'button',
            disabled: isSelf,
            onClick: () => openStatusDialog(user),
            title: isSelf ? 'Anda tidak dapat menonaktifkan akun sendiri' : 'Klik untuk mengubah status aktivasi',
            class: cn(
              'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border transition-all cursor-pointer select-none',
              isActive
                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/80 hover:bg-emerald-100'
                : 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/80 hover:bg-rose-100',
              isSelf && 'opacity-70 cursor-not-allowed'
            ),
          },
          [
            h('span', {
              class: cn('w-1.5 h-1.5 rounded-full', isActive ? 'bg-[#10B981]' : 'bg-rose-500'),
            }),
            isActive ? 'Aktif' : 'Nonaktif',
          ]
        ),
      ])
    },
  }),

  // 6. Aksi
  columnHelper.display({
    id: 'actions',
    enableHiding: false,
    header: () =>
      h(
        'div',
        { class: 'text-right font-semibold text-slate-700 dark:text-slate-300 text-xs pr-2' },
        'Aksi'
      ),
    cell: ({ row }) => {
      return h(ReuseActionTemplate, {
        user: row.original,
      })
    },
  }),
])

// Filtered Data Source
const filteredUsers = computed(() => {
  return props.users.filter((user) => {
    // 1. Search Query
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const matchName = user.name.toLowerCase().includes(q)
      const matchEmail = user.email.toLowerCase().includes(q)
      const matchPhone = user.phone ? user.phone.toLowerCase().includes(q) : false
      if (!matchName && !matchEmail && !matchPhone) return false
    }

    // 2. Role Filter
    if (activeRoleFilter.value !== 'all') {
      if (user.primary_role !== activeRoleFilter.value && !user.roles.includes(activeRoleFilter.value)) {
        return false
      }
    }

    // 3. Status Filter
    if (activeStatusFilter.value === '1') {
      if (!user.is_active) return false
    } else if (activeStatusFilter.value === '0') {
      if (user.is_active) return false
    }

    return true
  })
})

const table = useTable({
  features,
  data: filteredUsers,
  columns,
})

function handleStatusFilterChange(status: StatusFilterKey) {
  activeStatusFilter.value = status
}

function handleRoleFilterChange(role: string) {
  activeRoleFilter.value = role
}

function resetAllFilters() {
  searchQuery.value = ''
  activeRoleFilter.value = 'all'
  activeStatusFilter.value = 'all'
}
</script>

<template>
  <Head title="Manajemen Pengguna & Aktivasi Akun - SIPALING" />

  <!-- Action Dropdown Reusable Template -->
  <DefineActionTemplate v-slot="{ user }">
    <div class="flex items-center justify-end gap-1">
      <!-- Quick Action: Edit Profile -->
      <Button
        variant="ghost"
        size="icon"
        class="h-7 w-7 rounded-lg text-slate-500 hover:text-[#2563EB] hover:bg-blue-50 dark:hover:bg-blue-950/60 cursor-pointer transition-colors"
        title="Edit Data Pengguna"
        @click.stop="openEditDialog(user)"
      >
        <span class="sr-only">Edit Pengguna</span>
        <Pencil class="h-3.5 w-3.5" />
      </Button>

      <!-- More Actions Dropdown -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            class="h-7 w-7 p-0 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
            title="Menu Tindakan Lainnya"
          >
            <span class="sr-only">Buka menu aksi</span>
            <MoreHorizontal class="h-3.5 w-3.5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-48 bg-white dark:bg-[#0F172A] border-slate-200 dark:border-slate-800 shadow-md">
          <DropdownMenuLabel class="text-xs text-slate-400 font-normal">Tindakan Akun</DropdownMenuLabel>
          
          <DropdownMenuItem
            class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs flex items-center"
            @click="copyUuid(user)"
          >
            <Check v-if="copiedId === user.id" class="mr-2 h-3.5 w-3.5 text-emerald-500" />
            <Copy v-else class="mr-2 h-3.5 w-3.5 text-slate-400" />
            <span>{{ copiedId === user.id ? 'UUID Tersalin' : 'Salin Kode UUID' }}</span>
          </DropdownMenuItem>

          <DropdownMenuItem
            class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs flex items-center"
            @click="openEditDialog(user)"
          >
            <Pencil class="mr-2 h-3.5 w-3.5 text-slate-400" />
            <span>Edit Profil &amp; Peran</span>
          </DropdownMenuItem>

          <DropdownMenuItem
            v-if="user.id !== currentAuthUserId"
            class="cursor-pointer text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs flex items-center"
            @click="openStatusDialog(user)"
          >
            <Power class="mr-2 h-3.5 w-3.5" :class="user.is_active ? 'text-rose-500' : 'text-[#10B981]'" />
            <span>{{ user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}</span>
          </DropdownMenuItem>

          <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />

          <DropdownMenuItem
            v-if="user.id !== currentAuthUserId"
            @click="openDeleteDialog(user)"
            class="cursor-pointer text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs flex items-center"
          >
            <Trash2 class="mr-2 h-3.5 w-3.5 text-rose-500" />
            <span>Hapus Pengguna</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </DefineActionTemplate>

  <AuthenticatedLayout>
    <!-- Header Slot (Breadcrumbs) -->
    <template #header>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-400">Tata Kelola</span>
        <span class="text-slate-300 dark:text-slate-700">/</span>
        <h1 class="text-sm font-bold text-slate-900 dark:text-white truncate">
          Manajemen Pengguna
        </h1>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Page Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Manajemen Pengguna &amp; Aktivasi Akun
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Kelola data staf operasional, tetapkan hak akses peran sistem, dan kontrol aktivasi akun terpusat.
          </p>
        </div>
      </div>

      <!-- 1. KPI Statistics Cards Component -->
      <UserStatsCards
        :stats="stats"
        :active-status-filter="activeStatusFilter"
        :active-role-filter="activeRoleFilter"
        @filter-status="handleStatusFilterChange"
        @filter-role="handleRoleFilterChange"
      />

      <!-- 2. Toolbar Component (Search, Role Filter, & Create Button) -->
      <UserToolbar
        :search-query="searchQuery"
        :active-role-filter="activeRoleFilter"
        :roles="roles"
        @update:search-query="searchQuery = $event"
        @update:active-role-filter="activeRoleFilter = $event"
        @create="openCreateDialog"
      />

      <!-- 3. Desktop TanStack Data Table View (Visible on >= md screens) -->
      <div class="hidden md:block rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <Table>
          <TableHeader class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800">
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="hover:bg-transparent">
              <TableHead
                v-for="header in headerGroup.headers"
                :key="header.id"
                :class="cn(
                  'h-10 px-2.5 sm:px-3 text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap',
                  header.column.id === 'actions' && 'sticky right-0 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-xs z-20 text-right pr-3 shadow-[-4px_0_8px_rgba(0,0,0,0.03)] border-l border-slate-200 dark:border-slate-800'
                )"
              >
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="table.getRowModel().rows.length">
              <TableRow
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                class="group border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <TableCell
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  :class="cn(
                    'px-2.5 sm:px-3 py-2.5 text-xs',
                    cell.column.id === 'actions' && 'sticky right-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/80 transition-colors z-10 text-right pr-3 shadow-[-4px_0_8px_rgba(0,0,0,0.03)] border-l border-slate-100 dark:border-slate-800/80'
                  )"
                >
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                  />
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableCell :colspan="columns.length" class="h-44 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 py-8">
                    <Inbox class="w-10 h-10 mb-2 stroke-[1.5]" />
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada pengguna yang cocok</p>
                    <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau sesuaikan filter peran dan status.</p>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="resetAllFilters"
                      class="mt-3 text-xs rounded-xl border-slate-200 dark:border-slate-800 cursor-pointer"
                    >
                      Reset Semua Filter
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>

        <!-- Table Pagination & Total Counter -->
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ table.getFilteredRowModel().rows.length }}</span> dari {{ users.length }} pengguna
          </div>
          <div class="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="!table.getCanPreviousPage()"
              @click="table.previousPage()"
              class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 rounded-xl cursor-pointer disabled:opacity-40 h-8 px-3"
            >
              Sebelumnya
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="!table.getCanNextPage()"
              @click="table.nextPage()"
              class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 rounded-xl cursor-pointer disabled:opacity-40 h-8 px-3"
            >
              Berikutnya
            </Button>
          </div>
        </div>
      </div>

      <!-- 4. Mobile Adaptive Cards View (Visible on < md screens) -->
      <div class="block md:hidden space-y-3">
        <template v-if="filteredUsers.length">
          <div
            v-for="u in filteredUsers"
            :key="u.id"
            class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0F172A] shadow-2xs space-y-3"
          >
            <!-- Card Header: Avatar, Name, Email & Role -->
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-[#2563EB] to-[#4F46E5] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                  {{ (u.name || 'U').slice(0, 2).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5">
                    <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">
                      {{ u.name }}
                    </h4>
                    <span
                      v-if="u.id === currentAuthUserId"
                      class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 uppercase"
                    >
                      Anda
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-400 font-mono truncate">
                    {{ u.email }}
                  </p>
                </div>
              </div>

              <!-- Role Badge -->
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border uppercase tracking-wider shrink-0',
                  getRoleBadge(u.primary_role).cls,
                ]"
              >
                <component :is="getRoleBadge(u.primary_role).icon" class="w-3 h-3 shrink-0" />
                {{ getRoleBadge(u.primary_role).label }}
              </span>
            </div>

            <!-- Card Body: Contact & Date -->
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400">
              <div class="flex items-center gap-1.5 truncate font-mono">
                <Phone class="w-3 h-3 text-slate-400 shrink-0" />
                <span>{{ u.phone || 'Belum diatur' }}</span>
              </div>
              <div class="flex items-center gap-1.5 justify-end">
                <Calendar class="w-3 h-3 text-slate-400 shrink-0" />
                <span>{{ formatDate(u.created_at) }}</span>
              </div>
            </div>

            <!-- Card Footer: Status Switch & Actions -->
            <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
              <!-- Activation Status Pill -->
              <button
                type="button"
                :disabled="u.id === currentAuthUserId"
                @click="openStatusDialog(u)"
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all cursor-pointer',
                  u.is_active
                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-[#10B981] dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/80'
                    : 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/80',
                  u.id === currentAuthUserId && 'opacity-70 cursor-not-allowed',
                ]"
              >
                <span :class="['w-2 h-2 rounded-full', u.is_active ? 'bg-[#10B981] animate-pulse' : 'bg-rose-500']" />
                {{ u.is_active ? 'Akun Aktif' : 'Dinonaktifkan' }}
              </button>

              <!-- Action Buttons -->
              <div class="flex items-center gap-1">
                <Button
                  variant="outline"
                  size="sm"
                  class="h-8 text-xs rounded-xl border-slate-200 dark:border-slate-800 cursor-pointer"
                  @click="openEditDialog(u)"
                >
                  <Pencil class="w-3.5 h-3.5 mr-1 text-slate-400" />
                  Edit
                </Button>
                <Button
                  v-if="u.id !== currentAuthUserId"
                  variant="outline"
                  size="icon"
                  class="h-8 w-8 rounded-xl border-slate-200 dark:border-slate-800 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                  @click="openDeleteDialog(u)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </Button>
              </div>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] text-center space-y-2">
            <Inbox class="w-10 h-10 mx-auto text-slate-400 stroke-[1.5]" />
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada pengguna ditemukan</p>
            <p class="text-xs text-slate-400">Ubah filter atau pencarian Anda.</p>
            <Button
              variant="outline"
              size="sm"
              @click="resetAllFilters"
              class="mt-2 text-xs rounded-xl"
            >
              Reset Filter
            </Button>
          </div>
        </template>
      </div>
    </div>

    <!-- Modals & Dialogs -->
    <UserFormDialog
      v-model:open="isFormDialogOpen"
      :mode="formDialogMode"
      :user="selectedUser"
      :roles="roles"
      :current-auth-user-id="currentAuthUserId"
      @saved="onUserSaved"
    />

    <UserStatusDialog
      v-model:open="isStatusDialogOpen"
      :user="statusTargetUser"
      :current-auth-user-id="currentAuthUserId"
      @confirmed="onStatusChanged"
    />

    <UserDeleteDialog
      v-model:open="isDeleteDialogOpen"
      :user="deleteTargetUser"
      :current-auth-user-id="currentAuthUserId"
      @deleted="onUserDeleted"
    />

    <!-- User Action Feedback Toast -->
    <UserToast :toast="toast" @close="toast = null" />
  </AuthenticatedLayout>
</template>
