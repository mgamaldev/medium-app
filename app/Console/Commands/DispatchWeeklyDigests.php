<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyDigestJob;
use App\Models\User;
use Illuminate\Console\Command;

class DispatchWeeklyDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digests:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly email digests to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentWeek = now()->format('Y-\WW');

        User::query()->where('subscribed_to_digests', true)
            ->chunkById(500, function ($users) use ($currentWeek) {
                foreach ($users as $user) {
                    if ($user->hasDigestBeenSent($currentWeek)) {
                        continue;
                    }
                    $user->recordDigestSend($currentWeek);

                    SendWeeklyDigestJob::dispatch($user)->onQueue('digests');

                }
            });

    }
}
