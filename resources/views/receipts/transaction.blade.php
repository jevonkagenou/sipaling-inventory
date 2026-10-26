<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $transaction->type === 'inbound' ? 'Bukti Penerimaan Barang' : 'Surat Jalan / Bukti Pengeluaran Barang' }} - {{ $transaction->reference_no }}</title>
    <style>
        @page {
            margin: 18mm 18mm 18mm 18mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
            font-family: 'Helvetica', Arial, sans-serif;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Header / Kop Dokumen Formal */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-desc {
            font-size: 8pt;
            color: #475569;
            margin-top: 2px;
        }

        .doc-title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            line-height: 1.1;
        }
        table.meta-table-right {
            border-collapse: collapse;
            margin-left: auto;
            margin-right: 0;
        }
        table.meta-table-right td {
            font-size: 8pt;
            padding: 1.5px 0;
            line-height: 1.35;
        }
        .meta-r-label {
            color: #64748b;
            text-align: right;
            white-space: nowrap;
            padding-right: 4px;
        }
        .meta-r-sep {
            color: #94a3b8;
            text-align: center;
            width: 12px;
            padding-right: 4px;
        }
        .meta-r-val {
            color: #0f172a;
            text-align: left;
            white-space: nowrap;
        }

        /* Garis Pemisah Header */
        .divider-line {
            border-top: 1.5px solid #0f172a;
            margin: 8px 0 14px 0;
        }

        /* Informasi Transaksi (Info Box) */
        table.info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
        }
        table.info-box td {
            padding: 8px 10px;
            vertical-align: top;
        }
        .info-heading {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-party-name {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
        }
        .info-subtext {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 3px;
        }

        table.info-kv-right {
            width: 100%;
            border-collapse: collapse;
        }
        table.info-kv-right td {
            padding: 1.5px 0;
            font-size: 8pt;
            vertical-align: top;
        }
        .kv-k {
            width: 40%;
            color: #64748b;
        }
        .kv-s {
            width: 5%;
            color: #94a3b8;
            text-align: center;
        }
        .kv-v {
            width: 55%;
            color: #0f172a;
        }

        /* Tabel Rincian Barang */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 8pt;
        }
        table.items-table thead th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #0f172a;
            text-align: left;
        }
        table.items-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: top;
        }
        table.items-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .product-title {
            font-weight: bold;
            color: #0f172a;
        }
        .product-notes {
            font-size: 7pt;
            color: #64748b;
            font-style: italic;
            margin-top: 1px;
        }

        /* Total / Footer Tabel */
        table.items-table tfoot td {
            background-color: #f8fafc;
            padding: 7px 8px;
            border-top: 1.5px solid #0f172a;
            border-bottom: 3px double #0f172a;
            font-weight: bold;
            color: #0f172a;
        }

        /* Catatan Khusus */
        .doc-notes {
            margin-top: -8px;
            margin-bottom: 16px;
            padding: 6px 8px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            font-size: 7.5pt;
            color: #334155;
            border-radius: 3px;
        }

        /* Tanda Tangan */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        table.sig-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }
        .sig-title {
            font-size: 8pt;
            color: #334155;
            font-weight: 500;
        }
        .sig-space {
            height: 50px;
        }
        .sig-line {
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 135px;
            padding-bottom: 2px;
            font-weight: bold;
            font-size: 8pt;
            color: #0f172a;
        }
        .sig-role {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer Halaman */
        .footer-bar {
            margin-top: 22px;
            padding-top: 6px;
            border-top: 0.5px solid #cbd5e1;
            font-size: 7pt;
            color: #94a3b8;
            page-break-inside: avoid;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-mono { font-family: 'Courier', monospace !important; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $isInbound = $transaction->type === 'inbound';
        $totalQuantity = $transaction->details->sum('quantity');
        $totalValuation = $transaction->details->sum(function($d) {
            return (float) $d->quantity * (float) ($d->unit_price ?? 0);
        });

        if (!function_exists('formatRupiahPdf')) {
            function formatRupiahPdf($amount) {
                $num = (float) ($amount ?? 0);
                if ($num == 0) return 'Rp 0';
                if (floor($num) == $num) {
                    return 'Rp ' . number_format($num, 0, ',', '.');
                }
                return 'Rp ' . number_format($num, 2, ',', '.');
            }
        }
    @endphp

    <!-- Header Dokumen Formal (Seragam Inbound & Outbound) -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="company-name">SIPALING INVENTORY</div>
                <div class="company-desc">Warehouse Management System</div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">
                    {{ $isInbound ? 'BUKTI PENERIMAAN BARANG' : 'SURAT JALAN / PENGELUARAN' }}
                </div>
                <table class="meta-table-right" cellpadding="0" cellspacing="0" align="right">
                    <tr>
                        <td class="meta-r-label">No. Dokumen</td>
                        <td class="meta-r-sep">:</td>
                        <td class="meta-r-val font-mono font-bold" style="color: #1d4ed8;">{{ $transaction->reference_no }}</td>
                    </tr>
                    <tr>
                        <td class="meta-r-label">Tanggal</td>
                        <td class="meta-r-sep">:</td>
                        <td class="meta-r-val">{{ \Carbon\Carbon::parse($transaction->transaction_date)->translatedFormat('d F Y, H:i') }} WIB</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-line"></div>

    <!-- Bagian Atas / Metadata (Beda Antara Inbound vs Outbound) -->
    @if ($isInbound)
        <!-- Info Box Inbound: Fokus Pemasok & Penerimaan -->
        <table class="info-box" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 52%; vertical-align: middle;">
                    <div class="info-heading">PEMASOK / VENDOR:</div>
                    <div class="info-party-name">{{ $transaction->party_name ?: 'Pemasok Umum' }}</div>
                    @if ($transaction->notes)
                        <div class="info-subtext"><strong>Catatan Masuk:</strong> {{ Str::limit($transaction->notes, 75) }}</div>
                    @endif
                </td>
                <td style="width: 48%; vertical-align: middle; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                    <table class="info-kv-right" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="kv-k">Petugas Penerima</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v font-bold">{{ $transaction->creator->name ?? 'Staf Gudang' }}</td>
                        </tr>
                        <tr>
                            <td class="kv-k">Jenis Mutasi</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v font-bold" style="color: #059669;">Barang Masuk (Inbound)</td>
                        </tr>
                        <tr>
                            <td class="kv-k">Status Fisik</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v" style="color: #0f172a;">Diterima &amp; Masuk Stok</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        <!-- Info Box Outbound: Sesuai Referensi Pengeluaran Barang / Surat Jalan -->
        <table class="info-box" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 52%; vertical-align: middle;">
                    <div class="info-heading">PENERIMA / PEMOHON:</div>
                    <div class="info-party-name">{{ $transaction->party_name ?: 'Pihak Penerima' }}</div>
                    @if ($transaction->notes)
                        <div class="info-subtext"><strong>Tujuan / Catatan:</strong> {{ Str::limit($transaction->notes, 75) }}</div>
                    @endif
                </td>
                <td style="width: 48%; vertical-align: middle; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                    <table class="info-kv-right" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="kv-k">Petugas Gudang</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v font-bold">{{ $transaction->creator->name ?? 'Staf Gudang' }}</td>
                        </tr>
                        <tr>
                            <td class="kv-k">Jenis Mutasi</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v font-bold" style="color: #d97706;">Barang Keluar (Outbound)</td>
                        </tr>
                        <tr>
                            <td class="kv-k">Status Pengiriman</td>
                            <td class="kv-s">:</td>
                            <td class="kv-v" style="color: #0f172a;">Dikeluarkan dari Gudang</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <!-- Tabel Rincian Barang (Beda Struktur Antara Inbound vs Outbound) -->
    @if ($isInbound)
        <!-- Tabel Inbound: Dilengkapi Harga Satuan & Total Valuasi Masuk -->
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 4%;">NO</th>
                    <th style="width: 16%;">KODE BARANG</th>
                    <th style="width: 32%;">NAMA BARANG</th>
                    <th style="width: 16%;">KATEGORI</th>
                    <th class="text-center" style="width: 10%;">KUANTITAS</th>
                    <th class="text-right" style="width: 11%;">HARGA SATUAN</th>
                    <th class="text-right" style="width: 11%;">TOTAL HARGA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaction->details as $index => $detail)
                    @php
                        $qty = (int) $detail->quantity;
                        $price = (float) ($detail->unit_price ?? 0);
                        $subtotal = $qty * $price;
                    @endphp
                    <tr>
                        <td class="text-center font-mono" style="color: #64748b;">{{ $index + 1 }}</td>
                        <td class="font-mono font-bold">{{ $detail->product->sku ?? '-' }}</td>
                        <td>
                            <div class="product-title">{{ $detail->product->name ?? 'Barang Tanpa Nama' }}</div>
                            @if ($detail->notes)
                                <div class="product-notes">Ket: {{ $detail->notes }}</div>
                            @endif
                        </td>
                        <td style="color: #475569;">{{ $detail->product->category->name ?? '-' }}</td>
                        <td class="text-center font-bold">
                            {{ number_format($qty, 0, ',', '.') }} {{ $detail->product->unit ?? 'Unit' }}
                        </td>
                        <td class="text-right font-mono">
                            {{ formatRupiahPdf($price) }}
                        </td>
                        <td class="text-right font-mono font-bold">
                            {{ formatRupiahPdf($subtotal) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 16px; color: #94a3b8;">
                            Tidak ada rincian barang pada dokumen ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right font-bold" style="letter-spacing: 0.5px;">TOTAL NILAI BARANG MASUK:</td>
                    <td class="text-center font-bold font-mono">
                        {{ number_format($totalQuantity, 0, ',', '.') }} Unit
                    </td>
                    <td colspan="2" class="text-right font-bold font-mono" style="font-size: 9pt; color: #0f172a;">
                        {{ formatRupiahPdf($totalValuation) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <!-- Tabel Outbound: Format Surat Jalan / Pengeluaran Fisik Logistik -->
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 4%;">NO</th>
                    <th style="width: 16%;">KODE BARANG</th>
                    <th style="width: 36%;">NAMA BARANG</th>
                    <th style="width: 16%;">KATEGORI</th>
                    <th class="text-center" style="width: 10%;">JUMLAH</th>
                    <th class="text-center" style="width: 8%;">SATUAN</th>
                    <th style="width: 10%;">STATUS CEK</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaction->details as $index => $detail)
                    @php
                        $qty = (int) $detail->quantity;
                    @endphp
                    <tr>
                        <td class="text-center font-mono" style="color: #64748b;">{{ $index + 1 }}</td>
                        <td class="font-mono font-bold">{{ $detail->product->sku ?? '-' }}</td>
                        <td>
                            <div class="product-title">{{ $detail->product->name ?? 'Barang Tanpa Nama' }}</div>
                            @if ($detail->notes)
                                <div class="product-notes">Ket: {{ $detail->notes }}</div>
                            @endif
                        </td>
                        <td style="color: #475569;">{{ $detail->product->category->name ?? '-' }}</td>
                        <td class="text-center font-bold font-mono">
                            {{ number_format($qty, 0, ',', '.') }}
                        </td>
                        <td class="text-center" style="color: #475569;">
                            {{ $detail->product->unit ?? 'Unit' }}
                        </td>
                        <td style="font-size: 7.5pt; color: #059669; font-weight: 500;">
                            Sesuai Order
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 16px; color: #94a3b8;">
                            Tidak ada rincian barang pada dokumen ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right font-bold" style="letter-spacing: 0.5px;">TOTAL BARANG KELUAR:</td>
                    <td class="text-center font-bold font-mono">
                        {{ number_format($totalQuantity, 0, ',', '.') }}
                    </td>
                    <td class="text-center font-bold">
                        Unit
                    </td>
                    <td style="font-size: 7pt; color: #64748b; font-weight: normal;">
                        Terverifikasi
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    <!-- Catatan Tambahan (Bila Ada) -->
    @if ($transaction->notes && strlen($transaction->notes) > 75)
        <div class="doc-notes">
            <strong style="color: #0f172a;">Catatan Lengkap:</strong> {{ $transaction->notes }}
        </div>
    @endif

    <!-- Lembar Tanda Tangan 3 Pihak Resmi -->
    <table class="sig-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="sig-title">Diserahkan Oleh,</div>
                <div class="sig-space"></div>
                <div>
                    <span class="sig-line">
                        {{ $isInbound ? ($transaction->party_name ?: '........................................') : ($transaction->creator->name ?? '........................................') }}
                    </span>
                </div>
                <div class="sig-role">{{ $isInbound ? 'Pemasok / Driver Pengirim' : 'Petugas Gudang Pengirim' }}</div>
            </td>
            <td>
                <div class="sig-title">Diterima Oleh,</div>
                <div class="sig-space"></div>
                <div>
                    <span class="sig-line">
                        {{ $isInbound ? ($transaction->creator->name ?? '........................................') : ($transaction->party_name ?: '........................................') }}
                    </span>
                </div>
                <div class="sig-role">{{ $isInbound ? 'Petugas Gudang Penerima' : 'Pihak Penerima Barang' }}</div>
            </td>
            <td>
                <div class="sig-title">Mengetahui,</div>
                <div class="sig-space"></div>
                <div>
                    <span class="sig-line">
                        ........................................
                    </span>
                </div>
                <div class="sig-role">Kepala Gudang / Supervisor</div>
            </td>
        </tr>
    </table>

    <!-- Footer Dokumen Bersih -->
    <div class="footer-bar">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; text-align: left;">
                    SIPALING &bull; Warehouse Management System
                </td>
                <td style="width: 40%; text-align: right;">
                    Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>
</body>
</html>