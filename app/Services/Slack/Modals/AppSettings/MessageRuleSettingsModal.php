<?php

namespace App\Services\Slack\Modals\AppSettings;

use App\Models\User;
use App\Services\AppHomeService;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MessageRuleSettingsModal {

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
            'private_metadata' => SlackConstants::VIEW_ID_APP_SETTINGS_MESSAGE_RULE,
            'title' => [
                'type' => 'plain_text',
                'text' => 'App Settings',
            ],
            'submit' => [
                'type' => 'plain_text',
                'text' => 'Save Token',
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
                                'text' => 'Message Rules',
                            ]
                    ],
                    [
                        'type' => 'section',
                        'text' =>  [
                            'type' => 'plain_text',
                            'text' => "This is a tool to help lock down channels and threads with various options. It uses App Mentions (@SlackCommunityHelper) to function.",
                        ],
                        'accessory' =>
                            [
                                'type' => 'checkboxes',
                                'action_id' => SlackConstants::ACTIONS_INPUT_MESSAGE_RULE_ENABLED,
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
                            'text' => "Any messages which this feature ends up deleting, will also be logged in a channel of your choosing\nIf you select a private channel, please be sure to invite the app to the channel",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' =>
                            [
                                [
                                    'type' => 'conversations_select',
                                    'action_id' => SlackConstants::ACTIONS_INPUT_MESSAGE_RULE_CHANNEL,
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
                            'text' => "User Token which can delete messages (chat:write) (We encrypt this) :lock:",
                            'emoji' => true,
                        ],
                        'element' =>
                            [
                                'type' => 'plain_text_input',
                                'action_id' => SlackConstants::ACTIONS_INPUT_MESSAGE_RULE_CUSTOM_TOKEN,
                                'placeholder' =>  [
                                    'type' => 'plain_text',
                                    'text' => "xoxp-24695941...",
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

        if(tenant()->isMessageRuleEnabled)
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
        if(tenant()->messageRuleChannel !== null)
        {
            $payload['blocks'][4]['elements'][0]['initial_conversation'] = tenant()->messageRuleChannel;
        }
        if(tenant()->messageRuleCustomToken !== null)
        {
            $payload['blocks'][5]['element']['initial_value'] = Crypt::encryptString(tenant()->messageRuleCustomToken);
            $payload['blocks'][5]['hint'] =
                [
                    'type' => 'plain_text',
                    'text' => 'Your Token: ' . substr(tenant()->messageRuleCustomToken, 0, strlen(tenant()->messageRuleCustomToken)/3) . '...',
                ];
        }
    }


    public function handleInputEnabledToggle(User $user, object $payload)
    {
        $existingRule = tenant()->isMessageRuleEnabled;
        $newRule = false;
        if(!empty($payload->actions[0]->selected_options) && $payload->actions[0]?->selected_options[0]?->value === SlackConstants::BOOLEAN_TRUE)
        {
            $newRule = true;
        }
        if($existingRule != $newRule)
        {
            Log::info('ChannelRuleSettingsModal - Enabled Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['isMessageRuleEnabled' => $newRule])->saveOrFail();
            resolve(AppSettingsModal::class)->updateExistingView($user, $payload->view->root_view_id);
            resolve(AppHomeService::class)->publishAppHome($user);
        }
        return response('success', 200);
    }
    public function handleInputChannel(User $user, object $payload)
    {
        $existingRule = tenant()->messageRuleChannel;
        $newRule = null;
        if($payload->actions[0]->selected_conversation)
        {
            $newRule = $payload->actions[0]->selected_conversation;
        }
        if($existingRule != $newRule)
        {
            Log::info('ChannelRuleSettingsModal - Channel Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['messageRuleChannel' => $newRule])->saveOrFail();

            $convoInfo = resolve(SlackService::class)->getConversationInfo($newRule);
            if($convoInfo->ok !== true)
            {
                Log::debug('ChannelRuleSettingsModal - Unable to join Channel (Private)');
                $this->updateViewWithChannelWarning($user, $payload->view->id);
            }
            if($convoInfo->channel->is_member !== true)
            {
                $canJoinConversationResponse = resolve(SlackService::class)->conversationJoin($newRule);
                if($canJoinConversationResponse->ok !== true)
                {
                    Log::debug('ChannelRuleSettingsModal - Unable to join public Channel', ['response' => $canJoinConversationResponse]);
                    $this->updateViewWithChannelWarning($user, $payload->view->id);
                }
            }
        }
        return response('success', 200);
    }

    public function handleViewSubmit(User $user, object $payload)
    {
        $existingRule = tenant()->messageRuleCustomToken;
        $newRule = null;
        $newRuleDecrypted = 'invalid';

        $messageConst = SlackConstants::ACTIONS_INPUT_MESSAGE_RULE_CUSTOM_TOKEN;
        foreach ($payload->view->state->values as $block)
        {
            if(is_object($block->$messageConst ?? null)){
                $newRule = $block->$messageConst->value;
                try{
                    $newRuleDecrypted = Crypt::decryptString($newRule);
                }
                catch(DecryptException $throwable){}
                break;
            }
        }
        if($existingRule !== $newRule && $existingRule !== $newRuleDecrypted)
        {
            Log::info('ChannelRuleSettingsModal - Custom Token Changed', ['team_id' => tenant()->team_id, 'from' => $existingRule, 'to' => $newRule]);
            tenant()->forceFill(['messageRuleCustomToken' => $newRule])->saveOrFail();
        }

        return response('', 200);
    }

    public function updateViewWithChannelWarning(User $user, string $view_id)
    {
        $newView = $this->generateView($user);
        $newView['blocks'][] = [
            'type' => 'section',
            'text' =>  [
                'type' => 'mrkdwn',
                'text' => ":triangular_flag_on_post: _Please make sure to invite our app!_",
            ],
        ];
        resolve(SlackService::class)->viewUpdate($view_id, $newView);
    }

    public function updateViewWithoutChannelWarning(User $user, string $view_id)
    {
        $newView = $this->generateView($user);
        resolve(SlackService::class)->viewUpdate($view_id, $newView);
    }

}
