<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SchedulesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $schedules;

    public function __construct($schedules)
    {
        $this->schedules = $schedules;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->schedules;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No.',
            'Tanggal',
            'Pengemudi',
            'Tipe Pengemudi',
            'Rute',
            'Unit',
            'Plat Nomor',
            'Shift',
            'Status',
            'Backup Pengemudi',
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($schedule): array
    {
        $status = ucfirst(str_replace('_', ' ', $schedule->status));
        $shift = ($schedule->shift == 'pagi' || $schedule->shift == 'morning') ? 'Pagi' : 'Siang';
        
        return [
            $schedule->id,
            $schedule->schedule_date,
            $schedule->driver->name,
            ucfirst($schedule->driver->type),
            $schedule->route->name . ' (' . $schedule->route->route_number . ')',
            $schedule->unit->unit_number,
            $schedule->unit->plate_number,
            $shift,
            $status,
            $schedule->backup_driver_id ? $schedule->backupDriver->name : '-',
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
