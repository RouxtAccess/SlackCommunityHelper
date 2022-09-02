<?php

namespace App\Services\SlackService;


use App\Services\Slack\Modals\AppSettings\AutoJoinNewChannelSettingsModal;
use App\Services\Slack\Modals\AppSettings\InviteHelperSettingsModal;
use App\Services\Slack\Modals\AppSettings\MessageRuleSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserJoinedLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserUpdateLogSettingsModal;
use App\Services\Slack\Modals\MessageRuleView\BotViewModal;
use App\Services\Slack\Modals\MessageRuleView\ChannelViewModal;
use App\Services\Slack\Modals\MessageRuleView\ThreadViewModal;

class SlackConstants
{
    // App Settings
    public const VIEW_ID_APP_HOME = 'app_home';
    public const VIEW_ID_APP_SETTINGS = 'app_settings';
    public const ACTIONS_OPEN_APP_HOME_APP_SETTINGS = 'open_app_home_app_settings';

    // Auto Join
    public const VIEW_TRANSITION_AUTO_JOIN_NEW_CHANNEL = 'transition.app_settings.auto_join_new_channel';
    public const VIEW_ID_APP_SETTINGS_AUTO_JOIN = 'app_settings.auto_join_new_channel';
    public const ACTIONS_INPUT_AUTO_JOIN_NEW_CHANNEL_ENABLED = 'input.auto_join_new_channel_enabled';

    // User Update Log
    public const VIEW_TRANSITION_USER_UPDATE_LOG = 'transition.app_settings.user.update_log';
    public const VIEW_ID_APP_SETTINGS_USER_UPDATED = 'app_settings.user.updated';
    public const ACTIONS_INPUT_USER_UPDATES_ENABLED = 'input.user.updates.enabled';
    public const ACTIONS_INPUT_USER_UPDATES_CHANNEL = 'input.user.updates.channel';

    // User Joined Log
    public const VIEW_ID_APP_SETTINGS_USER_JOINED = 'app_settings.user.joined';
    public const VIEW_TRANSITION_USER_JOINED_LOG = 'transition.app_settings.user.joined_log';
    public const ACTIONS_INPUT_USER_JOINED_ENABLED = 'input.user.joined.enabled';
    public const ACTIONS_INPUT_USER_JOINED_CHANNEL = 'input.user.joined.channel';

    // Invite Helper
    public const VIEW_TRANSITION_INVITE_HELPER = 'transition.app_settings.invite_helper';
    public const VIEW_ID_APP_SETTINGS_INVITE_HELPER = 'app_settings.invite_helper';
    public const ACTIONS_INPUT_INVITE_HELPER_ENABLED = 'input.invite_helper.enabled';
    public const ACTIONS_INPUT_INVITE_HELPER_CHANNEL = 'input.invite_helper.channel';
    public const ACTIONS_INPUT_INVITE_HELPER_MESSAGE = 'input.invite_helper.message';

    // Message Rule Settings
    public const VIEW_TRANSITION_MESSAGE_RULE = 'transition.app_settings.message_rule';
    public const VIEW_ID_APP_SETTINGS_MESSAGE_RULE = 'app_settings.message_rule';
    public const ACTIONS_INPUT_MESSAGE_RULE_ENABLED = 'input.message_rule.enabled';
    public const ACTIONS_INPUT_MESSAGE_RULE_CHANNEL = 'input.message_rule.channel';
    public const ACTIONS_INPUT_MESSAGE_RULE_CUSTOM_TOKEN = 'input.message_rule.custom_token';
    // Message Rule View
    public const ACTIONS_OPEN_MESSAGE_RULE_VIEW = 'open_app_home_channel_rule_view';
    public const VIEW_ID_MESSAGE_RULE_VIEW = 'message_rule_view';
    public const VIEW_TRANSITION_MESSAGE_RULE_VIEW_THREAD = 'transition.message_rule_view.thread';
    public const VIEW_ID_MESSAGE_RULE_VIEW_THREAD = 'message_rule_view.thread';
    public const ACTIONS_MESSAGE_RULE_THREAD_DELETE = 'message_rule_view.thread.delete';
    public const ACTIONS_MESSAGE_RULE_CHANNEL_DELETE = 'message_rule_view.channel.delete';
    public const ACTIONS_MESSAGE_RULE_BOT_DELETE = 'message_rule_view.bot.delete';
    public const VIEW_TRANSITION_MESSAGE_RULE_VIEW_CHANNEL = 'transition.message_rule_view.channel';
    public const VIEW_ID_MESSAGE_RULE_VIEW_CHANNEL = 'message_rule_view.channel';
    public const VIEW_TRANSITION_MESSAGE_RULE_VIEW_BOT = 'transition.message_rule_view.bot';
    public const VIEW_ID_MESSAGE_RULE_VIEW_BOT = 'message_rule_view.bot';

    // General Config
    public const ENABLED = 'Active';
    public const ENABLED_EMOJI = ':white_check_mark:';
    public const DISABLED = 'Deactivated';
    public const DISABLED_EMOJI = ':x:';

    public const BOOLEAN_TRUE = 'enabled';


    public const VIEW_TRANSITIONS = [
        self::VIEW_TRANSITION_AUTO_JOIN_NEW_CHANNEL => AutoJoinNewChannelSettingsModal::class,
        self::VIEW_TRANSITION_USER_UPDATE_LOG => UserUpdateLogSettingsModal::class,
        self::VIEW_TRANSITION_USER_JOINED_LOG => UserJoinedLogSettingsModal::class,
        self::VIEW_TRANSITION_INVITE_HELPER => InviteHelperSettingsModal::class,
        self::VIEW_TRANSITION_MESSAGE_RULE => MessageRuleSettingsModal::class,

        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_THREAD => ThreadViewModal::class,
        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_CHANNEL => ChannelViewModal::class,
        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_BOT => BotViewModal::class,
    ];

}
