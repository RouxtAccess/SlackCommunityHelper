<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\GetSlackInfoForUser;
use App\Services\SlackService;
use App\Services\SlackService\Domain;
use App\Models\User;
use App\Models\Workspace;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Stancl\Tenancy\Facades\Tenancy;
use Stancl\Tenancy\Features\UserImpersonation;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class LoginController extends Controller
{
    public function addNewSlackWorkspace(Request $request)
    {
        $provider = 'slack';
        Log::info('addNewSlackWorkspace - New Callback', ['request' => $request->all()]);

        $allInfo = Socialite::driver($provider)->allInfo();
        Log::info('addNewSlackWorkspace - Got All Info', ['data' => $allInfo]);

        $workspaceData = Socialite::driver($provider)->getTeamByToken(Arr::get($allInfo, 'access_token'));
        Log::info('addNewSlackWorkspace - Got Workspace', ['data' => $workspaceData]);

        $workspaceConfig = Workspace::defaultConfig();
        Arr::set($workspaceConfig, 'free_tier.main.channel', Arr::get($allInfo, 'incoming_webhook.channel_id'));

        Log::info('addNewSlackWorkspace - Workspace Find/Creating...');
        $workspace = Workspace::firstOrCreate(['team_id' => Arr::get($workspaceData, 'team.id')],
            [
                'team_id' => Arr::get($workspaceData, 'team.id'),
                'user_id' => Arr::get($allInfo, 'bot_user_id'),
                'app_id' => Arr::get($allInfo, 'app_id'),
                'name' => Arr::get($workspaceData, 'team.name'),
                'logo' => Arr::get($workspaceData, 'team.icon.image_132'),
                'slack_bot_access_token' => Arr::get($allInfo, 'access_token'),
                'data' => $workspaceConfig,
            ]);
        if($workspace->wasRecentlyCreated){
            Log::error("New Workspace Created, Get on it!!!", ['name' => $workspace->name]);
        }
        Log::info('addNewSlackWorkspace - Workspace Found/Created!', ['workspace' => $workspace]);

        Tenancy::initialize($workspace);


        Log::info('addNewSlackWorkspace - Owner User Find/Creating...');
        GetSlackInfoForUser::dispatch(Arr::get($allInfo, 'authed_user.id'));
        Log::info('addNewSlackWorkspace - Owner User Found/Created!', ['user_slack_id' => Arr::get($allInfo, 'authed_user.id')]);


        return redirect(route('workspace.add.success'));
    }
}
