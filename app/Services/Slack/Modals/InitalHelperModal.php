<?php

namespace App\Services\Slack\Modals;

use App\Jobs\SlackJoinAllPublicChannels;
use App\Models\BotRule;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InitalHelperModal {

    public ?SlackService $slackService;
    private ?array $result = null;


    public function openModal(User $user, object $payload): void
    {
        if(!$user->isWorkspaceAdmin())
        {
            return;
        }
        resolve(SlackService::class)->viewOpen($payload->trigger_id, $this->generateModal($user));
    }

    public function updateExistingView(User $user, string $view_id)
    {
        resolve(SlackService::class)->viewUpdate($view_id, $this->generateModal($user));
    }

    public function generateModal(User $user)
    {
        return [

            'type' => 'modal',
            'private_metadata' => SlackConstants::VIEW_ID_INITIAL_HELPER,
            'title' => [
                'type' => 'plain_text',
                'text' => 'Initial Setup',
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Close',
            ],
            'blocks' => $this->mergeBlocks([
                $this->getHeader(),
                $this->getAutoGenerateChannels(),
            ])
        ];
    }

    public function getHeader()
    {
        return [
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => 'These functions should help get you up and running in no time!',
                    ]
            ],
        ];
    }

    public function getAutoGenerateChannels()
    {
        $payload = [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => "Create default channels",
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => "This will create standard named channels for this app's features and invite you to them",
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::ACTIONS_INITIAL_HELPER_CREATE_CHANNELS,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Create',
                            ]
                    ],
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => "Join All Public Channels",
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => "This will make the app join all public channels, it's required if you want message updates from that channel (delete/edit) or if you want to utilize tools like the Message Rules.",
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::ACTIONS_INITIAL_HELPER_JOIN_CHANNELS,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Join All',
                            ]
                    ],
            ],
        ];
        if(is_array($this->result))
        {
            unset($payload[2]['accessory']);
            foreach ($this->result as $channel => $result)
            {
                $payload[] = [
                    'type' => 'section',
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => ($result['success'] ? ':white_check_mark:' : ':x:') . " - #{$channel} - " . $result['message'],
                            'verbatim' => true,
                        ],
                ];
            }
        }
        return $payload;
    }


    public function mergeBlocks($sections)
    {
        $array = [];
        foreach ($sections as $blocks)
        {
            foreach ($blocks as $block)
            {
                $array[] = $block;
            }
        }
        return $array;
    }

    public function createChannels(User $user, object $payload)
    {
        $this->slackService = resolve(SlackService::class);
        Log::info('InitialHelperModal - Create/Joining default channels');
        $this->joinChannel($payload->user->id,
            ['channel' => 'zmeta-helper-invite-automated', 'attribute' => 'inviteHelperChannel'],
            ['channel' => 'zmeta-helper-delete-log', 'attribute' => 'messageDeleteLogChannel'],
            ['channel' => 'zmeta-helper-update-log', 'attribute' => 'messageUpdateLogChannel'],
            ['channel' => 'zmeta-helper-channel-log', 'attribute' => 'channelLogChannel', 'private' => false],
            ['channel' => 'zmeta-helper-rule-delete', 'attribute' => 'messageRuleChannel'],
            ['channel' => 'zmeta-helper-user-new', 'attribute' => 'userJoinedChannel'],
            ['channel' => 'zmeta-helper-user-updates', 'attribute' => 'userUpdatedChannel'],
        );

        $this->updateExistingView($user, $payload->view->root_view_id);

        return response('success', 200);
    }

    protected function joinChannel(string $user, array ...$channelData)
    {
        $slackChannelData = $this->slackService->getConversationAll(false);
        $result = [];
        foreach ($channelData as $channelToBeJoined)
        {
            $this->createOrJoin(
                channel: $channelToBeJoined['channel'],
                workspaceAttribute: $channelToBeJoined['attribute'],
                user: $user,
                result: $result,
                convoInfo: $slackChannelData->first(fn($value) => $value->name === $channelToBeJoined['channel']),
                private: ($channelToBeJoined['private'] ?? true),
            );
        }
        $this->result = $result;
    }

    protected function createOrJoin(string $channel, string $workspaceAttribute, string $user, array &$result, ?object $convoInfo, bool $private = true,) : void
    {
        if(is_object($convoInfo)) {
            if ($convoInfo?->is_archived === true) {
                $result[$channel] = ['success' => false, 'message' => "Channel is archived, please unarchive manually"];
                return;
            }

            // Check if we're in the channel
            if ($convoInfo?->is_member === true) {
                if (tenant()->$workspaceAttribute === null) {
                    tenant()->$workspaceAttribute = $convoInfo->id;
                    tenant()->save();
                }
                $result[$channel] = ['success' => true, 'message' => 'Already created and joined'];
                $this->slackService->conversationInvite($convoInfo->id, $user);
                return;
            }

            $canJoinConversationResponse = $this->slackService->conversationJoin($convoInfo->id);
            if ($canJoinConversationResponse->ok === true) {
                if (tenant()->$workspaceAttribute === null) {
                    tenant()->$workspaceAttribute = $convoInfo->id;
                    tenant()->save();
                }
                $result[$channel] = ['success' => true, 'message' => 'Channel existed, we joined'];
                $this->slackService->conversationInvite($convoInfo->id, $user);
                return;
            }
            $result[$channel] = ['success' => false, 'message' => "Channel exists, we couldn't join [{$canJoinConversationResponse->error}]"];
            return;
        }

        // Create Channel
        $channelCreate = $this->slackService->conversationCreate($channel, $private);
        if($channelCreate->ok !== true)
        {
            $result[$channel] = ['success' => false, 'message' => "Couldn't create channel [{$channelCreate->error}]"];
            return;
        }

        if (tenant()->$workspaceAttribute === null) {
            tenant()->$workspaceAttribute = $channelCreate->channel->id;
            tenant()->save();
        }
        $result[$channel] = ['success' => true, 'message' => 'New Channel Created & Joined'];
        $this->slackService->conversationInvite($channelCreate->channel->id, $user);
    }

    public function joinAllPublicChannels(User $user, object $payload)
    {
        SlackJoinAllPublicChannels::dispatch();
    }

}
