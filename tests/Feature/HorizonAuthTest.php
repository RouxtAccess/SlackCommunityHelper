<?php

namespace Tests\Feature;

use Tests\TestCase;

class HorizonAuthTest extends TestCase
{
    public function test_the_dashboard_is_forbidden_when_no_credentials_are_configured(): void
    {
        config(['horizon.basic_auth.username' => null, 'horizon.basic_auth.password' => null]);

        $this->get('/horizon')->assertForbidden();
    }

    public function test_the_dashboard_requests_credentials_when_none_are_sent(): void
    {
        $this->configureCredentials();

        $this->get('/horizon')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic');
    }

    public function test_the_dashboard_rejects_wrong_credentials(): void
    {
        $this->configureCredentials();

        $this->withBasicAuth('ops', 'wrong-password')
            ->get('/horizon')
            ->assertUnauthorized();
    }

    public function test_the_dashboard_rejects_a_wrong_username_with_the_right_password(): void
    {
        $this->configureCredentials();

        $this->withBasicAuth('intruder', 'secret')
            ->get('/horizon')
            ->assertUnauthorized();
    }

    public function test_the_dashboard_opens_with_correct_credentials(): void
    {
        $this->configureCredentials();

        $this->withBasicAuth('ops', 'secret')
            ->get('/horizon')
            ->assertOk();
    }

    protected function configureCredentials(): void
    {
        config(['horizon.basic_auth.username' => 'ops', 'horizon.basic_auth.password' => 'secret']);
    }
}
