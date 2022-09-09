<?php

namespace App\Services;

use App\Http\Requests\Api\EventsRequest;
use App\Services\SlackService\SlackInternalConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService {

    protected $apiEndpoint;
    protected $bot_oauth_access;


    public function __construct()
    {
        $this->apiEndpoint = "https://slack.com/api/";
        $this->setToken();
    }

    public function setToken(string $token = null)
    {
        $token = $token ?? tenant()?->slack_bot_access_token;
        $this->bot_oauth_access = $token;
        return $this;
    }

    public function getUserInfo(string $username) : object
    {
        Log::debug('SlackService - Getting user Info...', ['username' => $username]);
        $endpoint = 'users.info';
        $payload = [
            'user' => $username,
        ];

        return $this->get($endpoint, $payload);
    }
    public function getUserAll(int $limit = 1000): Collection
    {
        Log::debug('SlackService - Getting All Users...');
        $collection = collect();
        $cursor = null;
        while(true)
        {
            $data = $this->getUserList(limit: $limit);
            if($data->ok === false && $data->error === SlackInternalConstants::API_ERROR_LIMIT_REQUIRED)
            {
                Log::warning('SlackService - Getting All Users - Dropping limit', ['limit' => $limit]);
                $limit = (int) ($limit/2);
                continue;
            }
            foreach($data->members as $user)
            {
                $collection->push($user);
            }
            $cursor = $data?->response_metadata?->next_cursor ?? null;
            if(!$cursor)
            {
                break;
            }
        }
        return $collection;
    }
    public function getUserList(int $limit = 1000, string $cursor = null) : \stdClass
    {
        Log::debug('SlackService - Getting User List...');
        $endpoint = 'users.list';
        $payload = [
            'limit' => $limit
        ];
        if($cursor !== null)
            $payload['cursor'] = $cursor;

        return $this->get($endpoint, $payload);
    }

    public function getConversationAll(bool $exclude_archived = true, string $types = 'public_channel,private_channel'): Collection
    {
        Log::debug('SlackService - Getting All Conversations...');
        $conversations = collect();
        $cursor = null;
        while(true)
        {
            $data = $this->getConversationList(cursor: $cursor, exclude_archived: $exclude_archived, limit: 200, types: $types);
            foreach($data->channels as $channel)
            {
                $conversations->push($channel);
            }
            $cursor = $data?->response_metadata?->next_cursor ?? null;
            if(!$cursor)
            {
                break;
            }
        }
        return $conversations;
    }

    public function getConversationList(string $cursor = null, bool $exclude_archived = true, int $limit = 100, string $types ='public_channel,private_channel,mpim,im') : object
    {
        Log::debug('SlackService - Getting List of Conversations...', ['cursor' => $cursor, 'exclude_archived' => $exclude_archived, 'limit' => $limit, 'types' => $types]);

        $endpoint = 'conversations.list';
        $payload = [
            'exclude_archived' => $exclude_archived,
            'limit' => $limit,
            'types' => $types,
        ];
        if($cursor !== null)
        {
            $payload['cursor'] = $cursor;
        }

        return $this->get($endpoint, $payload);
    }

    public function getConversationInfo(string $channel) : object
    {
        Log::debug('SlackService - Getting info from Conversation...', ['channel' => $channel]);

        $endpoint = 'conversations.info';
        $payload = [
            'channel' => $channel,
        ];

        return $this->get($endpoint, $payload);
    }

    public function conversationJoin(string $channel) : object
    {
        Log::debug('SlackService - Joining Conversation...', ['channel' => $channel]);

        $endpoint = 'conversations.join';
        $payload = [
            'channel' => $channel,
        ];

        return $this->post($endpoint, $payload);
    }

    public function conversationCreate(string $channelName, bool $is_private = false) : object
    {
        Log::debug('SlackService - Creating Conversation...', ['channelName' => $channelName]);

        $endpoint = 'conversations.create';
        $payload = [
            'name' => $channelName,
            'is_private' => $is_private,
        ];

        return $this->post($endpoint, $payload);
    }

    public function conversationInvite(string $channel, string $users) : object
    {
        Log::debug('SlackService - Inviting Users to Conversation...', ['channel' => $channel, 'users' => $users]);

        $endpoint = 'conversations.invite';
        $payload = [
            'channel' => $channel,
            'users' => $users,
        ];

        return $this->post($endpoint, $payload);
    }


    public function sendMessage(string $conversation, $text, array $blocks = [], $emoji = 'robot_face', $threadTimestamp = null, $username = null) : object
    {
        Log::debug('SlackService - Sending Message...', ['conversation' => $conversation]);
        $endpoint = 'chat.postMessage';
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'text' => $text,
            'link_names' => true,
            'icon_emoji' => $emoji,
            'username' => $username ?? config('services.slack.bot_user_name'),
        ];
        if(!empty($blocks)){
            $payload['blocks'] = json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        if($threadTimestamp !== null){
            $payload['thread_ts'] = $threadTimestamp;
        }
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Sent Message!', ['conversation' => $conversation]);

        return $response;
    }
    public function sendMessageEphemeral(string $slackUserId, string $conversation, string $text, $threadTimestamp = null, $blocks = null, $emoji = 'robot_face',  $username = null, $unfurl_links = false, $attachments = null) : \stdClass
    {
        Log::debug('SlackService - Sending Ephemeral...', ['conversation' => $conversation]);
        $endpoint = 'chat.postEphemeral';
        $payload = [
            'channel' => $conversation,
            'user' => $slackUserId,
            'text' => $text,
            'link_names' => true,
            'icon_emoji' => $emoji,
            'username' => $username ?? config('services.slack.bot_user_name'),
            'unfurl_links' => $unfurl_links,
        ];
        if($blocks !== null){
            $payload['blocks'] = json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        if($threadTimestamp !== null){
            $payload['thread_ts'] = $threadTimestamp;
        }
        if($attachments !== null){
            $payload['attachments'] = json_encode($attachments, JSON_THROW_ON_ERROR);
        }
        return $this->post($endpoint, $payload);
    }

    public function sendMessageAsThreadResponse(string $conversation, string $timestamp, ?string $text = null, ?array $blocks = null, ?string $emoji = null) :object
    {
        Log::debug('SlackService - Sending Initial Thread Response...', ['conversation' => $conversation, 'timestamp' => $timestamp,]);
        $endpoint = 'chat.postMessage';
        $payload = [
            'channel' => $conversation,
            'thread_ts' => $timestamp,
            'text' => $text,
            'link_names' => true,
            'icon_emoji' => $emoji,
            'username' => config('services.slack.bot_user_name'),
        ];
        if($blocks !== null){
            $payload['blocks'] = json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Sent Initial Thread Response!', ['conversation' => $conversation]);

        return $response;
    }



    public function deleteMessage(string $conversation, string $timestamp) : \stdClass
    {
        Log::debug('SlackService - Deleting Message...', ['conversation' => $conversation, 'timestamp' => $timestamp,]);
        $endpoint = 'chat.delete';
        $payload = [
            'channel' => $conversation,
            'ts' => $timestamp,
        ];
        return $this->post($endpoint, $payload);
    }

    public function getPermaLink(string $conversation, string $timestamp)
    {
        Log::debug('SlackService - Getting Perma Link...', ['conversation' => $conversation, 'timestamp' => $timestamp]);
        $endpoint = 'chat.getPermalink';
        $payload = [
            'channel' => $conversation,
            'message_ts' => $timestamp,
        ];
        return $this->get($endpoint, $payload);
    }


    public function getBotInfo() : object
    {
        Log::debug('SlackService - Getting Bot Info...');
        $endpoint = 'bots.info';
        $payload = [];
        return $this->get($endpoint, $payload);
    }


    public function addReaction($conversation, $timestamp, $emoji)
    {
        Log::debug('SlackService - Adding Emoji Response', ['conversation' => $conversation, 'timestamp' => $timestamp, 'emoji' => $emoji]);
        $endpoint = 'reactions.add';
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'timestamp' => $timestamp,
            'name' => $emoji,
        ];
        return $this->post($endpoint, $payload);
    }

    public function viewPublish(array $payload): object
    {
        Log::debug('SlackService - Publishing View...', ['payload' => $payload]);
        $endpoint = 'views.publish';
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Published View!', ['user' => $payload['user_id'] ?? 'unknown', 'response' => $response]);

        return $response;
    }

    public function viewOpen(string $trigger_id, array $view): object
    {
        Log::debug('SlackService - Opening View...', ['trigger_id' => $trigger_id, 'view' => $view]);
        $endpoint = 'views.open';
        $payload = [
            'trigger_id' => $trigger_id,
            'view' => json_encode($view, JSON_THROW_ON_ERROR),
        ];
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Opened View!', ['trigger_id' => $trigger_id]);

        return $response;
    }

    public function viewUpdate(string $view_id, array $view): object
    {
        Log::debug('SlackService - Updating View...', ['view_id' => $view_id, 'view' => $view]);
        $endpoint = 'views.update';
        $payload = [
            'view_id' => $view_id,
            'view' => json_encode($view, JSON_THROW_ON_ERROR),
        ];
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Updated View!', ['view_id' => $view_id]);

        return $response;
    }

    public function viewPush(string $trigger_id, array $view): object
    {
        Log::debug('SlackService - Pushing New View...', ['trigger_id' => $trigger_id, 'view' => $view]);
        $endpoint = 'views.push';
        $payload = [
            'trigger_id' => $trigger_id,
            'view' => json_encode($view, JSON_THROW_ON_ERROR),
        ];
        $response = $this->post($endpoint, $payload);
        Log::debug('SlackService - Pushed New View!', ['trigger_id' => $trigger_id, 'response' => $response]);

        return $response;
    }


    public function getUsergroupsListUsers(string $usergroup_id): object
    {
        Log::debug('SlackService - Geting User List for Usergroup...', ['usergroup_id' => $usergroup_id,]);
        $endpoint = 'usergroups.users.list';
        $payload = [
            'usergroup' => $usergroup_id,
        ];
        $response = $this->get($endpoint, $payload);
        Log::debug('SlackService - Found User List for Usergroup!', ['usergroup_id' => $usergroup_id, 'response' => $response]);

        return $response;
    }



    public function get(string $endpoint, array $payload): object
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->bot_oauth_access])
            ->get($this->apiEndpoint.$endpoint, $payload)
            ->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Unsuccessful GET Request',  [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
            ]);
        }
        return $response;
    }

    public function post(string $endpoint, array $payload): object
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->bot_oauth_access])
            ->asJson()
            ->post($this->apiEndpoint.$endpoint, $payload)
            ->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Unsuccessful POST Request',  [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
            ]);
        }
        return $response;
    }



    ////////////////////////////////////////////////////////////////////
    /// Old stuff
    ////////////////////////////////////////////////////////////////////


    public function getMessagesFromConversation($conversation, $pages = 10)
    {
        $endpoint = 'conversations.history';
        $nextCursor = false;
        $messages = collect();
        do{
            $payload = [
                'token' => $this->bot_oauth_access,
                'channel' => $conversation,
                'limit' => 100
            ];
            if($nextCursor){
                $payload['cursor'] = $nextCursor;
            }
            $response = json_decode($this->guzzle->get($this->apiEndpoint . $endpoint, ['query' => $payload])->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $messages = $messages->merge(collect($response['messages']));
            $nextCursor = $response['has_more'] ? $response['response_metadata']['next_cursor'] : false;
            $pages--;
        }
        while($pages > 0 && $nextCursor);
        return $messages;
    }

    public function getSpecificMessage($conversation, $timestamp)
    {
        $endpoint = 'conversations.history';
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'latest' => $timestamp,
            'inclusive' => 1,
            'limit' => 1
        ];
        return json_decode($this->guzzle->get($this->apiEndpoint . $endpoint, ['query' => $payload])->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }


    public static function getSpecificMessageClean($conversation, $timestamp) : \stdClass
    {
        Log::debug('SlackService - Getting Specific Message...', ['conversation' => $conversation, 'timestamp' => $timestamp]);
        $endpoint = 'conversations.history';
        $payload = [
            'channel' => $conversation,
            'latest' => $timestamp,
            'inclusive' => 1,
            'limit' => 1
        ];
        return static::get($endpoint, $payload);
    }


    public static function getSpecificThreadResponse(string $conversation, string $threadTimestamp, $timestamp) : \stdClass
    {
        Log::debug('SlackService - Getting Specific Thread Response...', ['conversation' => $conversation, 'timestamp' => $timestamp]);
        $endpoint = 'conversations.replies';
        $payload = [
            'channel' => $conversation,
            'ts' => $threadTimestamp,
            'latest' => $timestamp,
            'inclusive' => 1,
            'limit' => 1
        ];
        return static::get($endpoint, $payload);
    }

    public function sendOrUpdateThreadedMessage($conversation, $timestamp, $message, EventsRequest $request)
    {
        $parentMessage = $this->getSpecificMessage($conversation, $timestamp);
        $threadTimestamp = $parentMessage['messages'][0]['thread_ts'] ?? $parentMessage['messages'][0]['ts'];

        $ourMessageTimestamp = Cache::tags(['Conversation:'.$conversation, 'parent_thread_message_timestamp'])->get($threadTimestamp);

        if($ourMessageTimestamp){
            Cache::increment('spamSaved');
            Log::debug('Initial Message already sent', ['conversation' => $conversation, 'timestamp' => $ourMessageTimestamp]);
            $users = Cache::tags(['Conversation:'.$conversation, 'users'])->get($ourMessageTimestamp);
            if(is_array($users) && !in_array($request->event['user'], $users)){
                $users[] = $request->event['user'];
                Cache::tags(['Conversation:'.$conversation, 'users'])->put($ourMessageTimestamp, $users, $this->cache_default_ttl);
                $this->updateExistingMessage($conversation, $message, $ourMessageTimestamp, $users);
            }
            return true;
        }

        $response = $this->sendMessageAsThreadResponse($conversation, $message, $threadTimestamp, $request->event['user'], $message[0]['text']['text']);
        Cache::increment('messagesSent');
        Cache::tags(['Conversation:'.$conversation, 'parent_thread_message_timestamp'])->put($threadTimestamp, $response['ts'], $this->cache_default_ttl);
        Cache::tags(['Conversation:'.$conversation, 'users'])->put($response['ts'], [$request->event['user']], $this->cache_default_ttl);


        return $response;

    }

    public function updateExistingMessage($conversation, $message, $timestamp, $users)
    {
        Log::debug('Updating Existing Message', ['conversation' => $conversation, 'timestamp' => $timestamp, 'users' => $users]);
        $endpoint = 'chat.update';
        $message[] = $this->appendUsersBlock($users);
        $payload = [
            'token' => $this->bot_oauth_access,
            'channel' => $conversation,
            'ts' => $timestamp,
            'blocks' => json_encode($message, JSON_THROW_ON_ERROR),
        ];
        return json_decode($this->guzzle->post($this->apiEndpoint.$endpoint, ['form_params' => $payload])->getBody()->getContents(),true);
    }

    public static function sendMessageNew($conversation, $text, $blocks = null, $emoji = null, $threadTimestamp = null, $username = null, $unfurl_links = false, $attachments = null) : \stdClass
    {
        Log::info('SlackService - Sending Message...', ['conversation' => $conversation]);
        $endpoint = 'chat.postMessage';
        $payload = [
            'channel' => $conversation,
            'text' => $text,
            'link_names' => true,
            'icon_emoji' => $emoji,
            'username' => $username,
            'unfurl_links' => $unfurl_links,
        ];
        if($blocks !== null){
            $payload['blocks'] = json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        if($threadTimestamp !== null){
            $payload['thread_ts'] = $threadTimestamp;
        }
        if($attachments !== null){
            $payload['attachments'] = json_encode($attachments, JSON_THROW_ON_ERROR);
        }
        return static::post($endpoint, $payload);
    }




    public static function getEmojiList($userToken = null)
    {
        $userToken = $userToken ?? config('slack.bot_oauth_access');
        Log::debug('Get Emoji List');
        $apiEndpoint = config('slack.api_endpoint');
        $endpoint = 'emoji.list';
        $payload = [
            'token' => $userToken,
        ];
        return Http::get($apiEndpoint.$endpoint, $payload)->json();
    }




    public static function getConversationsList(bool $excludeArchived = true, string $types ="public_channel,private_channel", int $limit = 1000, string $cursor = null) : \stdClass
    {
        Log::info('SlackService - Getting Conversations List...');

        $token = config('slack.bot_oauth_access');
        $apiEndpoint = config('slack.api_endpoint');
        $endpoint = 'conversations.list';
        $payload = [
            'limit' => $limit,
            'exclude_archived' => $excludeArchived,
            'types' => $types,
        ];
        if($cursor !== null)
            $payload['cursor'] = $cursor;

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($apiEndpoint.$endpoint, $payload)->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Getting Conversations List Failed',  [
                'limit' => $limit,
                'exclude_archived' => $excludeArchived,
                'types' => $types,
                'response_data' => $response,
            ]);
        }
        return $response;
    }

    public static function getConversationsInfo(string $conversation_id, bool $include_locale = true, bool $include_num_members = true) : \stdClass
    {
        Log::info('SlackService - Getting Conversations Info...', ['conversation_id' => $conversation_id]);

        $token = config('slack.bot_oauth_access');
        $apiEndpoint = config('slack.api_endpoint');
        $endpoint = 'conversations.info';
        $payload = [
            'channel' => $conversation_id,
            'include_locale' => $include_locale,
            'include_num_members' => $include_num_members,
        ];

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($apiEndpoint.$endpoint, $payload)->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Getting Conversations Info Failed',  [
                'conversation_id' => $conversation_id,
                'include_locale' => $include_locale,
                'include_num_members' => $include_num_members,
            ]);
        }
        return $response;
    }


    public static function getUserGroupList(bool $include_users = true, bool $include_disabled = true,  bool $include_count = true ) : \stdClass
    {
        Log::info('SlackService - Getting UserGroup List...');

        $token = config('slack.bot_oauth_access');
        $apiEndpoint = config('slack.api_endpoint');
        $endpoint = 'usergroups.list';
        $payload = [
            'include_users' => $include_users,
            'include_disabled' => $include_disabled,
            'include_count' => $include_count,
        ];

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($apiEndpoint.$endpoint, $payload)->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Getting UserGroup List Failed',  [
                'include_users' => $include_users,
                'include_disabled' => $include_disabled,
                'include_count' => $include_count,
                'response_data' => $response,
            ]);
        }
        return $response;
    }

    public static function getUserGroupInfo(string $conversation_id, bool $include_locale = true, bool $include_num_members = true) : \stdClass
    {
        Log::info('SlackService - Getting Conversations Info...', ['conversation_id' => $conversation_id]);

        $token = config('slack.bot_oauth_access');
        $apiEndpoint = config('slack.api_endpoint');
        $endpoint = 'conversations.info';
        $payload = [
            'channel' => $conversation_id,
            'include_locale' => $include_locale,
            'include_num_members' => $include_num_members,
        ];

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($apiEndpoint.$endpoint, $payload)->object();
        if($response->ok !==true)
        {
            Log::error('SlackService - Getting Conversations Info Failed',  [
                'conversation_id' => $conversation_id,
                'include_locale' => $include_locale,
                'include_num_members' => $include_num_members,
            ]);
        }
        return $response;
    }



}
