<?php

namespace App\Services\Slack\Modals\AppSettings;

use App\Models\User;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Traits\JoinsChannelsFromModal;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use Illuminate\Support\Facades\Log;

class ChannelLogSettingsModal {

    use JoinsChannelsFromModal;

    public function openModalInExistingModal(User $user, object $payload)
    {
        if(!$user->isWorkspaceAdmin())
        {
            return;
        }
        resolve(SlackService::class)->viewPush($payload->trigger_id, $this->generateView($user));
    }

    public function generateView(User $user)
    {
        $payload =  [
            'type' => 'modal',
            'private_metadata' => SlackConstants::VIEW_ID_APP_SETTINGS_USER_JOINED,
            'title' => [
                'type' => 'plain_text',
                'text' => 'App Settings',
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Back',
            ],
            'blocks' =>
                [
                    [
                        'type' => 'divider',
                    ],
                    [
                        'type' => 'header',
                        'text' =>
                            [
                                'type' => 'plain_text',
                                'text' => 'Channel Log',
                            ]
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "This will various channel events to the set channel",
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel Create Event",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_CREATE_ENABLED,
                                'options' =>
                                    [
                                        [
                                            'value' => SlackConstants::BOOLEAN_TRUE,
                                            'text' =>
                                                [
                                                    'type' => 'mrkdwn',
                                                    'text' => 'Enabled',
                                                ]
                                        ],
                                    ],
                            ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel Delete Event",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_DELETE_ENABLED,
                                'options' =>
                                    [
                                        [
                                            'value' => SlackConstants::BOOLEAN_TRUE,
                                            'text' =>
                                                [
                                                    'type' => 'mrkdwn',
                                                    'text' => 'Enabled',
                                                ]
                                        ],
                                    ],
                            ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel Rename Event",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_RENAME_ENABLED,
                                'options' =>
                                    [
                                        [
                                            'value' => SlackConstants::BOOLEAN_TRUE,
                                            'text' =>
                                                [
                                                    'type' => 'mrkdwn',
                                                    'text' => 'Enabled',
                                                ]
                                        ],
                                    ],
                            ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel Archived Event",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_ARCHIVE_ENABLED,
                                'options' =>
                                    [
                                        [
                                            'value' => SlackConstants::BOOLEAN_TRUE,
                                            'text' =>
                                                [
                                                    'type' => 'mrkdwn',
                                                    'text' => 'Enabled',
                                                ]
                                        ],
                                    ],
                            ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel Unarchived Event",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_UNARCHIVE_ENABLED,
                                'options' =>
                                    [
                                        [
                                            'value' => SlackConstants::BOOLEAN_TRUE,
                                            'text' =>
                                                [
                                                    'type' => 'mrkdwn',
                                                    'text' => 'Enabled',
                                                ]
                                        ],
                                    ],
                            ],
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Channel:",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' =>
                            [
                                [
                                    'type' => 'conversations_select',
                                    'action_id' => SlackConstants::ACTIONS_INPUT_CHANNEL_LOG_CHANNEL,
                                    'placeholder' =>
                                        [
                                            'type' => 'plain_text',
                                            'text' => 'Select a Channel',
                                        ],
                                    'filter' =>[
                                        'include' => [
                                            'private',
                                            'public',
                                        ],
                                    ],
                                ],
                            ],
                    ],
                ],
        ];
        $this->setDefaults($payload);

        return $payload;

    }

    public function setDefaults(array &$payload)
    {

        if(tenant()->isChannelLogCreateEnabled)
        {
            $payload['blocks'][3]['accessory']['initial_options'] = [
                [
                    'value' => SlackConstants::BOOLEAN_TRUE,
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Enabled',
                        ]
                ],
            ];
        }
        if(tenant()->isChannelLogDeleteEnabled)
        {
            $payload['blocks'][4]['accessory']['initial_options'] = [
                [
                    'value' => SlackConstants::BOOLEAN_TRUE,
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Enabled',
                        ]
                ],
            ];
        }
        if(tenant()->isChannelLogRenameEnabled)
        {
            $payload['blocks'][5]['accessory']['initial_options'] = [
                [
                    'value' => SlackConstants::BOOLEAN_TRUE,
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Enabled',
                        ]
                ],
            ];
        }
        if(tenant()->isChannelLogArchiveEnabled)
        {
            $payload['blocks'][6]['accessory']['initial_options'] = [
                [
                    'value' => SlackConstants::BOOLEAN_TRUE,
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Enabled',
                        ]
                ],
            ];
        }
        if(tenant()->isChannelLogUnarchiveEnabled)
        {
            $payload['blocks'][7]['accessory']['initial_options'] = [
                [
                    'value' => SlackConstants::BOOLEAN_TRUE,
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Enabled',
                        ]
                ],
            ];
        }
        if(tenant()->channelLogChannel !== null)
        {
            $payload['blocks'][9]['elements'][0]['initial_conversation'] = tenant()->channelLogChannel;
        }
    }

    public function handleInputCreateEnabledToggle(User $user, object $payload)
    {
        return $this->handleInputEnabledToggle($user, $payload, 'isChannelLogCreateEnabled');
    }
    public function handleInputDeleteEnabledToggle(User $user, object $payload)
    {
        return $this->handleInputEnabledToggle($user, $payload, 'isChannelLogDeleteEnabled');
    }
    public function handleInputRenameEnabledToggle(User $user, object $payload)
    {
        return $this->handleInputEnabledToggle($user, $payload, 'isChannelLogRenameEnabled');
    }
    public function handleInputArchiveEnabledToggle(User $user, object $payload)
    {
        return $this->handleInputEnabledToggle($user, $payload, 'isChannelLogArchiveEnabled');
    }
    public function handleInputUnarchiveEnabledToggle(User $user, object $payload)
    {
        return $this->handleInputEnabledToggle($user, $payload, 'isChannelLogUnarchiveEnabled');
    }

    public function handleInputEnabledToggle(User $user, object $payload, string $attribute)
    {
        $existingRule = tenant()->$attribute;
        $newRule = false;
        if(!empty($payload->actions[0]->selected_options) && $payload->actions[0]?->selected_options[0]?->value === SlackConstants::BOOLEAN_TRUE)
        {
            $newRule = true;
        }
        if($existingRule != $newRule)
        {
            Log::info("ChannelLogModal - {$attribute} Changed", ['from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill([$attribute => $newRule])->saveOrFail();
            resolve(AppSettingsModal::class)->updateExistingView($user, $payload->view->root_view_id);
        }
        return response('success', 200);
    }


    public function handleInputChannel(User $user, object $payload)
    {
        $existingRule = tenant()->channelLogChannel;
        $newRule = null;
        if($payload->actions[0]->selected_conversation)
        {
            $newRule = $payload->actions[0]->selected_conversation;
        }
        if($existingRule != $newRule)
        {
            Log::info('ChannelLogModal - Channel Changed', ['from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['channelLogChannel' => $newRule])->saveOrFail();

            $this->joinChannelAndUpdateViewAccordingly($user, $payload, $newRule);
        }
        return response('success', 200);
    }






}
