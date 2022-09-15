<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetSlackInfoForUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $slack_user_id;
    public ?User $user;

    public function __construct($slack_user_id, ?User $user = null)
    {
        $this->slack_user_id = $slack_user_id;
        $this->user = $user;
    }


    public function handle()
    {
        tenant()->fresh();
        Log::withContext(['team_id' => tenant()->team_id, 'user_slack_id' => $this->slack_user_id]);

        if(!$this->user){
            $this->user = User::firstOrCreate(['slack_id' => $this->slack_user_id],
                [
                    'name' => 'CreatedByGetSlackInfoForUser',
                    'slack_nickname' => 'CreatedByGetSlackInfoForUser',
                ]);
        }

        $slackService = resolve(SlackService::class);

        $userResponse = $slackService->getUserInfo($this->user->slack_id);
        if($userResponse->ok !== true)
        {
            Log::warning("GetSlackEmailForUser - Couldn't find slack user, this was probably a bot account");
            return;
        }
        $userInfo = $userResponse->user;

        #$avatar = $userInfo->profile->image_original ?? $userInfo->profile->image_512 ?? $userInfo->profile->image_192;

        $type = User::TYPE_MEMBER;
        if($userInfo->is_owner === true)
        {
            $type = User::TYPE_WORKSPACE_OWNER;
        }
        elseif ($userInfo->is_admin === true)
        {
            $type = User::TYPE_WORKSPACE_ADMIN;
        }

        $this->user->fill(
            [
                'name' => $userInfo->profile->real_name,
                'type' => $type,
                'slack_nickname' => $userInfo?->profile?->display_name ?? $userInfo->name
            ]);
        if($this->user->isDirty())
        {
            if(config('logging.log_level') === 'debug')
            {
                $attributes = $this->user->getDirty();
                foreach ($attributes as $dirtyAttributeKey => $dirtyAttributeValue)
                {
                    $attributes[$dirtyAttributeKey] = [
                        'old' => $this->user->getOriginal($dirtyAttributeKey),
                        'new' => $dirtyAttributeValue,
                    ];
                }
                Log::debug('GetSlackEmailForUser - Updating user info...',
                    [
                        'user' => $this->user->getKey(),
                        'updated-attributes' => $attributes
                    ]);
            }
            $this->user->save();
        }
    }
}
