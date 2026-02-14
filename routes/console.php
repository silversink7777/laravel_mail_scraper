<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// スクレイプ＆メール通知（30分ごと。変更する場合はここでスケジュールを編集）
Schedule::command('scrape:notify')->everyThirtyMinutes();
