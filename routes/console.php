<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled tasks. The scheduler will
| check each configuration's poll_interval_seconds to determine if
| it should process that mailbox.
|
*/

Schedule::command('emails:process-inbound')
    ->everyTenSeconds()
    ->withoutOverlapping()
    ->runInBackground();
