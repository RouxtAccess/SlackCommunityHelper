<?php

namespace App\Jobs;

use App\Models\BotRule;
use App\Models\ChannelRule;
use App\Models\ThreadRule;
use App\Services\SlackService;
use App\Services\SlackService\SlackInternalConstants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessSlackEventMessage implements ShouldQueue
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
        $this->channel = Arr::get($requestData, 'event.channel');
        $this->timestamp = Arr::get($this->requestData, 'event.thread_ts') ?? Arr::get($this->requestData, 'event.ts');
        $this->slack_user_id = Arr::get($this->requestData, 'event.user')
            ?? Arr::get($this->requestData, 'event.bot_id')
            ?? Arr::get($this->requestData, 'event.previous_message.user');
        if(!$this->slack_user_id && !Arr::exists($this->requestData, 'event.previous_message.username'))
        {
            Log::error("Message Processing - Can't Find User!", ['data' => $requestData]);
        }
        $this->slackService = resolve(SlackService::class);
    }

    public function handle()
    {
        tenant()->fresh();
        Log::withContext(
            [
                'team_id' => tenant()->team_id,
                'slack_user' => $this->slack_user_id,
                'conversation' => $this->channel,
                'timestamp' => $this->timestamp,
            ]);


        if($this->sentFromUs())
            return;
        if($this->checkForInviteMessage())
            return;
        if($this->checkForMessageDelete())
            return;
        if($this->checkForMessageUpdate())
            return;

        // We don't care about these message types
        if(Arr::get($this->requestData, 'event.type') !== 'message')
            return;
        if(Arr::get($this->requestData, 'event.subtype') === SlackInternalConstants::EVENT_SUBTYPE_MESSAGE_MESSAGE_CHANGED
            || Arr::get($this->requestData, 'event.subtype') === SlackInternalConstants::EVENT_SUBTYPE_MESSAGE_MESSAGE_DELETED
            || Arr::get($this->requestData, 'event.subtype') === 'event_callback'
        )
            return;

        if($this->checkForBotMessages())
            return;
        if($this->checkForChannelRules())
            return;
        if($this->checkForThreadRules())
            return;
        return;
    }



    protected function checkForInviteMessage()
    {
        if(!tenant()->isInviteHelperEnabled)
        {
            return false;
        }
        // Invites
        if((Arr::get($this->requestData, 'event.subtype', false) === false)
            && Arr::get($this->requestData, 'event.user') === tenant()->inviteHelperUser
            && Arr::get($this->requestData, 'event.channel') === tenant()->inviteHelperChannel
        )
        {
            $this->handleInviteMessage();
            return true;
        }
        return false;
    }

    protected function checkForMessageDelete()
    {
        if(
            !tenant()->isMessageDeleteLogEnabled
            || Arr::get($this->requestData, 'event.subtype') !== SlackInternalConstants::EVENT_SUBTYPE_MESSAGE_MESSAGE_DELETED
        )
        {
            return false;
        }

        $eventTimestamp = Carbon::make(Arr::get($this->requestData, 'event.ts'));
        $previousMessageTimestamp = Carbon::make(Arr::get($this->requestData, 'event.previous_message.ts'));
        $channel = Arr::get($this->requestData, 'event.channel');
        Log::debug('MessageProcessing - Logging Message Delete', ['channel' => $channel]);

        $topMessage = $this->slackService->sendMessage(
            conversation: tenant()->messageDeleteLogChannel,
            text: "Message Deleted in <#{$channel}> :thread:",
            emoji: "candle",
        );
        $threadTimestamp = $topMessage->message->ts;
        $username = $this->slack_user_id ? "<@{$this->slack_user_id}>" :  "a bot using the username: ". Arr::get($this->requestData, 'event.previous_message.username');
        $this->slackService->sendMessage(
            conversation: tenant()->messageDeleteLogChannel,
            text: "Delete event happened at: {$eventTimestamp}".
            "\nOriginal message was sent at: {$previousMessageTimestamp}".
            "\nOriginal message sent by {$username}" .
            (Arr::get($this->requestData, 'event.previous_message.thread_ts') !== null ? "\nMessage was in a thread" : ""),
            emoji: "robot_face",
            threadTimestamp: $threadTimestamp,
            username: "Bot - MetaInformation"
        );

        if(Arr::get($this->requestData, 'event.previous_message.subtype') === SlackInternalConstants::EVENT_SUBTYPE_EVENT_BOT_MESSAGE)
        {
            $blocks = Arr::get($this->requestData, 'event.previous_message.blocks', []);
            $this->slackService->sendMessage(
                conversation: tenant()->messageDeleteLogChannel,
                text: Arr::get($this->requestData, 'event.previous_message.text'),
                blocks: $blocks,
                emoji: "robot_face",
                threadTimestamp: $threadTimestamp,
                username: 'Bot - OriginalMessage'
            );
        }
        else{
            // This message is from a user, not a bot, We can't recreate `rich_text` blocks so we just have to use the text
            $this->slackService->sendMessage(
                conversation: tenant()->messageDeleteLogChannel,
                text: Arr::get($this->requestData, 'event.previous_message.text'),
                emoji: "bust_in_silhouette",
                threadTimestamp: $threadTimestamp,
                username: 'Bot - OriginalMessage'
            );
        }
        return true;
    }

    protected function checkForMessageUpdate()
    {
        if(
            !tenant()->isMessageUpdateLogEnabled
            || Arr::get($this->requestData, 'event.subtype') !== SlackInternalConstants::EVENT_SUBTYPE_MESSAGE_MESSAGE_CHANGED
        )
        {
            return false;
        }
        if(Arr::get($this->requestData, 'event.previous_message.blocks') === Arr::get($this->requestData, 'event.message.blocks'))
        {
            return false;
        }

        $eventTimestamp = Carbon::make(Arr::get($this->requestData, 'event.ts'));
        $previousMessageTimestamp = Carbon::make(Arr::get($this->requestData, 'event.previous_message.ts'));
        $channel = Arr::get($this->requestData, 'event.channel');
        Log::debug('MessageProcessing - Logging Message Delete', ['channel' => $channel]);

        $username = $this->slack_user_id ? "<@{$this->slack_user_id}>" :  "a bot using the username: ". Arr::get($this->requestData, 'event.previous_message.username');
        $topMessage = $this->slackService->sendMessage(
            conversation: tenant()->messageUpdateLogChannel,
            text: "Message Edited by {$username} in <#{$channel}> :thread:",
            emoji: "recycle",
        );
        $threadTimestamp = $topMessage->message->ts;
        $this->slackService->sendMessage(
            conversation: tenant()->messageUpdateLogChannel,
            text: "Message Edit event happened at: {$eventTimestamp}".
            "\nOriginal message was sent at: {$previousMessageTimestamp}" .
            (Arr::get($this->requestData, 'event.previous_message.thread_ts') !== null ? "\nMessage was in a thread" : ""),
            emoji: "robot_face",
            threadTimestamp: $threadTimestamp,
            username: "Bot - MetaInformation"
        );

        if(Arr::get($this->requestData, 'event.previous_message.subtype') === SlackInternalConstants::EVENT_SUBTYPE_EVENT_BOT_MESSAGE)
        {
            $this->slackService->sendMessage(
                conversation: tenant()->messageUpdateLogChannel,
                text: Arr::get($this->requestData, 'event.previous_message.text'),
                blocks: Arr::get($this->requestData, 'event.previous_message.blocks', []),
                emoji: "one",
                threadTimestamp: $threadTimestamp,
            );
            $this->slackService->sendMessage(
                conversation: tenant()->messageUpdateLogChannel,
                text: Arr::get($this->requestData, 'event.message.text'),
                blocks: Arr::get($this->requestData, 'event.message.blocks', []),
                emoji: "two",
                threadTimestamp: $threadTimestamp,
            );
        }
        else{
            // This message is from a user, not a bot, We can't recreate `rich_text` blocks so we just have to use the text
            $this->slackService->sendMessage(
                conversation: tenant()->messageUpdateLogChannel,
                text: Arr::get($this->requestData, 'event.previous_message.text'),
                emoji: "one",
                threadTimestamp: $threadTimestamp,
                username: "Bot - Original Message"
            );
            $this->slackService->sendMessage(
                conversation: tenant()->messageUpdateLogChannel,
                text: Arr::get($this->requestData, 'event.message.text'),
                emoji: "two",
                threadTimestamp: $threadTimestamp,
                username: "Bot - New Message"
            );
        }
        return true;
    }

    protected function checkForBotMessages() : bool
    {
        // Check bot user messages
        if(Arr::get($this->requestData, 'event.subtype') === 'bot_message')
        {
            $channelBotData = BotRule::getRuleData();
            if((isset($channelBotData[$this->channel]))
                && ($channelBotData[$this->channel] === false)
            )
            {
                Log::info('MessageProcessing - Bot Message to be removed!', ['ts' => Arr::get($this->requestData, 'event.ts')]);
                SlackAutoRemoveMessage::dispatch(
                    $this->channel,
                    Arr::get($this->requestData, 'event.ts'),
                    Arr::get($this->requestData, 'event.thread_ts', null),
                    null,
                    Arr::get($this->requestData, 'event.text', null),
                    Arr::get($this->requestData, 'event.blocks', null),);
                return true;
            }
            Log::debug('ProcessSlackEventMessage - No Bot Rule Detected');
        }
        return false;
    }

    protected function sentFromUs(): bool
    {
        if(
            Arr::get($this->requestData, 'event.bot_id') === config('services.slack.bot_id')
            || Arr::get($this->requestData, 'event.bot_id') === tenant()->app_id
            || Arr::get($this->requestData, 'event.bot_id') === tenant()->user_id)
        {
            Log::debug('MessageProcessing - Ignored because we sent the message');
            return true;
        }
        return false;
    }

    protected function checkForChannelRules() : bool
    {
        if(Arr::get($this->requestData, 'event.subtype') === 'bot_message')
            return false;

        $channelRuleData = ChannelRule::getRuleData();
        if(isset($channelRuleData[$this->channel]))
        {
            $channelRule = $channelRuleData[$this->channel];
            if(!Arr::get($this->requestData, 'event.thread_ts'))
            {
                if($channelRule['allow_list_top_level_enabled'] === false
                    && Arr::get($channelRule, "deny_list_top_level.{$this->slack_user_id}"))
                {
                    Log::debug('MessageProcessing - Denied by channelRuleTopDeny');
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        null,
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
                if($channelRule['allow_list_top_level_enabled'] === true
                    && !Arr::get($channelRule, "allow_list_top_level.{$this->slack_user_id}"))
                {
                    Log::debug('MessageProcessing - Denied by channelRuleTopAllow', $channelRule);
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        null,
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
            }
            else
            {
                $checkedSlackUserID = $this->slack_user_id === Arr::get($this->requestData, 'event.parent_user_id') ? '<<OP>>' : $this->slack_user_id;
                if($channelRule['allow_list_thread_enabled'] === false
                    && (Arr::get($channelRule, "deny_list_thread.{$checkedSlackUserID}")
                        || Arr::get($channelRule, "deny_list_thread.{$this->slack_user_id}")
                    )
                )
                {
                    Log::debug('MessageProcessing - Denied by channelRuleThreadDeny', ['looked_for' => $checkedSlackUserID]);
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        Arr::get($this->requestData, 'event.thread_ts', null),
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
                if($channelRule['allow_list_thread_enabled'] === true
                    && (!Arr::get($channelRule, "allow_list_thread.{$checkedSlackUserID}")
                        && !Arr::get($channelRule, "allow_list_thread.{$this->slack_user_id}")
                    )
                )
                {
                    Log::debug('MessageProcessing - Denied by channelRuleThreadAllow', ['looked_for' => $checkedSlackUserID, 'rule' => $channelRule]);
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        Arr::get($this->requestData, 'event.thread_ts', null),
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
            }
        }
        Log::debug('ProcessSlackEventMessage - No Channel Rule Detected');
        return false;
    }

    protected function checkForThreadRules() : bool
    {
        if(Arr::get($this->requestData, 'event.subtype') === 'bot_message')
            return false;

        if(Arr::get($this->requestData, 'event.thread_ts'))
        {
            $threadRuleData = ThreadRule::getRuleData();
            if($this->slack_user_id === Arr::get($this->requestData, 'event.parent_user_id'))
            {
                $checkedSlackUserID = '<<OP>>';
            }
            else{
                $checkedSlackUserID = $this->slack_user_id;
            }
            if(isset($threadRuleData[$this->channel][Arr::get($this->requestData, 'event.thread_ts')]))
            {
                $threadRule = $threadRuleData[$this->channel][Arr::get($this->requestData, 'event.thread_ts')];

                if($threadRule['allow_list_enabled'] === false
                    && (Arr::get($threadRule, "deny_list.{$checkedSlackUserID}")
                        || Arr::get($threadRule, "deny_list.{$this->slack_user_id}")
                    )
                )
                {
                    Log::info('MessageProcessing - Denied by threadRuleDeny', ['looked_for' => $checkedSlackUserID, 'thread_rule' => $threadRule]);
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        Arr::get($this->requestData, 'event.thread_ts', null),
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
                if($threadRule['allow_list_enabled'] === true
                    && (!Arr::get($threadRule, "allow_list.{$checkedSlackUserID}")
                        || !Arr::get($threadRule, "allow_list.{$this->slack_user_id}")
                    )
                )
                {
                    Log::info('MessageProcessing - Denied by threadRuleAllow', ['looked_for' => $checkedSlackUserID, 'thread_rule' => $threadRule]);
                    SlackAutoRemoveMessage::dispatch(
                        $this->channel,
                        Arr::get($this->requestData, 'event.ts'),
                        Arr::get($this->requestData, 'event.thread_ts', null),
                        $this->slack_user_id,
                        Arr::get($this->requestData, 'event.text', null),
                        Arr::get($this->requestData, 'event.blocks', null),);
                    return true;
                }
            }
            Log::debug('ProcessSlackEventMessage - No Thread Rule Detected');
        }
        return false;
    }

    protected function handleInviteMessage() : bool
    {
        // Ensure that it is a user invite and not a shared channel request
        if(!str_contains(Arr::get($this->requestData, 'event.text'), 'requested to invite one person to this workspace'))
        {
            Log::debug('ProcessSlackEventMessage - HandleInvite Message - Not a user invite message', ['text' => Arr::get($this->requestData, 'event.text')]);
            return false;
        }

        // Get the Inviter's user string
        $matches = [];
        if(!preg_match("/<@([^>]+)>/", Arr::get($this->requestData, 'event.text'), $matches)){
            Log::error('ProcessSlackEventMessage - HandleInvite Message -Unable to match user string', ['text' => Arr::get($this->requestData, 'event.text')]);
            return false;
        }
        $inviter = $matches[1];

        // Get the email address
        if(!preg_match("/<mailto:([^|]+)/", Arr::get($this->requestData, 'event.attachments.0.text'), $matches))
        {
            Log::warning('ProcessSlackEventMessage - HandleInvite Message - Unable to match email', ['text' => Arr::get($this->requestData, 'event.attachments.0.text')]);
        }
        $inviteeEmail = $matches[1] ?? 'unknown';

        $reasonForRequest = 'No Reason Specified';
        if(Arr::get($this->requestData, 'event.attachments.1.text', false) !== false)
        {
            $reasonForRequest = str_replace('*Reason for Request*:', '', Arr::get($this->requestData, 'event.attachments.1.text'));
        }

        Log::debug('ProcessSlackEventMessage - HandleInvite Message - Found message info', ['user' => $inviter, 'email' => $inviteeEmail, 'reason' => $reasonForRequest]);


        $interpolatedTextMessage = str_replace(['###inviter_id###', '###invitee_email###', '###reason###'], [$inviter, $inviteeEmail, $reasonForRequest], tenant()->inviteHelperMessage);

        $inviterMessageBlock = [
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => "mrkdwn",
                        'text' => $interpolatedTextMessage,
                    ]
            ],
        ];
        $this->slackService->sendMessage($inviter, $interpolatedTextMessage, $inviterMessageBlock, 'robot_face');

        // Thread response the original message
        $threadMessageBlock = [
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => "mrkdwn",
                        'text' => "We sent the message to the user :)\nuser_id: {$inviter}\ninvitee_email: {$inviteeEmail}\nreason: {$reasonForRequest}",
                    ]
            ],
        ];
        $this->slackService->sendMessage(Arr::get($this->requestData, 'event.channel'), $threadMessageBlock[0]['text']['text'], $threadMessageBlock, 'robot_face', Arr::get($this->requestData, 'event.ts'));
        foreach (tenant()->inviteHelperEmojis as $emoji)
        {
            $this->slackService->addReaction(Arr::get($this->requestData, 'event.channel'), Arr::get($this->requestData, 'event.ts'), $emoji);
        }

        return true;
    }


}
