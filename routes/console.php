<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Jadwal perjalanan <-> Cartrack: link driver saat mulai, lepas saat selesai, tarik trip.
// Butuh cron server: * * * * * cd /path/to/app && php artisan schedule:run
Schedule::command('cartrack:jalankan-jadwal')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();
