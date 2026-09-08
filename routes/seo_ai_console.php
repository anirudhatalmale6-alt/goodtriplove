<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('gtl:seo-ai-weekly')
    ->weeklyOn(1, '04:45')
    ->withoutOverlapping(180);
