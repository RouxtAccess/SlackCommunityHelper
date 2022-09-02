<?php

namespace App\Jobs;

use App\Models\BotRule;
use App\Models\ChannelRule;
use App\Models\ThreadRule;
use App\Models\User;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Tenancy;

class ProcessSlackEventAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $requestData;
    protected string $channel;
    protected string $timestamp;
    protected ?string $slack_user_id;
    public SlackService $slackService;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
        $this->channel = Arr::get($this->requestData, 'event.channel');
        $this->timestamp = Arr::get($this->requestData, 'event.thread_ts') ?? Arr::get($this->requestData, 'event.ts');
        $this->slack_user_id = Arr::get($this->requestData, 'event.user', Arr::get($this->requestData, 'event.bot_id', Arr::get($this->requestData, 'event.previous_message.user')));
        if(!$this->slack_user_id)
        {
            Log::error("WELP", ['data' => $requestData]);
        }
        $this->slackService = resolve(SlackService::class);
    }

    public function handle()
    {
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
                'slack_user' => $this->slack_user_id,
                'conversation' => $this->channel,
                'timestamp' => $this->timestamp,
            ]);



        $theirMessageParts = explode(' ',Arr::get($this->requestData, 'event.text'));

        // check if mention is at the start, if not, ignore it
        $ourMention = str_replace(array('@', '>', '<'), '', $theirMessageParts[0]);
        if($ourMention !== tenant()->user_id)
        {
            Log::debug("AppMention - Ignored because we're just being mentioned, not asked", ['our_mention' => $ourMention]);
            return null;
        }

        $this->user = User::where('slack_id', $this->slack_user_id)->first();

        if(!$this->user)
        {
            GetSlackInfoForUser::dispatchSync($this->slack_user_id);
            $this->user = User::where('slack_id', $this->slack_user_id)->first();
        }

        if(!$this->user->isWorkspaceAdmin())
        {
            Log::info("AppMention - Non admin message");
            $this->sendNonAdminMessage();
            return null;
        }

        Log::info("AppMention - Parts", ['parts' => $theirMessageParts]);

        if(!isset($theirMessageParts[1])){
            $this->sendCannotUnderstandMessage('Missing Feature [Help|BotMessages|ChannelRule|ThreadRule]');
            return null;
        }


        switch(strtolower($theirMessageParts[1])){
            case 'help':
                $this->sendHelpMessage();
                break;
            case 'botmessages':
                $this->handleBotMessage($theirMessageParts);
                break;
            case 'channelrule':
                $this->handleChannelRule($theirMessageParts);
                break;
            case 'threadrule':
                $this->handleThreadRule($theirMessageParts);
                break;
            default:
                $this->sendCannotUnderstandMessage($theirMessageParts[1]);
                break;
        }
        return true;
    }

    protected function handleBotMessage($theirMessageParts)
    {
        if(!isset($theirMessageParts[2]))
            $this->sendCannotUnderstandMessage('Missing Action `[Allow|Deny]`');


        switch(strtolower($theirMessageParts[2])){
            case 'allow':
                $botRule = BotRule::find(['channel_id' => $this->channel]);
                if($botRule)
                {
                    $botRule->delete();
                    BotRule::refreshRuleDataCache();
                }
                Log::info("AppMention - Bot Rule Deleted");
                $this->sendSuccessMessage("Channel Bot Rule - Bot messages are now allowed in this channel");
                break;

            case 'deny':
                BotRule::updateOrCreate(['channel_id' => $this->channel]);
                BotRule::refreshRuleDataCache();
                Log::info("AppMention - Bot Rule Added");
                $this->sendSuccessMessage("Channel Bot Rule - Bot messages are now blocked in this channel!");
                break;

            default:
                $this->sendCannotUnderstandMessage($theirMessageParts[2]);
                break;
        }
    }

    protected function handleChannelRule($theirMessageParts)
    {
        if(!isset($theirMessageParts[2])){
            $this->sendCannotUnderstandMessage('Missing Action [AllowTop|DenyTop|AllowThread|DenyThread|Remove]');
        }
        $userData = $this->getUserData(array_slice($theirMessageParts, 3));
        $userDataString = $userData['string'];
        $channelRule = ChannelRule::where('channel_id', $this->channel)->first();
        if(!$channelRule && in_array(strtolower($theirMessageParts[2]), ['allowtop', 'denytop', 'allowthread', 'denythread']))
            $channelRule = ChannelRule::make(['channel_id' => $this->channel]);

        switch(strtolower($theirMessageParts[2])){
            case 'allowtop':
                Log::info("AppMention - Channel Rule Added/Updated AllowTop");
                $channelRule->allow_list_top_level_enabled = true;
                $channelRule->allow_list_top_level = $userData;
                $channelRule->save();
                ChannelRule::refreshRuleDataCache();
                $this->sendSuccessMessage("ChannelRule - Top Levels Messages are now locked down to only [{$userDataString}]!");
                break;
            case 'denytop':
                Log::info("AppMention - Channel Rule Added/Updated DenyTop");
                $channelRule->allow_list_top_level_enabled = false;
                $channelRule->deny_list_top_level = $userData;
                $channelRule->save();
                ChannelRule::refreshRuleDataCache();
                $this->sendSuccessMessage("ChannelRule - Top Levels Messages are now blocked for [{$userDataString}]!");
                break;
            case 'allowthread':
                Log::info("AppMention - Channel Rule Added/Updated AllowThread");
                $channelRule->allow_list_thread_enabled = true;
                $channelRule->allow_list_thread = $userData;
                $channelRule->save();
                ChannelRule::refreshRuleDataCache();
                $this->sendSuccessMessage("ChannelRule - Thread Messages are now locked down to only [{$userDataString}]!");
                break;
            case 'denythread':
                Log::info("AppMention - Channel Rule Added/Updated DenyThread");
                $channelRule->allow_list_thread_enabled = false;
                $channelRule->deny_list_thread = $userData;
                $channelRule->save();
                ChannelRule::refreshRuleDataCache();
                $this->sendSuccessMessage("ChannelRule - Thread Messages are now blocked for [{$userDataString}]!");
                break;
            case 'remove':
                Log::info("AppMention - Channel Rule Deleted");
                if($channelRule->delete())
                {
                    $this->sendSuccessMessage("Found and deleted ChannelRule!");
                    ChannelRule::refreshRuleDataCache();
                }
                else
                    $this->sendErrorMessage("No ChannelRule found to delete!");

                break;
            default:
                $this->sendCannotUnderstandMessage($theirMessageParts[2]);
                break;
        }
    }

    protected function handleThreadRule($theirMessageParts)
    {
        if(!isset($theirMessageParts[2]))
            $this->sendCannotUnderstandMessage('Missing Action `[Allow|Deny|Remove]`');

        $userData = $this->getUserData(array_slice($theirMessageParts, 3));
        $userDataString = $userData['string'];
        $threadRule = ThreadRule::where('channel_id', $this->channel)
            ->where('timestamp', $this->timestamp)
            ->first();


        switch(strtolower($theirMessageParts[2])){
            case 'allow':
                if(!$threadRule)
                {
                    $threadRule = ThreadRule::make(['timestamp' => $this->timestamp, 'channel_id' => $this->channel]);
                    $threadRule->getPermaLink();
                }
                $threadRule->allow_list_enabled = true;
                $threadRule->allow_list = $userData;
                $threadRule->save();
                ThreadRule::refreshRuleDataCache();
                Log::info("AppMention - ThreadRule " . ($threadRule->wasRecentlyCreated ? 'Created' : 'Updated'));
                $this->sendSuccessMessage("ThreadRule " .($threadRule->wasRecentlyCreated ? 'New' : 'Updated') . " - Messages are now locked down to only [{$userDataString}]!");
                break;
            case 'deny':
                if(!$threadRule)
                {
                    $threadRule = ThreadRule::make(['timestamp' => $this->timestamp, 'channel_id' => $this->channel]);
                    $threadRule->getPermaLink();
                }
                $threadRule->allow_list_enabled = false;
                $threadRule->deny_list = $userData;
                $threadRule->save();
                ThreadRule::refreshRuleDataCache();
                Log::info("AppMention - ThreadRule " . ($threadRule->wasRecentlyCreated ? 'Created' : 'Updated'));
                $this->sendSuccessMessage("ThreadRule " .($threadRule->wasRecentlyCreated ? 'New' : 'Updated') . " - Thread Messages are now blocked for [{$userDataString}]!");
                break;
            case 'remove':
                Log::info("AppMention - ThreadRule Deleted");
                if($threadRule->delete())
                {
                    $this->sendSuccessMessage("Found and deleted ThreadRule!");
                    ThreadRule::refreshRuleDataCache();
                }
                else
                    $this->sendErrorMessage("No ThreadRule found to delete!");
                break;
            default:
                $this->sendCannotUnderstandMessage($theirMessageParts[2]);
                break;
        }
    }

    protected function getUserData(array $userArray)
    {
        $userData = ['users' => [], 'usergroups' => [], 'string' => ''];
        $stringUserStarted = false;
        $stringUsergroupStarted = false;
        foreach ($userArray as $slackUser)
        {
            $slackUser = trim ($slackUser, ', ');
            if($slackUser === '&lt;&lt;OP&gt;&gt;')
            {
                $userData['users'][] = '<<OP>>';
                $userData['string'] .= $stringUserStarted === false ? "Users: OP" : ", OP";
                $stringUserStarted = true;
                continue;
            }
            $slackId = str_replace(array('@', '>', '<'), '', $slackUser);
            if(str_starts_with($slackId, '!subteam^'))
            {
                $slackUserGroupId = explode('|', str_replace('!subteam^', '', $slackId))[0];
                $userData['usergroups'][] = $slackUserGroupId;
                $userData['string'] .= $stringUserStarted ? ' | ' : '';
                $userData['string'] .= $stringUsergroupStarted === false ? "UserGroups: {$slackUser}" : ", {$slackUser}";
                $stringUsergroupStarted = true;
                $stringUserStarted = false;
            }
            else{
                $userData['users'][] = $slackId;
                $userData['string'] .= $stringUsergroupStarted ? ' | ' : '';
                $userData['string'] .= $stringUserStarted === false ? "Users: {$slackUser}" : ", {$slackUser}";
                $stringUserStarted = true;
                $stringUsergroupStarted = false;
            }
        }
        return $userData;
    }

    protected function sendNonAdminMessage(){

        $this->slackService->sendMessageAsThreadResponse($this->channel, $this->timestamp, "Hi there, I'm afraid this is only available for workspace admins at the moment!");
    }

    protected  function sendHelpMessage(){
        $this->slackService->sendMessageAsThreadResponse($this->channel, $this->timestamp,
            "Help! {$this->getHelpText()}");
    }

    protected  function sendCannotUnderstandMessage(string $problemPart = ''){
        $this->slackService->sendMessageAsThreadResponse($this->channel, $this->timestamp,
            "Not quite sure what you mean by this? `{$problemPart}`\n Use this format: {$this->getHelpText()}");
    }
    protected  function sendSuccessMessage(string $message = ''){
        $this->slackService->sendMessageAsThreadResponse($this->channel, $this->timestamp,
            "Success! `{$message}`");
    }

    protected  function sendErrorMessage(string $message = ''){
        $this->slackService->sendMessageAsThreadResponse($this->channel, $this->timestamp,
            "Error! `{$message}`");
    }

    protected function getHelpText()
    {
        return "\n```Help  - This help" .
            "\nBotMessages [Allow|Deny]" .
            "\nChannelRule [AllowTop|DenyTop|AllowThread|DenyThread|Remove] [@user1, @user2, @user...]" .
            "\nThreadRule [Allow|Deny|Remove] [@user1, @user2, @user...]```";
    }


}
