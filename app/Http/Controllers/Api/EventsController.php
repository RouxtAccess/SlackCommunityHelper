<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\EventsChallengeRequest;
use App\Http\Requests\Api\EventsRequest;
use App\Jobs\HandleSlackAppHomeOpened;
use App\Jobs\ProcessSlackEventAppMessage;
use App\Jobs\HandleUserProfileChanged;
use App\Jobs\ProcessSlackEventMessage;
use App\Jobs\SendEmojiAddedMessage;
use App\Jobs\SendEmojiRemovedMessage;
use App\Jobs\SendEmojiRenamedMessage;
use App\Jobs\SlackChannelArchived;
use App\Jobs\SlackChannelCreated;
use App\Jobs\SlackChannelDeleted;
use App\Jobs\SlackChannelRenamed;
use App\Jobs\SlackChannelUnarchived;
use App\Jobs\TeamJoinedJob;
use App\Models\Workspace;
use App\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Stancl\Tenancy\Facades\Tenancy;

class EventsController extends Controller
{
    public function action(Request $request)
    {
        // ToDo Challenge Verification
        if(isset($request->event)
            && is_array($request->event)
            && method_exists($this, 'events_' . Arr::get($request->event, 'type')))
        {
            Tenancy::initialize(Workspace::where('team_id', $request->team_id)->firstOrFail());
            Log::withContext(['team_id' => tenant()->team_id, ]);
            return $this->{'events_' . Arr::get($request->event, 'type')}(EventsRequest::createFrom($request));
        }
        if($request->type === 'url_verification')
        {
            return $this->url_verification(EventsChallengeRequest::createFrom($request));
        }

        Log::info('Unable to match request to method call', ['request' => $request->all()]);
        return response()->json(['status' => 'error', 'error' => 'unable to find matching action type [events_' . $request->type . ']'], 422);
    }

    protected function url_verification(EventsChallengeRequest $request): JsonResponse
    {
        Log::info('Url Verification');
        return response()->json(['challenge' => $request->challenge]);
    }

    protected function events_channel_created(EventsRequest $request): JsonResponse
    {
        Log::info('Event-Channel-Created', ['event' => $request->event]);
        SlackChannelCreated::dispatch($request->event);

        return response()->json(['status' => 'success'], 200);
    }
    protected function events_channel_deleted(EventsRequest $request): JsonResponse
    {
        Log::info('Event-Channel-Deleted', ['event' => $request->event]);
        SlackChannelDeleted::dispatch($request->event);

        return response()->json(['status' => 'success'], 200);
    }
    protected function events_channel_rename(EventsRequest $request): JsonResponse
    {
        Log::info('Event-Channel-Renamed', ['event' => $request->event]);
        SlackChannelRenamed::dispatch($request->event);

        return response()->json(['status' => 'success'], 200);
    }
    protected function events_channel_archive(EventsRequest $request): JsonResponse
    {
        Log::info('Event-Channel-Archived', ['event' => $request->event]);
        SlackChannelArchived::dispatch($request->event);

        return response()->json(['status' => 'success'], 200);
    }
    protected function events_channel_unarchive(EventsRequest $request): JsonResponse
    {
        Log::info('Event-Channel-Unarchived', ['event' => $request->event]);
        SlackChannelUnarchived::dispatch($request->event);

        return response()->json(['status' => 'success'], 200);
    }


    protected function events_app_home_opened(EventsRequest $request): JsonResponse
    {
        Log::info('SlackEvents - App Home Opened...',  ['user' => Arr::get($request->all(), 'event.user'), ]);
        HandleSlackAppHomeOpened::dispatch($request->all());
        return response()->json(['status' => 'success'], 200);
    }

    protected function events_team_join(EventsRequest $request)
    {
        Log::info('Event-TeamJoin', ['username' => $request->event['user']['id']]);
        TeamJoinedJob::dispatch($request->event['user']['id']);
        return response()->json(['status' => 'success'], 200);
    }

    protected function events_app_mention(EventsRequest $request)
    {
        if(tenant()->isMessageRuleEnabled) {
            ProcessSlackEventAppMessage::dispatch($request->all());
        }
        return response()->json(['status' => 'success'], 200);
    }




    protected function events_reaction_added(EventsRequest $request)
    {

        return response()->json(['status' => 'success'], 200);
    }

    protected function events_message(EventsRequest $request)
    {
        Log::debug("event_message", ['request' => Arr::get($request->all(), 'event')]);
        ProcessSlackEventMessage::dispatch($request->all());
        return response()->json(['status' => 'success'], 200);
    }

    protected function events_user_profile_changed(EventsRequest $request)
    {
        HandleUserProfileChanged::dispatch($request->all());
        return response()->json(['status' => 'success'], 200);
    }



    public function events_emoji_changed(EventsRequest $request)
    {
        if(!config('slack.emoji_helper.notifications.enabled'))
        {
            return response()->json(['status' => 'success'], 200);
        }
        switch ($request->event['subtype'])
        {
            case 'add':
                SendEmojiAddedMessage::dispatch($request->event['name'], $request->event['value']);
                break;
            case 'remove':
                SendEmojiRemovedMessage::dispatch($request->event['names']);
                break;
            case 'rename':
                SendEmojiRenamedMessage::dispatch($request->event['old_name'], $request->event['new_name'], $request->event['value']);
                break;
        }
        return response()->json(['status' => 'success'], 200);
    }

    protected function withinArray($needle, $haystack)
    {
        if (count($haystack) === 0) {
            return true;
        }

        if(in_array($needle, $haystack, true)) {
            return true;
        }

        return false;
    }


}
