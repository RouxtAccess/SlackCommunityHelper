<?php

namespace App\Services\Slack;

use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use App\Models\User;

class AppHomeService {

    public function publishAppHome(User $user)
    {
        return resolve(SlackService::class)->viewPublish($this->generateAppHomePage($user));
    }

    public function generateAppHomePage(User $user)
    {
        return [
            'user_id' => $user->slack_id,
            'view' =>
                [
                    'private_metadata' => SlackConstants::VIEW_ID_APP_HOME,
                    'type' => 'home',
                    'blocks' => $this->mergeBlocks(
                        [
                            $this->getAppHomeHeader($user),
                            $this->getAppHomeActions($user),
                        ])
                ]
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
            return [
                [
                    'type' => 'header',
                    'text' =>
                        [
                            'type' => 'plain_text',
                            'text' => "You're a {$user->typeRead}!\nCurrently only workspace admins or higher can edit the configuration here!",
                        ]
                ],
            ];
        }
        $payload = [
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
                        'action_id' => SlackConstants::ACTIONS_OPEN_INITIAL_HELPER,
                        'text' => [
                            'type' => 'plain_text',
                            'text' => ':one: Initial Setup',
                            'emoji' => true,
                        ]
                    ],
                    [
                        'type' => 'button',
                        'action_id' => SlackConstants::ACTIONS_OPEN_APP_HOME_APP_SETTINGS,
                        'text' => [
                            'type' => 'plain_text',
                            'text' => ':gear:️App Settings',
                            'emoji' => true,
                        ]
                    ],
                ]
            ],
        ];
        if(tenant()->isMessageRuleEnabled)
        {
            $payload[1]['elements'][] = [
                'type' => 'button',
                'action_id' => SlackConstants::ACTIONS_OPEN_MESSAGE_RULE_VIEW,
                'text' => [
                    'type' => 'plain_text',
                    'text' => ':lock:️View Message Rules',
                    'emoji' => true,
                ]
            ];
        }
        return $payload;
    }



}
