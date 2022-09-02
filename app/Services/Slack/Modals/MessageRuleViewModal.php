<?php

namespace App\Services\Slack\Modals;

use App\Models\BotRule;
use App\Models\ChannelRule;
use App\Models\ThreadRule;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use App\Models\User;

class MessageRuleViewModal {

    public function openSettingsModal(User $user, string $trigger_id): void
    {
        if(!$user->isWorkspaceAdmin())
        {
            return;
        }
        resolve(SlackService::class)->viewOpen($trigger_id, $this->generateSettingsModal($user));
    }

    public function updateExistingView(User $user, string $view_id)
    {
        resolve(SlackService::class)->viewUpdate($view_id, $this->generateSettingsModal($user));
    }

    public function generateSettingsModal(User $user)
    {
        return [

            'type' => 'modal',
            'private_metadata' => SlackConstants::VIEW_ID_MESSAGE_RULE_VIEW,
            'title' => [
                'type' => 'plain_text',
                'text' => 'Message Rules',
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Close',
            ],
            'blocks' => $this->mergeBlocks([
                $this->getSettingsHeader(),
                $this->getMessageRuleThreadBlocks(),
                $this->getMessageRuleChannelBlocks(),
                $this->getMessageRuleBotBlocks(),
            ])
        ];
    }

    public function getSettingsHeader()
    {
        return [];
    }

    public function getMessageRuleThreadBlocks()
    {
        $payload = [
            [
                'type' => 'divider',
            ]
        ];

        $threadRuleCount = ThreadRule::count();

        if($threadRuleCount > 0)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ":thread: You have {$threadRuleCount} Thread Rules!",
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_RULE_VIEW_THREAD,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'View :thread:',
                                'emoji' => true
                            ]
                    ],
            ];
        }

        if(count($payload) === 1)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ':thread: No Thread Rules are currently active!',
                    ]
            ];
        }
        return $payload;
    }


    public function getMessageRuleChannelBlocks()
    {
        $payload = [
            [
                'type' => 'divider',
            ]
        ];

        $channelRuleCount = ChannelRule::count();

        if($channelRuleCount > 0)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ":lock: You have {$channelRuleCount} Channel Rules!",
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_RULE_VIEW_CHANNEL,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'View :lock:',
                            ]
                    ],
            ];
        }

        if(count($payload) === 1)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ':lock: No Channel Rules are currently active!',
                    ]
            ];
        }
        return $payload;
    }

    public function getMessageRuleBotBlocks()
    {
        $payload = [
            [
                'type' => 'divider',
            ]
        ];

        $ruleCount = BotRule::count();

        if($ruleCount > 0)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ":robot_face: You have {$ruleCount} Channel Bot Rules!",
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_RULE_VIEW_BOT,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'View :robot_face:',
                                'emoji' => true,
                            ]
                    ],
            ];
        }

        if(count($payload) === 1)
        {
            $payload[] = [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => ':robot_face: No Bot Rules are currently active!',
                    ]
            ];
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



}
