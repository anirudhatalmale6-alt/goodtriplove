<?php
return [
 'youtube' => [
   'daily_quota' => (int) env('YOUTUBE_DAILY_QUOTA', 10000),
   'warning_percent' => (int) env('YOUTUBE_QUOTA_WARNING_PERCENT', 80),
   'hard_stop_percent' => (int) env('YOUTUBE_QUOTA_HARD_STOP_PERCENT', 95),
 ],
 'history_retention_days' => (int) env('USER_HISTORY_RETENTION_DAYS', 90),
 'maintenance_bypass_roles' => ['super_admin'],
 'pwa' => ['enabled' => true],
];
