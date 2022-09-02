<?php

namespace App\Services\Slack\Modals\MessageRuleView;

use App\Models\ChannelRule;
use App\Models\ThreadRule;
use App\Models\User;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Modals\MessageRuleViewModal;
use App\Services\SlackService;
use App\Services\SlackService\SlackConstants;
use Illuminate\Support\Facades\Log;

class ThreadViewModal {

    public function openModalInExistingModal(User $user, object $payload)
    {
        if(!$user->isWorkspaceAdmin())
        {
            return;
        }
        resolve(SlackService::class)->viewPush($payload->trigger_id, $this->generateView($user));
    }

    public function updateExistingView(User $user, string $view_id)
    {
        resolve(SlackService::class)->viewUpdate($view_id, $this->generateView($user));
    }

    public function generateView(User $user)
    {
        $payload =  [
            'type' => 'modal',
            'private_metadata' => SlackConstants::VIEW_ID_MESSAGE_RULE_VIEW_THREAD,
            'title' => [
                'type' => 'plain_text',
                'text' => 'Message Rules',
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
                                'text' => 'Thread Rules',
                            ]
                    ],
                ]
        ];

        foreach (ThreadRule::all() as $rule)
        {
            $payload['blocks'][] =
                [
                    'type' => 'section',
                    'text' =>
                        [
                            'type' => 'mrkdwn',
                            'text' => $rule->slackMessageDisplay(),
                        ],
                    'accessory' =>
                        [
                            'type' => 'button',
                            'action_id' => SlackConstants::ACTIONS_MESSAGE_RULE_THREAD_DELETE,
                            'style' => 'danger',
                            'value' => (string) $rule->getKey(),
                            'confirm' =>
                                [
                                    'title' =>
                                        [
                                            'type' => 'plain_text',
                                            'text' => "Delete ThreadRule {$rule->getKey()}",
                                        ],
                                    'text' =>
                                        [
                                            'type' => 'plain_text',
                                            'text' => 'This will remove the ThreadRule',
                                        ],
                                    'confirm' =>
                                        [
                                            'type' => 'plain_text',
                                            'text' => 'Delete',
                                        ],
                                    'deny' =>
                                        [
                                            'type' => 'plain_text',
                                            'text' => 'Back',
                                        ],
                                    'style' => 'danger',
                                ],
                            'text' =>
                                [
                                    'type' => 'plain_text',
                                    'text' => 'Delete :put_litter_in_its_place:',
                                ],
                        ],
                ];
        }

        if(count($payload['blocks']) === 2)
        {
            $payload['blocks'][] = [
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



    public function handleDelete(User $user, object $payload)
    {
        Log::info('delete', ['payload' => $payload]);
        $threadRule = ThreadRule::find($payload->actions[0]?->value);
        if(!$threadRule)
            return response('success', 200);

        Log::info('ThreadViewModal - Deleting ThreadRule', ['thread_rule' => $threadRule->getKey(), 'user' => $payload->user->id]);
        $threadRule->delete();
        ThreadRule::refreshRuleDataCache();
        $this->updateExistingView($user, $payload->view->id);
        resolve(MessageRuleViewModal::class)->updateExistingView($user, $payload->view->root_view_id);

        return response('success', 200);
    }

}
