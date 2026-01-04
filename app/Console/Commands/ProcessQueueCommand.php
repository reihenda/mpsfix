<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:process-kas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Kas Excel import queue (for shared hosting compatibility)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting queue processing for Kas Excel imports...');
        
        // Process up to 10 jobs per execution (suitable for cron jobs)
        $exitCode = Artisan::call('queue:work', [
            '--queue' => 'default',
            '--tries' => 1,
            '--max-jobs' => 10,
            '--max-time' => 300, // 5 minutes max
            '--sleep' => 3,
            '--timeout' => 300
        ]);
        
        if ($exitCode === 0) {
            $this->info('Queue processing completed successfully.');
        } else {
            $this->error('Queue processing encountered an error.');
        }
        
        return $exitCode;
    }
}
