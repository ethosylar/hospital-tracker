<?php
	
	use Illuminate\Support\Facades\Schedule;
	
	Schedule::command('eptw:sync --mode=INCREMENTAL')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
	
	Schedule::command('eptw:sync --mode=FULL')
    ->dailyAt('02:00')->withoutOverlapping();