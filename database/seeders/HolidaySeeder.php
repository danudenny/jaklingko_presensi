<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // Libur Nasional
            ['date' => '2026-01-01', 'name' => 'Tahun Baru', 'type' => 'libur_nasional'],
            ['date' => '2026-01-16', 'name' => 'Isra Mikraj', 'type' => 'libur_nasional'],
            ['date' => '2026-02-17', 'name' => 'Tahun Baru Imlek', 'type' => 'libur_nasional'],
            ['date' => '2026-03-19', 'name' => 'Hari Suci Nyepi', 'type' => 'libur_nasional'],
            ['date' => '2026-03-21', 'name' => 'Idul Fitri 1', 'type' => 'libur_nasional'],
            ['date' => '2026-03-22', 'name' => 'Idul Fitri 2', 'type' => 'libur_nasional'],
            ['date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus', 'type' => 'libur_nasional'],
            ['date' => '2026-04-05', 'name' => 'Paskah', 'type' => 'libur_nasional'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh', 'type' => 'libur_nasional'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus', 'type' => 'libur_nasional'],
            ['date' => '2026-05-27', 'name' => 'Idul Adha', 'type' => 'libur_nasional'],
            ['date' => '2026-05-31', 'name' => 'Waisak', 'type' => 'libur_nasional'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'type' => 'libur_nasional'],
            ['date' => '2026-06-16', 'name' => 'Tahun Baru Islam', 'type' => 'libur_nasional'],
            ['date' => '2026-08-17', 'name' => 'Kemerdekaan RI', 'type' => 'libur_nasional'],
            ['date' => '2026-08-25', 'name' => 'Maulid Nabi', 'type' => 'libur_nasional'],
            ['date' => '2026-12-25', 'name' => 'Kelahiran Yesus Kristus', 'type' => 'libur_nasional'],

            // Cuti Bersama
            ['date' => '2026-02-16', 'name' => 'Cuti Bersama Imlek', 'type' => 'cuti_bersama'],
            ['date' => '2026-03-18', 'name' => 'Cuti Bersama Nyepi', 'type' => 'cuti_bersama'],
            ['date' => '2026-03-20', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'cuti_bersama'],
            ['date' => '2026-03-23', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'cuti_bersama'],
            ['date' => '2026-03-24', 'name' => 'Cuti Bersama Idul Fitri', 'type' => 'cuti_bersama'],
            ['date' => '2026-05-15', 'name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'type' => 'cuti_bersama'],
            ['date' => '2026-05-28', 'name' => 'Cuti Bersama Idul Adha', 'type' => 'cuti_bersama'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}
