<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemController extends Controller
{
    /**
     * Manually runs every job normally driven by the production cron's
     * `schedule:run` line. Deliberately calls each command directly rather
     * than `Artisan::call('schedule:run')` itself -- schedule:run only runs
     * whatever happens to be due at the exact current minute, so clicking
     * this button outside those exact times (hourly jobs on `:00`, daily
     * jobs at their specific hour) would silently do nothing. Calling each
     * command directly makes every job actually run on demand, which is the
     * whole point of a manual "run it now" button.
     *
     * prices:refresh-ai / prices:refresh-gov still respect their own
     * price_refresh_ai_enabled check internally (they log a skip message
     * and return early) -- this endpoint doesn't need to duplicate that
     * gate, just surface whatever each command already reports.
     */
    public function runSchedule(Request $request)
    {
        $commands = [
            'ulam:maintenance',
            'billing:process-lifecycle',
            'prices:refresh-ai',
            'prices:refresh-gov',
            'ulam:expire-strikes',
            'ulam:weather-daily',
            'ulam:daily-reminders',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $exitCode = Artisan::call($command);
                $results[] = [
                    'command'   => $command,
                    'exit_code' => $exitCode,
                    'output'    => trim(Artisan::output()),
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'command'   => $command,
                    'exit_code' => 1,
                    'output'    => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
