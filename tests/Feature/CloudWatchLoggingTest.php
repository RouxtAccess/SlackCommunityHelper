<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Rouxtaccess\CloudWatch\Buffer\Buffer;
use Tests\TestCase;

class CloudWatchLoggingTest extends TestCase
{
    public function test_the_cloudwatch_channel_buffers_records_as_json(): void
    {
        Log::channel('cloudwatch')->info('Buffered for CloudWatch', ['source' => 'test']);

        $buffer = $this->app->make(Buffer::class);
        $batch = $buffer->peek(10);

        $this->assertSame(1, $buffer->pendingCount());
        $this->assertCount(1, $batch->records);

        $record = json_decode(current($batch->records), true);

        $this->assertSame('Buffered for CloudWatch', $record['message']);
        $this->assertSame('test', $record['context']['source']);
    }

    public function test_the_cloudwatch_channel_respects_its_minimum_level(): void
    {
        config(['logging.channels.cloudwatch.level' => 'info']);

        Log::channel('cloudwatch')->debug('Below the configured level');

        $this->assertSame(0, $this->app->make(Buffer::class)->pendingCount());
    }
}
