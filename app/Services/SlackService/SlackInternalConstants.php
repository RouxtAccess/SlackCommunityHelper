<?php

namespace App\Services\SlackService;


class SlackInternalConstants
{
    public const INTERACTIVITY_PAYLOAD_TYPE_BLOCK = 'block_actions';
    public const INTERACTIVITY_PAYLOAD_TYPE_VIEW_SUBMIT = 'view_submission';
    public const INTERACTIVITY_PAYLOAD_TYPE_VIEW_CLOSE = 'view_closed';

    public const SLACKBOT_USER = 'USLACKBOT';
}
