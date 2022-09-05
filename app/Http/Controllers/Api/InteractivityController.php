<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Slack\Modals\AppSettings\AutoJoinNewChannelSettingsModal;
use App\Services\Slack\Modals\AppSettings\InviteHelperSettingsModal;
use App\Services\Slack\Modals\AppSettings\MessageRuleSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserJoinedLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserUpdateLogSettingsModal;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Modals\MessageRuleView\BotViewModal;
use App\Services\Slack\Modals\MessageRuleView\ChannelViewModal;
use App\Services\Slack\Modals\MessageRuleView\ThreadViewModal;
use App\Services\Slack\Modals\MessageRuleViewModal;
use App\Services\SlackService\SlackConstants;
use App\Services\SlackService\SlackInternalConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class InteractivityController extends Controller
{
    public ?User $user;

    public function interactiveActions(Request $request)
    {
        $payload = json_decode($request->payload, false, 512, JSON_THROW_ON_ERROR);
        Tenancy::initialize(Workspace::where('team_id', $payload->team->id)->firstOrFail());
        Log::withContext(['team_id' => tenant()->team_id, ]);
        $this->user = User::where('slack_id', $payload->user->id)->first();
        switch ($payload->type)
        {
            case SlackInternalConstants::INTERACTIVITY_PAYLOAD_TYPE_BLOCK:
                return $this->handleBlockActions($payload);
            case SlackInternalConstants::INTERACTIVITY_PAYLOAD_TYPE_VIEW_SUBMIT:
                return $this->handleViewSubmit($payload);
            case SlackInternalConstants::INTERACTIVITY_PAYLOAD_TYPE_VIEW_CLOSE:
                return $this->handleViewClose($payload);
            default:
                Log::error('SlackInteractivityController - Unknown Interactive Action', ['payload' => $payload]);
                return response('success', 200);
        }
    }



    public function handleBlockActions(object $payload)
    {
        Log::debug('SlackInteractivityController - Generic Block Action', ['payload' => $payload]);
        $action_id = $payload->actions[0]->action_id;

        if(array_key_exists($action_id, SlackConstants::ACTIONS))
        {
            $functionCall = SlackConstants::ACTIONS[$action_id][1];
            resolve(SlackConstants::ACTIONS[$action_id][0])->$functionCall($this->user, $payload);
            return response('success', 200);
        }

        Log::warning('SlackInteractivityController - Uncaught Block Action', ['payload' => $payload]);

        return response('success', 200);
    }

    public function handleViewSubmit(object $payload)
    {
        Log::debug('SlackInteractivityController - Generic View Submit Action', ['payload' => $payload]);

        if($payload->view->private_metadata === SlackConstants::VIEW_ID_APP_SETTINGS_INVITE_HELPER)
        {
            return resolve(InviteHelperSettingsModal::class)->handleViewSubmit($this->user, $payload);
        }
        if($payload->view->private_metadata === SlackConstants::VIEW_ID_APP_SETTINGS_MESSAGE_RULE)
        {
            return resolve(MessageRuleSettingsModal::class)->handleViewSubmit($this->user, $payload);
        }

        Log::debug('SlackInteractivityController - Uncaught View Submition', ['payload' => $payload]);

        return response('', 200);
    }

    public function handleViewClose(object $payload)
    {
        Log::debug('SlackInteractivityController - View Close Action', ['payload' => $payload]);
        return response('success', 200);
    }
}
