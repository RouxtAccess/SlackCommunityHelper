<?php

namespace App\Http\Middleware;

use App\Services\SlackService\SlackInternalConstants;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifySlackRequestMiddleware
{
    // https://api.slack.com/docs/verifying-requests-from-slack#a_recipe_for_security
    public function handle(Request $request, Closure $next, string $secret = null)
    {
        $slackSignature = $request->header('X-Slack-Signature');
        if(!$slackSignature)
        {
            Log::warning('VerifySlackRequestMiddleware - Unable to find slack request header', ['request_content' => $request->getContent(), 'headers' => $request->headers]);
            return $next($request);
            abort(401, 'No X-Slack-Signature Found!');
        }

        $baseString = implode(':', [
            SlackInternalConstants::SIGNING_SIGNATURE_VERSION,
            $request->header('X-Slack-Request-Timestamp'),
            $request->getContent(),
        ]);

        $ourSignature = SlackInternalConstants::SIGNING_SIGNATURE_VERSION . '=' . hash_hmac('sha256', $baseString, config('services.slack.client_secret'));

        if($slackSignature !== $ourSignature)
        {
            Log::warning('VerifySlackRequestMiddleware - Failed to verify X-Slack-Signature.', ['request_content' => $request->getContent(), 'headers' => $request->headers]);
            return $next($request);
            abort(401, 'Failed to verify X-Slack-Signature.');
        }
        Log::info('VerifySlackRequestMiddleware - Success');
        return $next($request);
    }
}
