<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clean up temporary upload files every hour
        // Files older than 1 hour (3600 seconds) will be deleted
        $schedule->command('cleanup:temp-files --age=3600')
                 ->hourly()
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Temp files cleanup completed successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('Temp files cleanup failed');
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
