<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDay())
            ->get();

        $summary = [];

        foreach ($failedJobs as $job) {
            $payloadData = json_decode($job->payload, true);
            $jobClass = $payloadData['displayName'] ?? 'Unknown';

            if (! isset($summary[$jobClass])) {
                $summary[$jobClass] = 0;
            }

            $summary[$jobClass]++;
        }

        if (empty($summary)) {
            $this->info('No failed jobs older than 24 hours');

            return;
        }

        $this->components->twoColumnDetail('Failed Job Summary (last 24 hours)');

        foreach ($summary as $job => $count) {
            $this->components->task("$job", function () {});
        }

        $formattedData = [];

        foreach ($summary as $className => $count) {
            $formattedData[] = [
                'Job' => $className,
                'Failed Count' => $count,
            ];
        }

        usort($formattedData, function ($a, $b) {
            return $b['Failed Count'] <=> $a['Failed Count'];
        });

        $this->table(['job_class', 'failed_count'], $formattedData);

    }
}
