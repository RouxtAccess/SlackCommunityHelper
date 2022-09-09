<?php

namespace App\Services\SlackService;


class SlackInternalConstants
{
    public const INTERACTIVITY_PAYLOAD_TYPE_BLOCK = 'block_actions';
    public const INTERACTIVITY_PAYLOAD_TYPE_VIEW_SUBMIT = 'view_submission';
    public const INTERACTIVITY_PAYLOAD_TYPE_VIEW_CLOSE = 'view_closed';

    public const SLACKBOT_USER = 'USLACKBOT';

    public const EVENT_SUBTYPE_MESSAGE_MESSAGE_DELETED = 'message_deleted';
    public const EVENT_SUBTYPE_MESSAGE_MESSAGE_CHANGED = 'message_changed';
    public const EVENT_SUBTYPE_EVENT_EVENT_CALLBACK = 'event_callback';
    public const EVENT_SUBTYPE_EVENT_BOT_MESSAGE = 'bot_message';

    public const API_ERROR_LIMIT_REQUIRED = 'limit_required';
}
