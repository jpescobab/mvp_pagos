<?php

use App\Jobs\ImportarDolarDiarioJob;
use App\Jobs\ImportarIndicadoresMensualesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ImportarIndicadoresMensualesJob)
    ->monthlyOn(10, '06:00')
    ->timezone('America/Coyhaique')
    ->withoutOverlapping();

Schedule::job(new ImportarDolarDiarioJob)
    ->dailyAt('07:00')
    ->timezone('America/Coyhaique')
    ->withoutOverlapping();
