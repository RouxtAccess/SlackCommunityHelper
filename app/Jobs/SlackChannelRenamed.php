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

class SlackChannelRenamed implements ShouldQueue
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


        if(tenant()->isChannelLogRenameEnabled){
            $this->sendChannelLogMessage();
        }
    }

    public function sendChannelLogMessage()
    {
        Log::info('SlackChannelRenamed - Sending Log Message', ['channel' => Arr::get($this->eventData, 'channel.id')]);
        resolve(SlackService::class)->sendMessage(
            conversation: tenant()->channelLogChannel,
            text: ":recycle: Channel <#" . Arr::get($this->eventData, 'channel.id') ."> renamed to `". Arr::get($this->eventData, 'channel.name_normalized') ."`",
            blocks: [],
            emoji: 'arrow_right',
            username: config('services.slack.bot_user_name') . ' - Channel Renamed'
        );
    }
}
