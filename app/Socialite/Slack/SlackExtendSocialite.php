<?php

namespace App\Socialite\Slack;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SlackExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('slack', Provider::class);
    }
}
