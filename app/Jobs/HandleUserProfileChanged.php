<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleUserProfileChanged implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $requestData;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    public function handle()
    {
        tenant()->fresh();
        Log::withContext(['team_id' => tenant()->team_id, ]);


        $slackUser = json_decode(json_encode(Arr::get($this->requestData, 'event.user'), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $user_id = Arr::get($this->requestData, 'event.user.id');
        Log::info('HandleUserProfileChanged - Data Changed!', ['team_id' => tenant()->team_id, 'user_id'=> $user_id]);


        // Update the important data
        $tempUserData = [
            'name' => $slackUser->real_name ?? $slackUser->name,
            'slack_nickname' => $slackUser?->profile?->display_name ?? $slackUser->name,
        ];
        $user = User::where('slack_id', $user_id)->first();
        if(!$user)
        {
            Log::error('handleUserProfileChanged - Failed to find user', ['user_id'=> $user_id, 'newUserData' => $slackUser]);
            return false;
        }
        $user->fill($tempUserData);
        $dirty = $user->getDirty();
        $logData = [];
        foreach ($dirty as $attribute => $dirtyValue)
        {
            $logData[$attribute] = [
                'old' => $user->getOriginal($attribute),
                'new' => $dirtyValue,
            ];
        }
        if(!empty($logData))
        {
            Log::info('handleUserProfileChanged - Updated User Profile Information', ['user' => $user_id, 'changes' => $logData]);
            if(tenant()->isUserUpdatedEnabled)
            {
                SendUserProfileUpdatedMessage::dispatch($logData, $slackUser, $user);
            }
            $user->save();
        }
        return true;
    }
}
