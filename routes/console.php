<?php

use Illuminate\Support\Facades\Schedule;

/*
| OVH web hosting runs scheduled tasks at most once an hour (see ovh-cron.php),
| so everything here must be fine to run hourly.
*/

Schedule::command('shop:release-expired-reservations')->hourly();
