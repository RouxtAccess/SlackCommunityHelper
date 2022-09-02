<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class HandleSlackAppHomeOpened implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $slack_id = Arr::get($this->data, 'event.user');

        $user = User::firstOrCreate(['slack_id' => $slack_id],
            [
                'slack_id' => $slack_id,
                'name' => 'CreatedByAppHomeEvent',
                'slack_nickname' => 'CreatedByAppHomeEvent',
            ]);
        if($user->wasRecentlyCreated)
        {
            Log::info('HandleSlackAppHomeOpened - Created new user', ['user' => $user->getKey(), 'workspace' => tenant()?->getKey()]);
            // ToDo - Cache this or something
            Log::info('HandleSlackAppHomeOpened - Updating Slack Home View...', ['user' => $user->getKey(), 'workspace' => tenant()?->getKey()]);
            Bus::chain([
                new GetSlackInfoForUser($slack_id, $user),
                new UpdateSlackAppHomeTab($user),
            ])->dispatch();
            return;
        }
        // ToDo - Cache this or something
        Log::info('HandleSlackAppHomeOpened - Updating Slack Home View...', ['user' => $user->getKey(), 'workspace' => tenant()?->getKey()]);
        Bus::chain([
            new UpdateSlackAppHomeTab($user),
        ])->dispatch();
    }
}
