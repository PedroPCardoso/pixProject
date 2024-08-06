
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\SwooleTableService;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::job(new Heartbeat, 'heartbeats', 'sqs')->everyFiveMinutes()

Schedule::command('app:update-transactions')->everySecond();
