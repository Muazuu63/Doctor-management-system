<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('clinic:remind')->everyFiveMinutes();
