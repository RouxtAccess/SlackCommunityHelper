<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use BelongsToTenant;

    public const TYPE_DEACTIVATED = 'deactivated';
    public const TYPE_MEMBER = 'member';
    public const TYPE_WORKSPACE_ADMIN = 'workspace_admin';
    public const TYPE_WORKSPACE_OWNER = 'workspace_owner';
    public const TYPE_SUPER_ADMIN = 'super_admin';
    public const TYPES =
        [

            self::TYPE_DEACTIVATED => 'Deactivated',
            self::TYPE_MEMBER => 'Member',
            self::TYPE_WORKSPACE_ADMIN => 'Workspace Admin',
            self::TYPE_WORKSPACE_OWNER => 'Workspace Owner',
            self::TYPE_SUPER_ADMIN => 'Global Super Admin',
        ];

    protected $fillable = [
        'name',
        'type',
        'password',
        'workspace_id',
        'slack_id',
        'slack_nickname',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'avatar' => 'string',
        'workspace_id' => 'integer',
        'slack_id' => 'string',
        'slack_nickname' => 'string',
        'type' => 'string',
    ];


    /*****************
     *  Relations
     *****************/
    public function workspace() : BelongsTo
    {
        return $this->tenant();
    }


    /*****************
     *  Attributes
     *****************/

    public function getSlackUserReadAttribute()
    {
        return '<@' . $this->slack_id . '>';
    }
    protected function typeRead(): Attribute
    {
        return Attribute::make(
            get: fn () => self::TYPES[$this->type] ?? $this->type,
        );
    }




    /*******************
     *  Functions
     *******************/
    public function isWorkspaceAdmin(): bool
    {
        return $this->isWorkspaceOwner() || $this->type === static::TYPE_WORKSPACE_ADMIN;
    }
    public function isWorkspaceOwner(): bool
    {
        return $this->isSuperAdmin() || $this->type === static::TYPE_WORKSPACE_OWNER;
    }
    public function isSuperAdmin(): bool
    {
        return $this->type === static::TYPE_SUPER_ADMIN;
    }
}
