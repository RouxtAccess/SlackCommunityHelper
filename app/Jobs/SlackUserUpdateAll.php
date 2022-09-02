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

class SlackUserUpdateAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
            ]);
        Log::info('SlackUserUpdateAll - Syncing All Users...');
        while(true)
        {
            $cursor = $cursor ?? null;

            // Get Paginated list of slack users
            $data = SlackService::getUserList(1000, $cursor);
            foreach ($data->members as $slackUser)
            {
                $user = User::where('slack_id', $slackUser->id)->first() ?? User::make();
                if(isset($slackUser->is_owner) && isset($slackUser->is_admin))
                {
                    $type = $slackUser->is_owner ? User::TYPE_WORKSPACE_OWNER : ($slackUser->is_admin ? User::TYPE_WORKSPACE_ADMIN : User::TYPE_MEMBER);
                }
                elseif($slackUser->deleted)
                {
                    $type = User::TYPE_DEACTIVATED;
                }
                else {
                    $type = User::TYPE_MEMBER;
                }
                Log::debug('SlackUserUpdateAll - Resyncing User!', ['slack_id' => $slackUser->id, 'type' => $type]);
                $tempUserData = [
                    'name' => $slackUser->real_name ?? $slackUser->name,
                    'email' => $slackUser?->profile?->email ?? null,
                    'type' => $type,
                    'slack_id' => $slackUser->id,
                    'slack_nickname' => $slackUser?->profile?->display_name ?? $slackUser->name,
                ];
                if($user->created_at !== null)
                {
                    $tempUserData['created_at'] = Carbon::now();
                }
                $user->fill($tempUserData);
                $user->save();

            }
            $cursor = $data?->response_metadata?->next_cursor ?? null;
            if(is_null($cursor) || $cursor === "")
            {
                break;
            }
        }
        Log::info('SlackUserUpdateAll - Successfully Synced All Users...');
    }
}
