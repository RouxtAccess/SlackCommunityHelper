<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\SlackService\Channel;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SlackAutoRemoveMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $channel_id;
    protected string $timestamp;
    protected ?string $slackUserId;
    protected ?string $threadTs;
    protected ?string $text;
    protected array $blocks;
    protected bool $slack_log_enabled;
    protected string $slack_log_channel;
    protected SlackService $slackService;

    public function __construct(string $channel_id, string $timestamp, string $threadTs = null, string $slackUserId = null, string $text = null, $blocks = [])
    {
        $this->channel_id = $channel_id;
        $this->timestamp = $timestamp;
        $this->threadTs = $threadTs;
        $this->slackUserId = $slackUserId;
        $this->text = $text;
        $this->blocks = $blocks ?? [];
        $this->slack_log_enabled = tenant()->isMessageRuleEnabled;
        $this->slack_log_channel = tenant()->messageRuleChannel;
        $this->slackService = resolve(SlackService::class);
    }

    public function handle()
    {
        tenant()->fresh();
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
                'conversation' => $this->channel_id,
                'timestamp' => $this->timestamp,
                'thread_timestamp' => $this->threadTs,
                'slackUserId' => $this->slackUserId,
            ]);
        Log::info('SlackAutoRemoveMessage - Attempting to clean message...');
        // Delete the message

        if(!$this->slack_log_enabled)
        {
            Log::warning('SlackAutoRemoveMessage - Job was fired, but feature is disabled');
                return;
        }

        if(tenant()->messageRuleCustomToken !== null)
        {
            $deleteSuccess = $this->slackService->setToken(tenant()->messageRuleCustomToken)->deleteMessage($this->channel_id, $this->timestamp);
            $this->slackService->setToken();
        }

        if($deleteSuccess?->ok === true)
        {
            // Logging Deleted Message
            $carbonTimestamp = Carbon::make($this->timestamp);
            $topMessage = $this->slackService->sendMessage(
                conversation: $this->slack_log_channel,
                text: "Auto-deleted message from <@{$this->slackUserId}> in <#{$this->channel_id}> :thread:",
                emoji: "boom",
            );
            $threadTimestamp = $topMessage->message->ts;
            $this->slackService->sendMessage(
                conversation: $this->slack_log_channel,
                text: "Message was sent at: {$carbonTimestamp}",
                emoji: "robot_face",
                threadTimestamp: $threadTimestamp,
                username: "Bot - MetaInformation"
            );
            if(Arr::get($this->blocks, '0.type') === 'rich_text')
            {
                $this->slackService->sendMessage(
                    conversation: $this->slack_log_channel,
                    text: $this->text,
                    emoji: "bust_in_silhouette",
                    threadTimestamp: $threadTimestamp,
                    username: 'Bot - OriginalMessage',
                );
            }
            else{
                Log::debug('SlackAutoRemoveMessage - Bot User Blocks', ['blocks' => $this->blocks]);
                $this->slackService->sendMessage(
                    conversation: $this->slack_log_channel,
                    text: $this->text,
                    blocks: $this->blocks,
                    emoji: "robot_face",
                    threadTimestamp: $threadTimestamp,
                    username: 'Bot - OriginalMessage'
                );
            }

            // Ephemerally message the user (if they exist) in the thread (if it exists)
            if($this->slackUserId)
            {
                $this->slackService->sendMessageEphemeral(
                    $this->slackUserId,
                    $this->channel_id,
                    "Unfortunately you're not allowed to post to this channel/thread at this time!",
                    $this->threadTs);
            }
            Log::info('SlackAutoRemoveMessage - Message Cleaned!');
        }
    }
}
