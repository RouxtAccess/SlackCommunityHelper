<?php

namespace Tests\Feature;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_the_health_check_endpoint_responds_ok(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_health_check_endpoint_dispatches_the_diagnosing_health_event(): void
    {
        Event::fake([DiagnosingHealth::class]);

        $this->get('/up')->assertOk();

        Event::assertDispatched(DiagnosingHealth::class);
    }

    public function test_the_health_check_endpoint_answers_json_requests(): void
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertJson(['status' => 'up']);
    }

    public function test_the_health_check_endpoint_stays_available_during_maintenance_mode(): void
    {
        $this->artisan('down');

        try {
            $this->get('/up')->assertOk();
        } finally {
            $this->artisan('up');
        }
    }
}
