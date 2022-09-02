<?php

namespace App\Services\Slack\Modals\AppSettings;

use App\Models\User;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Traits\JoinsChannelsFromModal;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use Illuminate\Support\Facades\Log;

class UserUpdateLogSettingsModal {

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
            'private_metadata' => SlackConstants::VIEW_ID_APP_SETTINGS_USER_UPDATED,
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
                                'text' => 'User Update Log',
                            ]
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "This will log to the set channel whenever a user changes their name or display name.",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_USER_UPDATES_ENABLED,
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
                            'text' => "If you select a private channel, please be sure to invite the app to the channel",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' =>
                            [

                                [
                                    'type' => 'conversations_select',
                                    'action_id' => SlackConstants::ACTIONS_INPUT_USER_UPDATES_CHANNEL,
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

        if(tenant()->isUserUpdatedEnabled)
        {
            $payload['blocks'][2]['accessory']['initial_options'] = [
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
        if(tenant()->userUpdatedChannel !== null)
        {
            $payload['blocks'][4]['elements'][0]['initial_conversation'] = tenant()->userUpdatedChannel;
        }
    }


    public function handleInputEnabledToggle(User $user, object $payload)
    {
        $existingRule = tenant()->isUserUpdatedEnabled;
        $newRule = false;
        if(!empty($payload->actions[0]->selected_options) && $payload->actions[0]?->selected_options[0]?->value === SlackConstants::BOOLEAN_TRUE)
        {
            $newRule = true;
        }
        if($existingRule != $newRule)
        {
            Log::info('UserUpdateLogModal - Enabled Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['isUserUpdatedEnabled' => $newRule])->saveOrFail();
            resolve(AppSettingsModal::class)->updateExistingView($user, $payload->view->root_view_id);
        }
        return response('success', 200);
    }
    public function handleInputChannel(User $user, object $payload)
    {
        $existingRule = tenant()->userUpdatedChannel;
        $newRule = null;
        if($payload->actions[0]->selected_conversation)
        {
            $newRule = $payload->actions[0]->selected_conversation;
        }
        if($existingRule != $newRule)
        {
            Log::info('UserUpdateLogModal - Channel Changed Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['userUpdatedChannel' => $newRule])->saveOrFail();

            $this->joinChannelAndUpdateViewAccordingly($user, $payload, $newRule);
        }
        return response('success', 200);
    }



}
