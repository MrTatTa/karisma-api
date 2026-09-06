<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('karisma:sync-sheets')
    ->dailyAt('23:59')
    ->timezone('Asia/Jakarta');

