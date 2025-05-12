<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Cuti atau Izin Bekerja</title>
    <style>
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 650px;
            margin: 0 auto;
            background-color: #f5f7fb;
            padding: 20px;
        }

        /* Card Container */
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #4F46E5 0%, #7E76FF 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header h2 {
            margin: 10px 0 0;
            font-size: 18px;
            font-weight: 500;
            opacity: 0.9;
        }

        /* Content Styles */
        .content {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .content p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .info-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            width: 140px;
            color: #4b5563;
            flex-shrink: 0;
        }

        .info-value {
            flex: 1;
        }

        /* Status Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-requested {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .status-approved {
            background-color: #DCFCE7;
            color: #15803D;
        }

        .status-rejected {
            background-color: #FEE2E2;
            color: #B91C1C;
        }

        /* Button Styles */
        .action-button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #4338CA;
        }

        .notes {
            margin-top: 25px;
            padding: 15px;
            background-color: #F3F4F6;
            border-left: 4px solid #9CA3AF;
            border-radius: 4px;
            font-size: 14px;
            color: #4B5563;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #6B7280;
        }

        .footer p {
            margin: 5px 0;
        }

        /* Responsive Adjustments */
        @media screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .info-label, .info-value {
                width: 100%;
            }

            .info-value {
                margin-top: 5px;
            }

            .action-button {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>JakLingko Presensi</h1>
        <h2>Notifikasi Permintaan Cuti Baru</h2>
    </div>

    <div class="content">
        <p>Selamat {{ now()->format('H') < 12 ? 'pagi' : (now()->format('H') < 15 ? 'siang' : (now()->format('H') < 18 ? 'sore' : 'malam')) }}!</p>

        <p>Kami ingin memberitahukan bahwa terdapat <strong>permintaan cuti baru</strong> yang memerlukan perhatian Anda. Berikut adalah detail permintaan tersebut:</p>

        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Nama Pengemudi:</span>
                <span class="info-value">{{ $leaveRequest->driver->name }} - {{ $leaveRequest->driver->type }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Periode Cuti:</span>
                <span class="info-value">
                        @if($leaveRequest->start_date->isSameDay($leaveRequest->end_date))
                        {{ $leaveRequest->start_date->format('d M Y') }}
                    @else
                        {{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}
                    @endif
                        <span style="color: #6B7280;">(Durasi: {{ $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 }} hari)</span>
                    </span>
            </div>

            <div class="info-row">
                <span class="info-label">Jenis Izin:</span>
                <span class="info-value">{{ ucfirst($leaveRequest->type) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Alasan:</span>
                <span class="info-value">{{ $leaveRequest->reason ?? 'Tidak dicantumkan' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><span class="status-badge status-requested">Menunggu Persetujuan</span></span>
            </div>
        </div>

        <p>Mohon untuk segera meninjau permintaan ini untuk memastikan tidak ada gangguan pada jadwal operasional.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/leave-requests/' . $leaveRequest->id) }}" class="action-button">Tinjau Permintaan</a>
        </div>

        <div class="notes">
            <strong>Penting:</strong> Sebelum menyetujui permintaan ini, harap pastikan:
            <ul style="margin-top: 8px; padding-left: 20px;">
                <li>Tersedia pengemudi pengganti selama periode cuti</li>
                <li>Tidak ada tumpang tindih dengan cuti pengemudi lain</li>
                <li>Pengajuan sesuai dengan kebijakan cuti perusahaan</li>
            </ul>
            <p style="margin-top: 8px; margin-bottom: 0;">Hanya superadmin yang dapat menyetujui atau menolak permintaan ini.</p>
        </div>
    </div>

    <div class="footer">
        <p>Email ini dikirim secara otomatis oleh <strong>Sistem Presensi JakLingko</strong>. Mohon untuk tidak membalas pesan ini.</p>
        <p>Jika membutuhkan bantuan lebih lanjut, silakan hubungi <a href="mailto:support@jaklingko.id" style="color: #4F46E5; text-decoration: none;">support@jaklingko.id</a></p>
        <p>&copy; {{ date('Y') }} JakLingko. Hak cipta dilindungi undang-undang.</p>
    </div>
</div>
</body>
</html>
