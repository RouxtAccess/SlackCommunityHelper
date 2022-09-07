<?php

namespace App\Models;

use App\Services\SlackService\SlackConstants;
use App\Services\SlackService\SlackInternalConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\Concerns\InvalidatesResolverCache;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\Database\TenantCollection;
use Stancl\Tenancy\Events\CreatingTenant;
use Stancl\Tenancy\Events\DeletingTenant;
use Stancl\Tenancy\Events\SavingTenant;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;
use Stancl\Tenancy\Events\TenantSaved;
use Stancl\Tenancy\Events\TenantUpdated;
use Stancl\Tenancy\Events\UpdatingTenant;

class Workspace extends Model implements Tenant
{
    use CentralConnection;
    use HasInternalKeys;
    use TenantRun;
    use InvalidatesResolverCache;
    use HasFactory;

    protected $fillable =
        [
            'id',
            'team_id',
            'slack_bot_access_token',
            'user_id',
            'app_id',
            'name',
            'logo',
            'balance',
            'data',
        ];

    protected $casts =
        [
            'team_id' => 'string',
            'slack_bot_access_token' => 'string',
            'user_id' => 'string',
            'app_id' => 'string',
            'name' => 'string',
            'logo' => 'string',
            'balance' => 'integer',
            'data' => 'object',
        ];

    protected $hidden =
        [
            'slack_bot_access_token'
        ];


    public function getSlackTokenAttribute()
    {
        return $this->slack_bot_access_token;
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    protected $dispatchesEvents = [
        'saving' => SavingTenant::class,
        'saved' => TenantSaved::class,
        'creating' => CreatingTenant::class,
        'created' => TenantCreated::class,
        'updating' => UpdatingTenant::class,
        'updated' => TenantUpdated::class,
        'deleting' => DeletingTenant::class,
        'deleted' => TenantDeleted::class,
    ];


    /*****************
     *  Relations
     *****************/

    public function users()
    {
        return $this->hasMany(User::class);
    }


    /*******************
     *  Functions
     *******************/
    public static function defaultConfig()
    {
        return [
            'auto_join_new_channels' => false,
            'invite_helper' =>
                [
                    'enabled' => false,
                    'channel' => null,
                    'message' => "Hi :slightly_smiling_face:\n" .
                        "Regarding your recent invite request for ###invitee_email###, we're excited that you're wanting to invite new people!\n" .
                        "Would you be able to rather send them to <https://our_website_link.com|Our Invite Link Page>?\n" .
                        "That way they can sign themselves up :slightly_smiling_face:\n" .
                        "\n" .
                        "Have a fantastic day! :rocket:",
                    'user' => 'USLACKBOT',
                ],
            'user' =>
                [
                    'new_user_joined' => [
                        'enabled' => false,
                        'channel' => null,
                    ],
                    'user_updated' => [
                        'enabled' => false,
                        'channel' => null,
                    ],
                ],
            'channel_log' =>
                [
                    'channel' => null,
                    'create_enabled' => false,
                    'delete_enabled' => false,
                    'rename_enabled' => false,
                    'archive_enabled' => false,
                    'unarchive_enabled' => false,
                ],
            'message' =>
                [
                    'delete_log' => [
                        'enabled' => false,
                        'channel' => null,
                    ],
                    'update_log' => [
                        'enabled' => false,
                        'channel' => null,
                    ],
                ],
            'rules' =>
                [
                    'enabled' => false,
                    'channel' => null,
                ],
            'responses' =>
                [
                    'enabled' => false,
                    'username' => 'SlackBoard',
                    'emoji' => '',
                ],

        ];
    }


    /*****************
     *  Auto Join New Channel
     *****************/
    protected function isAutoJoinNewChannelsEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->auto_join_new_channels';
                return (bool) ($this->$parameter ?? ($this->data->auto_join_new_channels ?? false));
            },
            set: fn (bool $value) => ['data->auto_join_new_channels' => $value,],
        );
    }


    /*****************
     *  User Update Log
     *****************/
    protected function isUserUpdatedEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->user->user_updated->enabled';
                return (bool) ($this->$parameter ?? ($this->data->user->user_updated->enabled ?? false));
            },
            set: fn (bool $value) => ['data->user->user_updated->enabled' => $value,],
        );
    }
    protected function userUpdatedChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->user->user_updated->channel';
                return ($this->$parameter ?? ($this->data->user->user_updated->channel ?? null));
            },
            set: fn (string $value) => ['data->user->user_updated->channel' => $value,],
        );
    }



    /*****************
     *  New User Log
     *****************/
    protected function isUserJoinedEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->user->new_user_joined->enabled';
                return (bool) ($this->$parameter ?? ($this->data->user->new_user_joined->enabled ?? false));
            },
            set: fn (bool $value) => ['data->user->new_user_joined->enabled' => $value,],
        );
    }
    protected function userJoinedChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->user->new_user_joined->channel';
                return ($this->$parameter ?? ($this->data->user->new_user_joined->channel ?? null));
            },
            set: fn (string $value) => ['data->user->new_user_joined->channel' => $value,],
        );
    }

    /*****************
     *  Message Delete Log
     *****************/
    protected function isMessageDeleteLogEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->message->delete_log->enabled';
                return (bool) ($this->$parameter ?? ($this->data->message->delete_log->enabled ?? false));
            },
            set: fn (bool $value) => ['data->message->delete_log->enabled' => $value,],
        );
    }
    protected function messageDeleteLogChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->message->delete_log->channel';
                return ($this->$parameter ?? ($this->data->message->delete_log->channel ?? null));
            },
            set: fn (string $value) => ['data->message->delete_log->channel' => $value,],
        );
    }

    /*****************
     *  Message Update Log
     *****************/
    protected function isMessageUpdateLogEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->message->update_log->enabled';
                return (bool) ($this->$parameter ?? ($this->data->message->update_log->enabled ?? false));
            },
            set: fn (bool $value) => ['data->message->update_log->enabled' => $value,],
        );
    }
    protected function messageUpdateLogChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->message->update_log->channel';
                return ($this->$parameter ?? ($this->data->message->update_log->channel ?? null));
            },
            set: fn (string $value) => ['data->message->update_log->channel' => $value,],
        );
    }

    /*****************
     *  Channel Log
     *****************/
    protected function isChannelLogCreateEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->create_enabled';
                return (bool) ($this->$parameter ?? ($this->data->channel_log->create_enabled ?? false));
            },
            set: fn (bool $value) => ['data->channel_log->create_enabled' => $value,],
        );
    }
    protected function isChannelLogDeleteEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->delete_enabled';
                return (bool) ($this->$parameter ?? ($this->data->channel_log->delete_enabled ?? false));
            },
            set: fn (bool $value) => ['data->channel_log->delete_enabled' => $value,],
        );
    }
    protected function isChannelLogRenameEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->rename_enabled';
                return (bool) ($this->$parameter ?? ($this->data->channel_log->rename_enabled ?? false));
            },
            set: fn (bool $value) => ['data->channel_log->rename_enabled' => $value,],
        );
    }
    protected function isChannelLogArchiveEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->archive_enabled';
                return (bool) ($this->$parameter ?? ($this->data->channel_log->archive_enabled ?? false));
            },
            set: fn (bool $value) => ['data->channel_log->archive_enabled' => $value,],
        );
    }
    protected function isChannelLogUnarchiveEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->unarchive_enabled';
                return (bool) ($this->$parameter ?? ($this->data->channel_log->unarchive_enabled ?? false));
            },
            set: fn (bool $value) => ['data->channel_log->unarchive_enabled' => $value,],
        );
    }
    protected function channelLogChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->channel_log->channel';
                return ($this->$parameter ?? ($this->data->channel_log->channel ?? null));
            },
            set: fn (string $value) => ['data->channel_log->channel' => $value,],
        );
    }

    /*****************
     *  Invite Helper
     *****************/
    protected function isInviteHelperEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->invite_helper->enabled';
                return (bool) ($this->$parameter ?? ($this->data->invite_helper->enabled ?? false));
            },
            set: fn (bool $value) => ['data->invite_helper->enabled' => $value,],
        );
    }
    protected function inviteHelperChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->invite_helper->channel';
                return ($this->$parameter ?? ($this->data->invite_helper->channel ?? null));
            },
            set: fn (string $value) => ['data->invite_helper->channel' => $value,],
        );
    }

    protected function inviteHelperMessage(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->invite_helper->message';
                return ($this->$parameter ?? ($this->data->invite_helper->message ?? null));
            },
            set: fn (string $value) => ['data->invite_helper->message' => $value,],
        );
    }
    protected function inviteHelperUser(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->invite_helper->user';
                return ($this->$parameter ?? ($this->data->invite_helper->user ?? SlackInternalConstants::SLACKBOT_USER));
            },
            set: fn (string $value) => ['data->invite_helper->user' => $value,],
        );
    }

    protected function inviteHelperEmojis(): Attribute
    {
        return Attribute::make(
            get: function (){
                return (array) ($this->data->invite_helper->emoji ?? ['robot_face', 'heavy_check_mark']);
            },
        );
    }




    /*****************
     *  Message Rules
     *****************/
    protected function isMessageRuleEnabled(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->rules->enabled';
                return (bool) ($this->$parameter ?? ($this->data->rules->enabled ?? false));
            },
            set: fn (bool $value) => ['data->rules->enabled' => $value,],
        );
    }
    protected function messageRuleChannel(): Attribute
    {
        return Attribute::make(
            get: function (){
                $parameter = 'data->rules->channel';
                return ($this->$parameter ?? ($this->data->rules->channel ?? null));
            },
            set: fn (string $value) => ['data->rules->channel' => $value,],
        );
    }
    protected function messageRuleCustomToken(): Attribute
    {
        return Attribute::make(
            get: function (){
                return (($this->data->rules->custom_token ?? null) !== null ? Crypt::decryptString($this->data->rules->custom_token) : null);
            },
            set: function (?string $value){
                if(!$value){
                    return ['data->rules->custom_token' => null,];
                }
                return ['data->rules->custom_token' => Crypt::encryptString($value),];
            },
        );
    }



}
