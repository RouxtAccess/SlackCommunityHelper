<?php

namespace App\Jobs;

use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SlackJoinAllPublicChannels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
            ]);
        Log::info('SlackJoinAllPublicChannels - Joining All Channels...');
        $slackService = resolve(SlackService::class);

        $channels = $slackService->getConversationAll(types: 'public_channel');
        $count = 0;
        foreach ($channels as $channel)
        {
            if($channel->is_member === false)
            {
                $slackService->conversationJoin($channel->id);
                $count++;
            }
        }
        Log::info('SlackJoinAllPublicChannels - Joined All Channels!', ['count' => $count]);

    }
}
