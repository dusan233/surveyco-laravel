<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class EmailAddressVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->email_verification_status !== "verified") {
            return throw new UnauthorizedException("Email address is not verified", Response::HTTP_FORBIDDEN);
        }


        return $next($request);
    }
}
