<?php

namespace App\Console\Commands;

use App\Http\Controllers\CronController;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SendAppointmentReminders extends Command
{
    protected $signature = 'clinic:remind';

    protected $description = 'Send appointment reminder SMS for upcoming slots';

    public function handle(SmsService $sms): int
    {
        $key = (string) clinic_setting('cron_key', '');
        $request = Request::create('/cron/remind', 'GET', ['key' => $key]);
        $response = app(CronController::class)->remind($request, $sms);
        $data = $response->getData(true);
        $this->info('Reminders sent: '.($data['sent'] ?? 0));

        return self::SUCCESS;
    }
}
