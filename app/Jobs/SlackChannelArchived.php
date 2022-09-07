<?php

namespace App\Jobs;

use App\Services\SlackService\Channel;
use App\Models\User;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SlackChannelArchived implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $eventData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $eventData)
    {
        $this->eventData = $eventData;
    }

    public function handle()
    {
        tenant()->fresh();
        Log::withContext(['team_id' => tenant()->team_id, ]);
        Log::info('ARCHIVED', ['data' => $this->eventData]);

        if(tenant()->isChannelLogArchiveEnabled){
            $this->sendChannelLogMessage();
        }
    }

    public function sendChannelLogMessage()
    {
        resolve(SlackService::class)->sendMessage(
            conversation: tenant()->channelLogChannel,
            text: ":closed_book: <@" . Arr::get($this->eventData, 'user') . "> archived channel <#" . Arr::get($this->eventData, 'channel') .">",
            blocks: [],
            emoji: ':arrow_down:',
            username: config('services.slack.bot_user_name') . ' - Channel Archived'
        );
    }
}
