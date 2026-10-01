<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi 2FA - SIPALING</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .description {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .otp-wrapper {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .otp-code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #2563eb;
            user-select: all;
        }
        .otp-expiry {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
        }
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
            color: #1e40af;
            margin-bottom: 24px;
        }
        .audit-info {
            font-size: 12px;
            color: #64748b;
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding: 20px 28px;
            background-color: #f8fafc;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>SIPALING</h1>
            <p>Sistem Inventaris Prediktif &amp; Audit Log Terintegrasi</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Halo, {{ $user->name }}</div>
            <p class="description">
                Kami menerima permintaan pengaturan ulang kata sandi untuk akun inventaris Anda. Silakan gunakan 6 digit kode verifikasi 2FA berikut untuk melanjutkan proses pemulihan akun:
            </p>

            <!-- OTP Box -->
            <div class="otp-wrapper">
                <div class="otp-label">Kode Verifikasi 2FA</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">Berlaku selama <strong>10 menit</strong> sejak pesan ini diterbitkan.</div>
            </div>

            <!-- Security Warning -->
            <div class="info-box">
                <strong>Peringatan Keamanan:</strong> Jangan pernah membagikan kode OTP ini kepada siapa pun, termasuk staf operasional ataupun pihak manajemen. Sistem kami tidak akan pernah menanyakan kode ini secara langsung.
            </div>

            <p class="description" style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                Jika Anda tidak merasa melakukan permintaan pemulihan kata sandi ini, abaikan email ini atau segera laporkan ke Auditor Internal untuk investigasi keamanan akun.
            </p>

            @if($ipAddress)
            <div class="audit-info">
                <strong>Jejak Audit Permintaan:</strong><br>
                Alamat IP Pemohon: <code>{{ $ipAddress }}</code> &bull; Waktu: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIPALING. Proyek PBL Kelompok 1 - SIB 3C.</p>
            <p>Jurusan Teknologi Informasi, Politeknik Negeri Malang.</p>
        </div>
    </div>
</body>
</html>
