<?php

namespace App\Services\Slack\Modals;

use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use App\Models\User;

class AppSettingsModal {

    public function openSettingsModal(User $user, object $payload): void
    {
        if(!$user->isWorkspaceAdmin())
        {
            return;
        }
        resolve(SlackService::class)->viewOpen($payload->trigger_id, $this->generateSettingsModal($user));
    }

    public function updateExistingView(User $user, string $view_id)
    {
        resolve(SlackService::class)->viewUpdate($view_id, $this->generateSettingsModal($user));
    }

    public function generateSettingsModal(User $user)
    {
        return [

            'type' => 'modal',
            'private_metadata' => SlackConstants::VIEW_ID_APP_SETTINGS,
            'title' => [
                'type' => 'plain_text',
                'text' => 'App Settings',
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Close',
            ],
            'blocks' => $this->mergeBlocks([
                $this->getSettingsHeader(),
                $this->getUserUpdateSettings(),
                $this->getUserJoinedSettings(),
                $this->getChannelLogSettings(),
                $this->getMessageDeletedSettings(),
                $this->getMessageUpdatedSettings(),
                $this->getInviteHelperSettings(),
                $this->getAutoJoinNewChannelsSettings(),
                $this->getMessageRulesSettings(),
            ])
        ];
    }

    public function getSettingsHeader()
    {
        return [];
//        return [
//            [
//                'type' => 'section',
//                'text' =>
//                    [
//                        'type' => 'mrkdwn',
//                        'text' => 'Note, this config affects your entire workspace!',
//                    ]
//            ],
//        ];
    }

    public function getAutoJoinNewChannelsSettings()
    {
        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isAutoJoinNewChannelsEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' Auto Join New Channels',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Force this bot to auto-join new channels',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_AUTO_JOIN_NEW_CHANNEL,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }

    public function getUserUpdateSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isUserUpdatedEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' User Update Log',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Log username updates',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_USER_UPDATE_LOG,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }

    public function getUserJoinedSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isUserJoinedEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' User Joined Log',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Log all new User events',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_USER_JOINED_LOG,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }
    public function getChannelLogSettings()
    {
        $enabledEmoji = SlackConstants::DISABLED_EMOJI;
        if(
            tenant()->isChannelLogCreateEnabled
            || tenant()->isChannelLogDeleteEnabled
            || tenant()->isChannelLogRenameEnabled
            || tenant()->isChannelLogArchiveEnabled
            || tenant()->isChannelLogUnarchiveEnabled
        ){
            $enabledEmoji = SlackConstants::ENABLED_EMOJI;
        }

            return [
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'header',
                    'text' =>
                        [
                            'type' => 'plain_text',
                            'text' =>  "{$enabledEmoji} Channel Log",
                        ]
                ],
                [
                    'type' => 'section',
                    'text' =>
                        [
                            'type' => 'plain_text',
                            'text' => 'Log various Channel Events',
                        ],
                    'accessory' =>
                        [
                            'type' => 'button',
                            'action_id' => SlackConstants::VIEW_TRANSITION_CHANNEL_LOG,
                            'text' =>
                                [
                                    'type' => 'plain_text',
                                    'text' => 'Config',
                                ]
                        ],
                ],
            ];
    }

    public function getMessageDeletedSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isMessageDeleteLogEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' Message Delete Log',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Log all deleted messages',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_DELETE_LOG,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }

    public function getMessageUpdatedSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isMessageUpdateLogEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' Message Update Log',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Log all edited messages',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_UPDATE_LOG,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }

    public function getInviteHelperSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isInviteHelperEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' User Invite Helper',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Manage those User Invites better',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_INVITE_HELPER,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
    }

    public function getMessageRulesSettings()
    {

        return [
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => (tenant()->isMessageRuleEnabled ? SlackConstants::ENABLED_EMOJI : SlackConstants::DISABLED_EMOJI) . ' Message Rules',
                    ]
            ],
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Strictly control Channel and Thread posting access',
                    ],
                'accessory' =>
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::VIEW_TRANSITION_MESSAGE_RULE,
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Config',
                            ]
                    ],
            ],
        ];
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

    public function getAppHomeHeader(User $user)
    {
        return [
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Hi ' . $user->name,
                    ]
            ]
        ];
    }

    public function getAppHomeActions(User $user)
    {
        if(!$user->isWorkspaceAdmin())
        {
            return [];
        }
        return [
            [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => "You're a {$user->typeRead}!",
                    ]
            ],
            [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::ACTIONS_OPEN_APP_HOME_APP_SETTINGS,
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '⚙️App Settings',
                            'emoji' => true,
                        ]
                    ],
                ]
            ],
        ];
    }



}
