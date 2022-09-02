<?php

namespace App\Services\Slack\Modals\AppSettings;

use App\Models\User;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Traits\JoinsChannelsFromModal;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use Illuminate\Support\Facades\Log;

class InviteHelperSettingsModal {

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
            'private_metadata' => SlackConstants::VIEW_ID_APP_SETTINGS_INVITE_HELPER,
            'title' => [
                'type' => 'plain_text',
                'text' => 'App Settings',
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Back',
            ],
            'submit' => [
                'type' => 'plain_text',
                'text' => 'Save',
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
                                'text' => 'Invite Helper',
                            ]
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "This tool is to to help work with slack's limited functionality when it comes to allowing your communitiy members to invite others.\n As a slack admin/owner you can set your invite rules to either:\n1) Allow all invites\n2) Send invite requests to admins via SlackBot\n3) Send invite requests to a channel\n\n This is all pretty terrible if you want to ensure that anyone joining your community has gone through your flow, or agreed to your Code of Conduct.\nThis feature helps solve that, in your slack settings you choose that last one, send the invites to a private channel which only the admins (or no one) is in, and then you tell this bot to listen to that channel and send the member who invited the person a nice DM, telling them how they should get their friend invited instead.\n\nNote: All fields will save when changed except for the textarea for message, that requires a 'save', just slack things.",
                        ],
                    ],
                    [
                        'type' => 'divider',
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "Config:",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_INVITE_HELPER_ENABLED,
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
                            'text' => "This is the channel where those SlackBot messages are being sent",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' =>
                            [
                                [
                                    'type' => 'conversations_select',
                                    'action_id' => SlackConstants::ACTIONS_INPUT_INVITE_HELPER_CHANNEL,
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
                    [
                        'type' => 'input',
                        'optional' => true,
                        'label' =>  [
                            'type' => 'plain_text',
                            'text' => "Message:",
                        ],
                        'hint' =>
                            [
                                'type' => 'plain_text',
                                'text' => "You can use ###inviter_id###, ###invitee_email###, or ###reason### and they'll be interpolated ",
                            ],
                        'element' =>
                            [
                                'type' => 'plain_text_input',
                                'action_id' => SlackConstants::ACTIONS_INPUT_INVITE_HELPER_MESSAGE,
                                'placeholder' =>
                                    [
                                        'type' => 'plain_text',
                                        'text' => "The message we'll send to the person doing the inviting",
                                    ],

                                'initial_value' => tenant()->inviteHelperMessage,
                                'multiline' => true,
                            ],
                    ],
                ],
        ];
        $this->setDefaults($payload);

        return $payload;

    }

    public function setDefaults(array &$payload)
    {

        if(tenant()->isInviteHelperEnabled)
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
        if(tenant()->inviteHelperChannel !== null)
        {
            $payload['blocks'][6]['elements'][0]['initial_conversation'] = tenant()->inviteHelperChannel;
        }
    }


    public function handleInputEnabledToggle(User $user, object $payload)
    {
        $existingRule = tenant()->isInviteHelperEnabled;
        $newRule = false;
        if(!empty($payload->actions[0]->selected_options) && $payload->actions[0]?->selected_options[0]?->value === SlackConstants::BOOLEAN_TRUE)
        {
            $newRule = true;
        }
        if($existingRule != $newRule)
        {
            Log::info('InviteHelperSettingsModal - Enabled Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['isInviteHelperEnabled' => $newRule])->saveOrFail();
            resolve(AppSettingsModal::class)->updateExistingView($user, $payload->view->root_view_id);
        }
    }
    public function handleInputChannel(User $user, object $payload)
    {
        $existingRule = tenant()->inviteHelperChannel;
        $newRule = null;
        if($payload->actions[0]->selected_conversation)
        {
            $newRule = $payload->actions[0]->selected_conversation;
        }
        if($existingRule != $newRule)
        {
            Log::info('InviteHelperSettingsModal - Channel Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['inviteHelperChannel' => $newRule])->saveOrFail();

            $this->joinChannelAndUpdateViewAccordingly($user, $payload, $newRule);
        }
    }

    public function handleViewSubmit(User $user, object $payload)
    {
        $existingRule = tenant()->inviteHelperMessage;
        $newRule = null;
        $messageConst = SlackConstants::ACTIONS_INPUT_INVITE_HELPER_MESSAGE;
        foreach ($payload->view->state->values as $block)
        {
            if(is_object($block->$messageConst ?? null)){
                $newRule = $block->$messageConst->value;
            }
        }
        if($existingRule != $newRule)
        {
            Log::info('InviteHelperSettingsModal - Message Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['inviteHelperMessage' => $newRule])->saveOrFail();
        }
        return response('', 200);
    }




}
