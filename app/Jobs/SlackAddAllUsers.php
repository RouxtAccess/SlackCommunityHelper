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

class SlackAddAllUsers implements ShouldQueue
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
        Log::info('SlackAddAllUsers - Adding all users...');
        $slackService = resolve(SlackService::class);

        $slackUsers = $slackService->getUserAll();
        $processedCount = 0;
        $updatedCount = 0;
        foreach($slackUsers as $userInfo)
        {
            if($userInfo->deleted === true)
            {
                continue;
            }
            $type = User::TYPE_MEMBER;
            if($userInfo->is_owner === true)
            {
                $type = User::TYPE_WORKSPACE_OWNER;
            }
            elseif ($userInfo->is_admin === true)
            {
                $type = User::TYPE_WORKSPACE_ADMIN;
            }
            $user = User::firstOrNew(['slack_id' => $userInfo->id],
                [
                    'name' => $userInfo->profile->real_name,
                    'type' => $type,
                    'slack_nickname' => $userInfo?->profile?->display_name ?? $userInfo->name
                ]);

            if($user->isDirty())
            {
                if(config('logging.log_level') === 'debug')
                {
                    $attributes = $user->getDirty();
                    foreach ($attributes as $dirtyAttributeKey => $dirtyAttributeValue)
                    {
                        $attributes[$dirtyAttributeKey] = [
                            'old' => $user->getOriginal($dirtyAttributeKey),
                            'new' => $dirtyAttributeValue,
                        ];
                    }
                    Log::debug('SlackAddAllUsers - Updating user info...',
                        [
                            'user' => $user->getKey(),
                            'updated-attributes' => $attributes
                        ]);
                }
                $user->save();
                $updatedCount++;
            }
            if($user->wasRecentlyCreated === true)
            {
                UpdateSlackAppHomeTab::dispatch($user);
            }
            $processedCount++;
        }
        Log::info('SlackAddAllUsers - Added all users!', ['processed' => $processedCount, 'updated' => $updatedCount]);
    }
}
