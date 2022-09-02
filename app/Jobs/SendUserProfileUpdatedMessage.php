<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendUserProfileUpdatedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected array $logChanges, protected $userData, protected User $user)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('SendUserProfileUpdatedMessage', ['user' => $this->user->getKey()]);
        $slackService = resolve(SlackService::class);

        $text = "User Updated : {$this->user->slackUserRead}\n";
        if(isset($this->logChanges['name']))
        {
            $text .= "Real Name: `{$this->logChanges['name']['old']}` ==> `{$this->logChanges['name']['new']}`\n";
        }

        if(isset($this->logChanges['slack_nickname']))
        {
            $text .= "Display Name: `{$this->logChanges['slack_nickname']['old']}` ==> `{$this->logChanges['slack_nickname']['new']}`";
        }
        $blocks = [
            [
                "type" => "section",
                'text' =>
                    [
                        'type' => "mrkdwn",
                        'text' => trim($text)
                    ]
            ],
        ];
        $slackService->sendMessage(
            tenant()->userUpdatedChannel,
            "User Updated! {$this->user->slackUserRead} was updated",
            $blocks,
            'bust_in_silhouette',
            null,
            'User Profile Change!'
        );
    }
}
