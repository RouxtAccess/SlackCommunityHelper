<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BotRule extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $fillable =
        [
            'workspace_id',
            'channel_id',
        ];

    public $casts =
        [
            'workspace_id' => 'int',
            'channel_id' => 'string',
        ];



    /*****************
     *  Relationships
     *****************/
    public function workspace() : BelongsTo
    {
        return $this->tenant();
    }


    /*****************
     *  Functions
     *****************/
    public static function getRuleData($forceFresh = true)
    {
        if($forceFresh)
            return static::refreshRuleDataCache();

        return  Cache::tags('bot-rules')->remember('bot-rule-data', config('services.slack.cache.default_ttl'), function()
        {
            $data = [];
            foreach(BotRule::all() as $rule) {
                $data[$rule->channel_id] = false;
            }
            return $data;
        });
    }
    public static function refreshRuleDataCache($warm = true)
    {
        Cache::tags('bot-rules')->forget('bot-rule-data');
        if(!$warm)
            return true;
        return static::getRuleData(false);
    }

    public function slackMessageDisplay(): string
    {
        return "<#{$this->channel_id}> is protected from all bot message";
    }
}
