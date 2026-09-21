<script setup>
import { Head } from '@inertiajs/vue3'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { MoreHorizontal, Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'

defineProps({
    barang: Array
})
</script>

<template>

    <Head title="Master Inventaris - SIPALING" />

    <div class="min-h-screen bg-zinc-50/50 p-8">
        <div class="mx-auto max-w-5xl space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Master Data Inventaris</h1>
                    <p class="text-sm text-zinc-500 mt-1">Kelola semua data barang, stok aktual, dan status
                        ketersediaan.</p>
                </div>
                <Button class="bg-zinc-900 text-white hover:bg-zinc-800">
                    <Plus class="w-4 h-4 mr-2" /> Tambah Barang
                </Button>
            </div>

            <div class="flex items-center justify-between">
                <div class="relative w-80">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-zinc-400" />
                    <Input type="text" placeholder="Cari kode atau nama barang..." class="pl-9 bg-white shadow-sm" />
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-zinc-50/80 hover:bg-zinc-50/80 border-b-zinc-200">
                            <TableHead class="w-[140px] font-semibold text-zinc-900">Kode Barang</TableHead>
                            <TableHead class="font-semibold text-zinc-900">Nama Barang</TableHead>
                            <TableHead class="text-right font-semibold text-zinc-900">Stok</TableHead>
                            <TableHead class="text-center font-semibold text-zinc-900">Status</TableHead>
                            <TableHead class="w-[80px] text-center font-semibold text-zinc-900">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in barang" :key="item.id" class="transition-colors hover:bg-zinc-50/50">
                            <TableCell class="font-medium text-zinc-900">{{ item.id }}</TableCell>
                            <TableCell class="text-zinc-600">{{ item.nama }}</TableCell>
                            <TableCell class="text-right font-medium text-zinc-700">{{ item.stok }}</TableCell>
                            <TableCell class="text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                    :class="item.status === 'Aman'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-700 border-amber-200'">
                                    {{ item.status }}
                                </span>
                            </TableCell>
                            <TableCell class="text-center">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0 hover:bg-zinc-100">
                                            <span class="sr-only">Buka menu</span>
                                            <MoreHorizontal class="h-4 w-4 text-zinc-500" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-40">
                                        <DropdownMenuLabel class="text-xs text-zinc-500">Aksi Data</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="cursor-pointer">
                                            <Pencil class="mr-2 h-4 w-4 text-zinc-500" /> Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="cursor-pointer text-red-600 focus:bg-red-50 focus:text-red-700">
                                            <Trash2 class="mr-2 h-4 w-4" /> Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!barang || barang.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-zinc-500">
                                Tidak ada data barang yang ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </div>
    </div>
</template>
