<?php

namespace App\Services\Slack\Traits;

use App\Models\User;
use App\Services\SlackService;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

trait JoinsChannelsFromModal
{
    // Note - this trait should only be used inside a Modal service with the `generateView` function


    function joinChannelAndUpdateViewAccordingly(User $user, object $payload, string $channel)
    {
        $convoInfo = resolve(SlackService::class)->getConversationInfo($channel);
        $failedToJoinMessage = false;
        if($convoInfo->ok !== true)
        {

            Log::debug((new ReflectionClass(static::class))->getShortName() . ' - Unable to join Channel (Private)');
            $failedToJoinMessage = true;
        }
        else if($convoInfo->channel->is_member !== true)
        {
            $canJoinConversationResponse = resolve(SlackService::class)->conversationJoin($channel);
            if($canJoinConversationResponse->ok !== true)
            {
                Log::debug((new ReflectionClass(static::class))->getShortName() . ' - Unable to join public Channel', ['response' => $canJoinConversationResponse]);
                $failedToJoinMessage = true;
            }
        }

        if($failedToJoinMessage)
        {
            $this->updateViewWithChannelWarning($user, $payload->view->id);
        }
        else{
            $this->updateViewWithoutChannelWarning($user, $payload->view->id);
        }
    }

    public function updateViewWithChannelWarning(User $user, string $view_id)
    {
        $newView = $this->generateView($user);
        $newView['blocks'][] = [
            'type' => 'section',
            'text' =>  [
                'type' => 'mrkdwn',
                'text' => ":triangular_flag_on_post: _Please make sure to invite our app!_",
            ],
        ];
        resolve(SlackService::class)->viewUpdate($view_id, $newView);
    }
    public function updateViewWithoutChannelWarning(User $user, string $view_id)
    {
        $newView = $this->generateView($user);
        resolve(SlackService::class)->viewUpdate($view_id, $newView);
    }
}
