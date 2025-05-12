<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permintaan Cuti atau Izin Bekerja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            display: inline-block;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-requested {
            background-color: #FEF3C7;
            color: #D97706;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>JakLingko Presensi</h1>
        <h2>Permintaan Cuti atau Izin Bekerja Baru</h2>
    </div>

    <div class="content">
        <p>Permintaan cuti atau izin bekerja baru telah diterima.</p>

        <div class="info-row">
            <span class="info-label">Nama Pengemudi:</span>
            <span>{{ $leaveRequest->driver->name }} - {{ $leaveRequest->driver->type }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Tanggal:</span>
            <span>
                @if($leaveRequest->start_date->isSameDay($leaveRequest->end_date))
                    {{ $leaveRequest->start_date->format('d M Y') }}
                @else
                    {{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}
                @endif
                ({{ $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 }} days)
            </span>
        </div>

        <div class="info-row">
            <span class="info-label">Tipe Cuti:</span>
            <span>{{ ucfirst($leaveRequest->type) }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Alasan:</span>
            <span>{{ $leaveRequest->reason ?? 'Tanpa alasan' }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="status-badge status-requested">Requested</span>
        </div>

        <p style="margin-top: 20px;">
            <a href="{{ url('/leave-requests/' . $leaveRequest->id) }}" style="background-color: #4F46E5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Lihat Detail</a>
        </p>

        <p style="margin-top: 20px; font-size: 12px; color: #666;">
            Note:Hanya superadmin yang dapat menyetujui permintaan cuti. Harap tinjau permintaan tersebut dan pastikan terdapat pengemudi cadangan yang tersedia sebelum menyetujui.
        </p>
    </div>

    <div class="footer">
        <p>Ini adalah pesan otomatis dari sistem Presensi JakLingko. Mohon untuk tidak membalas email ini.</p>
        <p>&copy; {{ date('Y') }} JakLingko. Hak cipta dilindungi undang-undang.</p>
    </div>
</body>
</html>
