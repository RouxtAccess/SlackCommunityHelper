<?php

namespace App\Models;

use App\Services\SlackService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ChannelRule extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $fillable =
        [
            'workspace_id',
            'channel_id',
            'description',
            'allow_list_top_level_enabled',
            'allow_list_top_level',
            'allow_list_thread_enabled',
            'deny_list_top_level',
            'allow_list_thread',
            'deny_list_thread',
        ];

    public $casts =
        [
            'workspace_id' => 'int',
            'channel_id' => 'string',
            'description' => 'string',
            'allow_list_top_level_enabled' => 'bool',
            'allow_list_top_level' => 'object',
            'deny_list_top_level' => 'object',
            'allow_list_thread_enabled' => 'bool',
            'allow_list_thread' => 'object',
            'deny_list_thread' => 'object',
        ];

    protected $attributes = [
        'allow_list_top_level' => '{"users":[],"usergroups":[]}',
        'allow_list_thread' => '{"users":[],"usergroups":[]}',
        'deny_list_top_level' => '{"users":[],"usergroups":[]}',
        'deny_list_thread' => '{"users":[],"usergroups":[]}',
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
    public static function getRuleData($forceFresh = false)
    {
        if($forceFresh)
            return static::refreshRuleDataCache();

        return  Cache::tags('channel-rules')->remember('channel-rule-data', config('services.slack.cache.default_ttl'), function()
        {
            $rules = ChannelRule::all();
            $data = [];
            foreach ($rules as $rule)
            {
                $ruleData = [
                    'allow_list_top_level_enabled' => $rule->allow_list_top_level_enabled,
                    'allow_list_thread_enabled' => $rule->allow_list_thread_enabled,
                ];
                if($rule->allow_list_top_level_enabled)
                    $ruleData['allow_list_top_level'] = static::compileListData($rule->allow_list_top_level);
                else
                    $ruleData['deny_list_top_level'] = static::compileListData($rule->deny_list_top_level);
                if($rule->allow_list_thread_enabled)
                    $ruleData['allow_list_thread'] = static::compileListData($rule->allow_list_thread);
                else
                    $ruleData['deny_list_thread'] = static::compileListData($rule->deny_list_thread);

                $data[$rule->channel_id] = $ruleData;
            }
            return $data;
        });
    }
    public static function refreshRuleDataCache($warm = true)
    {
        Cache::tags('channel-rules')->forget('channel-rule-data');
        if(!$warm)
            return true;
        return static::getRuleData();
    }

    public static function compileListData($data)
    {
        $result = [];
        $usergroups = [];
        foreach($data->users as $user)
        {
            $result[$user] = true;
        }
        foreach($data->usergroups as $usergroup_slack_id)
        {
            if(!Arr::exists($usergroups, $usergroup_slack_id))
            {
                $usergroups[$usergroup_slack_id] = (array) resolve(SlackService::class)->getUsergroupsListUsers($usergroup_slack_id)->users;
            }
            foreach($usergroups[$usergroup_slack_id] as $user_slack_id)
                $result[$user_slack_id] = true;
        }
        return $result;
    }

    public function displayListReadNames($list)
    {
        $string = '';
        foreach ((array)$list->usergroups as $usergroupId)
        {
            $string .= ", <!subteam^{$usergroupId}|handle>";
        }
        foreach ((array)$list->users as $userId)
        {
            $string .= ", <@{$userId}>";
        }
        return trim($string, ', ');
    }

    public function slackMessageDisplay(): string
    {
        $string = "<#{$this->channel_id}>:\n";

        if($this->allow_list_top_level_enabled)
        {
            $names = $this->displayListReadNames($this->allow_list_top_level);
            $string .= "\nTop level messages only allowed by: `" . ($names === '' ?  'Nobody' : $names ) . "`";
        }
        else
        {
            $names =$this->displayListReadNames($this->deny_list_top_level) ;
            $string .= "\nTop level messages denied to: `" . ($names === '' ?  'Nobody' : $names ) . "`";
        }

        if($this->allow_list_thread_enabled)
        {
            $names =$this->displayListReadNames($this->allow_list_thread);
            $string .= "\nThread messages allowed by: `" . ($names === '' ?  'Nobody' : $names ) . "`";
        }
        else
        {
            $names = $this->displayListReadNames($this->deny_list_thread);
            $string .= "\nThread messages denied to: `" . ($names === '' ?  'Nobody' : $names ) . "`";;
        }

        return $string;
    }

}
