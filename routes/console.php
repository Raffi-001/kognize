<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('kognize:send-notification-digests')->dailyAt('08:00')->timezone('Asia/Yerevan');
