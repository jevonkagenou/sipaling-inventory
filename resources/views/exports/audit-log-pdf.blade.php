<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Jejak Rekam Audit - SIPALING</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: A4 landscape;
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

        /* Informasi Audit (Info Box) */
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
            width: 42%;
            color: #64748b;
        }
        .kv-s {
            width: 5%;
            color: #94a3b8;
            text-align: center;
        }
        .kv-v {
            width: 53%;
            color: #0f172a;
        }

        /* Tabel Rincian Data Audit */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 8pt;
        }
        table.items-table thead {
            display: table-header-group;
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
            padding: 6.5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: top;
        }
        table.items-table tbody tr {
            page-break-inside: avoid;
        }
        table.items-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
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

        /* Tanda Tangan 2 Kolom Simetris */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        table.sig-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .sig-title {
            font-size: 8pt;
            color: #334155;
            font-weight: 500;
        }
        .sig-space {
            height: 48px;
        }
        .sig-line {
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 170px;
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
            margin-top: 18px;
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
        $docNumber = 'AUD-' . now()->format('Ymd') . '-' . strtoupper(substr(md5($exportedAt . $totalItems . $exportedBy), 0, 4));
    @endphp

    <!-- Header Dokumen Formal (Seragam dengan Format Transaksi) -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="company-name">SIPALING INVENTORY</div>
                <div class="company-desc">Warehouse Management System</div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="doc-title-main">
                    LAPORAN REKAM JEJAK AUDIT
                </div>
                <table class="meta-table-right" cellpadding="0" cellspacing="0" align="right">
                    <tr>
                        <td class="meta-r-label">No. Dokumen</td>
                        <td class="meta-r-sep">:</td>
                        <td class="meta-r-val font-mono font-bold">{{ $docNumber }}</td>
                    </tr>
                    <tr>
                        <td class="meta-r-label">Tanggal</td>
                        <td class="meta-r-sep">:</td>
                        <td class="meta-r-val">{{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-line"></div>

    <!-- Info Box: Parameter Filter & Metadata Audit -->
    <table class="info-box" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 52%; vertical-align: middle;">
                <div class="info-heading">PARAMETER &amp; CAKUPAN AUDIT:</div>
                <div class="info-party-name">{{ $filterDesc }}</div>
                <div class="info-subtext">Pencatatan forensik digital atas setiap perubahan data inventaris, mutasi stok, &amp; aktivitas akun.</div>
            </td>
            <td style="width: 48%; vertical-align: middle; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                <table class="info-kv-right" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="kv-k">Petugas Pemohon</td>
                        <td class="kv-s">:</td>
                        <td class="kv-v font-bold">{{ $exportedBy }}</td>
                    </tr>
                    <tr>
                        <td class="kv-k">Jenis Dokumen</td>
                        <td class="kv-s">:</td>
                        <td class="kv-v font-bold">Rekam Jejak Sistem (Audit Trail)</td>
                    </tr>
                    <tr>
                        <td class="kv-k">Status Integritas</td>
                        <td class="kv-s">:</td>
                        <td class="kv-v font-bold">Terverifikasi &amp; Terkunci</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Data Log Audit -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">NO</th>
                <th class="text-center" style="width: 14%;">WAKTU &amp; TANGGAL</th>
                <th style="width: 13%;">KATEGORI</th>
                <th class="text-center" style="width: 12%;">JENIS AKSI</th>
                <th style="width: 37%;">URAIAN AKTIVITAS / BUKTI FORENSIK</th>
                <th style="width: 20%;">PENGGUNA (AKTOR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                @php
                    $causerName  = $log->causer?->name  ?? 'Sistem / Anonim';
                    $causerEmail = $log->causer?->email ?? '-';
                @endphp
                <tr>
                    <td class="text-center font-mono" style="color: #64748b;">{{ $index + 1 }}</td>
                    <td class="text-center font-mono" style="color: #0f172a; font-size: 7.5pt;">
                        <strong>{{ $log->created_at?->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</strong>
                        <div style="color: #64748b; font-size: 7pt;">{{ $log->created_at?->setTimezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
                    </td>
                    <td style="color: #0f172a; font-weight: 600; text-transform: uppercase;">
                        {{ $log->log_name ?? '-' }}
                    </td>
                    <td class="text-center font-bold font-mono" style="color: #0f172a; font-size: 7.5pt;">
                        {{ strtoupper($log->event ?? '-') }}
                    </td>
                    <td>
                        <div style="font-weight: 500; color: #0f172a;">{{ $log->description ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">{{ $causerName }}</div>
                        <div style="font-size: 7pt; color: #64748b;">{{ $causerEmail }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 16px; color: #94a3b8;">
                        Tidak ada rekaman jejak audit yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold" style="letter-spacing: 0.5px;">TOTAL REKAMAN AKTIVITAS:</td>
                <td colspan="2" class="font-bold font-mono" style="font-size: 8.5pt; color: #0f172a;">
                    {{ number_format($totalItems, 0, ',', '.') }} Entri Log
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Lembar Tanda Tangan 2 Pihak Resmi (Simetris Kiri & Kanan) -->
    <table class="sig-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%;">
                <div class="sig-title">Dibuat / Diekspor Oleh,</div>
                <div class="sig-space"></div>
                <div>
                    <span class="sig-line">
                        {{ $exportedBy ?: '........................................' }}
                    </span>
                </div>
                <div class="sig-role">Petugas / Auditor Sistem</div>
            </td>
            <td style="width: 50%;">
                <div class="sig-title">Ditinjau / Disetujui Oleh,</div>
                <div class="sig-space"></div>
                <div>
                    <span class="sig-line">
                        ........................................
                    </span>
                </div>
                <div class="sig-role">Manajer Operasional / Penanggung Jawab</div>
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
                    Dicetak: {{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
