<?php

namespace App\Models;

use App\Services\SlackService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ThreadRule extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public $fillable =
        [
            'workspace_id',
            'channel_id',
            'message',
            'message_link',
            'timestamp',
            'allow_list_enabled',
            'allow_list',
            'deny_list',
        ];

    public $casts =
        [
            'workspace_id' => 'int',
            'channel_id' => 'string',
            'message' => 'string',
            'message_link' => 'string',
            'timestamp' => 'string',
            'allow_list_enabled' => 'bool',
            'allow_list' => 'object',
            'deny_list' => 'object',
        ];


    protected $attributes = [
        'allow_list' => '{"users":[],"usergroups":[]}',
        'deny_list' => '{"users":[],"usergroups":[]}',
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

        return  Cache::tags('thread-rules')->remember('thread-rule-data', config('services.slack.cache.default_ttl'), function()
        {
            $data = [];
            foreach(ThreadRule::all() as $threadRule) {
                {
                    $ruleData = [
                        'allow_list_enabled' => $threadRule->allow_list_enabled,
                    ];
                    if ($threadRule->allow_list_enabled)
                        $ruleData['allow_list'] = static::compileListData($threadRule->allow_list);
                    else
                        $ruleData['deny_list'] = static::compileListData($threadRule->deny_list);
                    if(isset($data[$threadRule->channel_id]))
                    {
                        $data[$threadRule->channel_id][$threadRule->timestamp] = $ruleData;
                    }
                    else{
                        $data[$threadRule->channel_id] = [$threadRule->timestamp => $ruleData];
                    }
                }
            }
            return $data;
        });
    }
    public static function refreshRuleDataCache($warm = true)
    {
        Cache::tags('thread-rules')->forget('thread-rule-data');
        if(!$warm)
            return true;
        return static::getRuleData(false);
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

    public function getPermaLink()
    {
        $response = resolve(SlackService::class)->getPermaLink($this->channel_id, $this->timestamp);
        $this->message_link = $response->permalink;
    }
    public function displayListReadNames()
    {
        $string = '';
        if($this->allow_list_enabled)
        {
            foreach ((array)$this->allow_list->usergroups as $usergroupId)
            {
                $string .= ", <!subteam^{$usergroupId}|handle>";
            }
            foreach ((array)$this->allow_list->users as $userId)
            {
                $string .= ", <@{$userId}>";
            }
        }
        else{
            foreach ((array)$this->deny_list->usergroups as $usergroupId)
            {
                $string .= ", <!subteam^{$usergroupId}|handle>";
            }
            foreach ((array)$this->deny_list->users as $userId)
            {
                $string .= ", <@{$userId}>";
            }
        }
        return trim($string, ', ');
    }

    public function slackMessageDisplay(): string
    {
        return "<{$this->message_link}|This message> in <#{$this->channel_id}> is set to " . ($this->allow_list_enabled ? '`allow`' : '`deny`') . ' '. $this->displayListReadNames();
    }
}
