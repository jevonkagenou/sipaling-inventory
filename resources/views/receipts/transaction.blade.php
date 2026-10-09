<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bukti Transaksi {{ $transaction->reference_no }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header p {
            font-size: 10px;
            color: #64748b;
            margin: 4px 0 0;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .badge-inbound {
            background-color: #d1fae5;
            color: #047857;
        }
        .badge-outbound {
            background-color: #fef3c7;
            color: #b45309;
        }
        .ref-title {
            font-size: 14px;
            font-weight: bold;
            margin: 4px 0 16px;
        }
        .metadata {
            width: 100%;
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .metadata table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata td {
            vertical-align: top;
            padding: 2px 8px 2px 0;
            width: 33%;
        }
        .metadata .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: bold;
        }
        .metadata .value {
            font-size: 10px;
            font-weight: bold;
            color: #334155;
        }
        .section-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 6px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.items th {
            background-color: #f8fafc;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .qty-in { color: #047857; font-weight: bold; }
        .qty-out { color: #b45309; font-weight: bold; }
        .notes-box {
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .notes-box .label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            font-size: 10px;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #0f172a;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIPALING — Bukti Serah Terima Barang</h1>
        <p>Sistem Inventaris Prediktif &amp; Audit Log Terintegrasi</p>
    </div>

    <span class="badge {{ $transaction->type === 'inbound' ? 'badge-inbound' : 'badge-outbound' }}">
        {{ $transaction->type === 'inbound' ? 'Barang Masuk (Inbound)' : 'Barang Keluar (Outbound)' }}
    </span>
    <div class="ref-title">Bukti Transaksi: {{ $transaction->reference_no }}</div>

    <div class="metadata">
        <table>
            <tr>
                <td>
                    <span class="label">Waktu Transaksi</span><br>
                    <span class="value">{{ \Carbon\Carbon::parse($transaction->transaction_date)->translatedFormat('d M Y, H:i') }}</span>
                </td>
                <td>
                    <span class="label">{{ $transaction->type === 'inbound' ? 'Pemasok' : 'Penerima' }}</span><br>
                    <span class="value">{{ $transaction->party_name ?? '-' }}</span>
                </td>
                <td>
                    <span class="label">Petugas Pencatat</span><br>
                    <span class="value">{{ $transaction->creator->name ?? 'Sistem' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Rincian Barang Mutasi</div>
    <table class="items">
        <thead>
            <tr>
                <th>SKU &amp; Nama Produk</th>
                <th class="text-center">Kuantitas</th>
                <th class="text-right">Harga Unit</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->details as $detail)
                <tr>
                    <td>
                        <strong>{{ $detail->product->name ?? '-' }}</strong><br>
                        <span style="font-size:9px;color:#94a3b8;">{{ $detail->product->sku ?? '-' }}</span>
                    </td>
                    <td class="text-center {{ $transaction->type === 'inbound' ? 'qty-in' : 'qty-out' }}">
                        {{ $transaction->type === 'inbound' ? '+' : '-' }}{{ $detail->quantity }} {{ $detail->product->unit ?? '' }}
                    </td>
                    <td class="text-right">
                        Rp{{ number_format($detail->unit_price, 0, ',', '.') }}
                    </td>
                    <td>{{ $detail->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($transaction->notes)
        <div class="notes-box">
            <span class="label">Catatan:</span>
            {{ $transaction->notes }}
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                Diserahkan oleh,
                <div class="signature-line">( _______________ )</div>
            </td>
            <td>
                Diterima oleh,
                <div class="signature-line">( _______________ )</div>
            </td>
        </tr>
    </table>
</body>
</html>