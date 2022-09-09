<?php

namespace App\Services\SlackService;


use App\Services\Slack\Modals\AppSettings\AutoJoinNewChannelSettingsModal;
use App\Services\Slack\Modals\AppSettings\ChannelLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\InviteHelperSettingsModal;
use App\Services\Slack\Modals\AppSettings\MessageDeleteLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\MessageRuleSettingsModal;
use App\Services\Slack\Modals\AppSettings\MessageUpdateLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserJoinedLogSettingsModal;
use App\Services\Slack\Modals\AppSettings\UserUpdateLogSettingsModal;
use App\Services\Slack\Modals\AppSettingsModal;
use App\Services\Slack\Modals\InitialHelperModal;
use App\Services\Slack\Modals\MessageRuleView\BotViewModal;
use App\Services\Slack\Modals\MessageRuleView\ChannelViewModal;
use App\Services\Slack\Modals\MessageRuleView\ThreadViewModal;
use App\Services\Slack\Modals\MessageRuleViewModal;

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

    // Message Deleted Log
    public const VIEW_ID_APP_SETTINGS_MESSAGE_DELETE_LOG = 'app_settings.message.deleted_log';
    public const VIEW_TRANSITION_MESSAGE_DELETE_LOG = 'transition.app_settings.message.deleted_log';
    public const ACTIONS_INPUT_MESSAGE_DELETE_LOG_ENABLED = 'input.message.deleted_log.enabled';
    public const ACTIONS_INPUT_MESSAGE_DELETE_LOG_CHANNEL = 'input.message.deleted_log.channel';

    // Channel Log
    public const VIEW_ID_APP_SETTINGS_CHANNEL_LOG = 'app_settings.message.channel_log';
    public const VIEW_TRANSITION_CHANNEL_LOG = 'transition.app_settings.channel_log';
    public const ACTIONS_INPUT_CHANNEL_LOG_CREATE_ENABLED = 'input.message.channel_log.create.enabled';
    public const ACTIONS_INPUT_CHANNEL_LOG_DELETE_ENABLED = 'input.message.channel_log.delete.enabled';
    public const ACTIONS_INPUT_CHANNEL_LOG_RENAME_ENABLED = 'input.message.channel_log.rename.enabled';
    public const ACTIONS_INPUT_CHANNEL_LOG_ARCHIVE_ENABLED = 'input.message.channel_log.archive.enabled';
    public const ACTIONS_INPUT_CHANNEL_LOG_UNARCHIVE_ENABLED = 'input.message.channel_log.unarchive.enabled';
    public const ACTIONS_INPUT_CHANNEL_LOG_CHANNEL = 'input.channel_log.channel';

    // Message Update Log
    public const VIEW_ID_APP_SETTINGS_MESSAGE_UPDATE_LOG = 'app_settings.message.update_log';
    public const VIEW_TRANSITION_MESSAGE_UPDATE_LOG = 'transition.app_settings.message.update_log';
    public const ACTIONS_INPUT_MESSAGE_UPDATE_LOG_ENABLED = 'input.message.update_log.enabled';
    public const ACTIONS_INPUT_MESSAGE_UPDATE_LOG_CHANNEL = 'input.message.update_log.channel';

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

    // Initial Helper
    public const ACTIONS_OPEN_INITIAL_HELPER = 'open.initial_helper';
    public const VIEW_ID_INITIAL_HELPER = 'initial_helper';
    public const ACTIONS_INITIAL_HELPER_CREATE_CHANNELS = 'initial_helper.create_channels';
    public const ACTIONS_INITIAL_HELPER_JOIN_CHANNELS = 'initial_helper.join_channels';
    public const ACTIONS_INITIAL_HELPER_ADD_USERS = 'initial_helper.add_users';

    // General Config
    public const ENABLED = 'Active';
    public const ENABLED_EMOJI = ':white_check_mark:';
    public const DISABLED = 'Deactivated';
    public const DISABLED_EMOJI = ':x:';

    public const BOOLEAN_TRUE = 'enabled';

    public const ACTIONS = [

        self::ACTIONS_OPEN_INITIAL_HELPER => [InitialHelperModal::class, 'openModal'],
        self::ACTIONS_INITIAL_HELPER_CREATE_CHANNELS => [InitialHelperModal::class, 'createChannels'],
        self::ACTIONS_INITIAL_HELPER_JOIN_CHANNELS => [InitialHelperModal::class, 'joinAllPublicChannels'],
        self::ACTIONS_INITIAL_HELPER_ADD_USERS => [InitialHelperModal::class, 'massUpdateAllUsers'],

        self::ACTIONS_OPEN_APP_HOME_APP_SETTINGS => [AppSettingsModal::class, 'openSettingsModal'],
        self::ACTIONS_OPEN_MESSAGE_RULE_VIEW => [MessageRuleViewModal::class, 'openSettingsModal'],

        self::VIEW_TRANSITION_AUTO_JOIN_NEW_CHANNEL => [AutoJoinNewChannelSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_AUTO_JOIN_NEW_CHANNEL_ENABLED => [AutoJoinNewChannelSettingsModal::class, 'handleInputAutoJoinNewChannelEnabledToggle'],

        self::VIEW_TRANSITION_USER_UPDATE_LOG => [UserUpdateLogSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_USER_UPDATES_ENABLED => [UserUpdateLogSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_USER_UPDATES_CHANNEL => [UserUpdateLogSettingsModal::class, 'handleInputChannel'],

        self::VIEW_TRANSITION_USER_JOINED_LOG => [UserJoinedLogSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_USER_JOINED_ENABLED => [UserJoinedLogSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_USER_JOINED_CHANNEL => [UserJoinedLogSettingsModal::class, 'handleInputChannel'],

        self::VIEW_TRANSITION_CHANNEL_LOG => [ChannelLogSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_CHANNEL_LOG_CREATE_ENABLED => [ChannelLogSettingsModal::class, 'handleInputCreateEnabledToggle'],
        self::ACTIONS_INPUT_CHANNEL_LOG_DELETE_ENABLED => [ChannelLogSettingsModal::class, 'handleInputDeleteEnabledToggle'],
        self::ACTIONS_INPUT_CHANNEL_LOG_RENAME_ENABLED => [ChannelLogSettingsModal::class, 'handleInputRenameEnabledToggle'],
        self::ACTIONS_INPUT_CHANNEL_LOG_ARCHIVE_ENABLED => [ChannelLogSettingsModal::class, 'handleInputArchiveEnabledToggle'],
        self::ACTIONS_INPUT_CHANNEL_LOG_UNARCHIVE_ENABLED => [ChannelLogSettingsModal::class, 'handleInputUnarchiveEnabledToggle'],
        self::ACTIONS_INPUT_CHANNEL_LOG_CHANNEL => [ChannelLogSettingsModal::class, 'handleInputChannel'],


        self::VIEW_TRANSITION_MESSAGE_DELETE_LOG => [MessageDeleteLogSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_MESSAGE_DELETE_LOG_ENABLED => [MessageDeleteLogSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_MESSAGE_DELETE_LOG_CHANNEL => [MessageDeleteLogSettingsModal::class, 'handleInputChannel'],

        self::VIEW_TRANSITION_MESSAGE_UPDATE_LOG => [MessageUpdateLogSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_MESSAGE_UPDATE_LOG_ENABLED => [MessageUpdateLogSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_MESSAGE_UPDATE_LOG_CHANNEL => [MessageUpdateLogSettingsModal::class, 'handleInputChannel'],

        self::VIEW_TRANSITION_INVITE_HELPER => [InviteHelperSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_INVITE_HELPER_ENABLED => [InviteHelperSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_INVITE_HELPER_CHANNEL => [InviteHelperSettingsModal::class, 'handleInputChannel'],

        self::VIEW_TRANSITION_MESSAGE_RULE => [MessageRuleSettingsModal::class, 'openModalInExistingModal'],
        self::ACTIONS_INPUT_MESSAGE_RULE_ENABLED => [MessageRuleSettingsModal::class, 'handleInputEnabledToggle'],
        self::ACTIONS_INPUT_MESSAGE_RULE_CHANNEL => [MessageRuleSettingsModal::class, 'handleInputChannel'],
        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_THREAD => [ThreadViewModal::class, 'openModalInExistingModal'],
        self::ACTIONS_MESSAGE_RULE_THREAD_DELETE => [ThreadViewModal::class, 'handleDelete'],
        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_CHANNEL => [ChannelViewModal::class, 'openModalInExistingModal'],
        self::ACTIONS_MESSAGE_RULE_CHANNEL_DELETE => [ChannelViewModal::class, 'handleDelete'],
        self::VIEW_TRANSITION_MESSAGE_RULE_VIEW_BOT => [BotViewModal::class, 'openModalInExistingModal'],
        self::ACTIONS_MESSAGE_RULE_BOT_DELETE => [BotViewModal::class, 'handleDelete'],
    ];

}
