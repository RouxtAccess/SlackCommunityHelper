<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HorizonBasicAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUsername = config('horizon.basic_auth.username');
        $expectedPassword = config('horizon.basic_auth.password');

        if (blank($expectedUsername)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (blank($expectedPassword)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! hash_equals($expectedUsername, (string) $request->getUser())) {
            return $this->requestCredentials();
        }

        if (! hash_equals($expectedPassword, (string) $request->getPassword())) {
            return $this->requestCredentials();
        }

        return $next($request);
    }

    protected function requestCredentials(): Response
    {
        return response('Invalid credentials.', Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Basic']);
    }
}
