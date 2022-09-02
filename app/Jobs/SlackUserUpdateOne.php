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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SlackUserUpdateOne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
                'slackUserId' => $this->user->slack_id,
                'user_id' => $this->user->getKey(),
            ]);
        Log::info('SlackUserUpdateOne - Updating User...');
        $slackUser = SlackService::getUserInfo($this->user->slack_id)->user;
        if (isset($slackUser->is_owner) && isset($slackUser->is_admin)) {
            $type = $slackUser->is_owner ? User::TYPE_WORKSPACE_OWNER : ($slackUser->is_admin ? User::TYPE_WORKSPACE_ADMIN : User::TYPE_MEMBER);
        } elseif ($slackUser->deleted) {
            $type = User::TYPE_DEACTIVATED;
        } else {
            $type = User::TYPE_MEMBER;
        }

        $tempUserData = [
            'name' => $slackUser->real_name ?? $slackUser->name,
            'email' => $slackUser?->profile?->email ?? null,
            'type' => $type,
            'slack_id' => $slackUser->id,
            'slack_nickname' => $slackUser?->profile?->display_name ?? $slackUser->name,
        ];
        if ($this->user->created_at !== null) {
            $tempUserData['created_at'] = Carbon::now();
        }
        $this->user->fill($tempUserData);
        $this->user->save();

        Log::info('SlackUserUpdateOne - Successfully Updated User...');
    }
}
