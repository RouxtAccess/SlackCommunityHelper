<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SlackService;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TeamJoinedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $requestData;
    private string $slackUserId;
    private $enabled;
    private $channel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $slackUserId)
    {
        $this->slackUserId = $slackUserId;
        $this->enabled = tenant()->isUserJoinedEnabled;
        $this->channel = tenant()->userJoinedChannel;
    }

    public function handle()
    {
        tenant()->fresh();
        Log::withContext(['team_id' => tenant()->team_id, ]);
        $slackService = resolve(SlackService::class);


        // Get full user data
        $userData = $slackService->getUserInfo($this->slackUserId);

        Log::info('TeamJoinedJob - New user joined!', ['slack_user_id' => $userData->user->id]);
        // Store new User
        User::create([
            'name' => $userData->user->real_name ?? $userData->user->name,
            'type' => User::TYPE_MEMBER,
            'slack_id' => $userData->user->id,
            'slack_nickname' => $userData->user->profile->display_name,
        ]);


        if($this->enabled)
        {
            $message = "New User: @{$userData->user->name} | {$userData->user->real_name}";
            $blocks = [
                [
                    "type" => "section",
                    "fields" => [
                        [
                            "type" => "mrkdwn",
                            "text" => ":heavy_plus_sign: *{$userData->user->real_name}* <@{$userData->user->name}>",
                        ],
                    ]
                ]
            ];
            $emoji = 'bust_in_silhouette';
            $slackService->sendMessage($this->channel, $message, $blocks, $emoji, null, 'New User Created!');
        }

    }
}
