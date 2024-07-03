<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ClerkAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuiSUpUmUSWZB1+xsjdqK\n" .
            "s/LGElobFflxgJk1FVoYwR/hnARsAq14+l5+kureGZBT3LxZ358JDWjM0pgyuCCx\n" .
            "BeA6tjEATykOiCRMzkqGf6kSDytbXWOjjTAh0pWoMWY9MAWsCGRF2UOEpmfjT5Wk\n" .
            "bzwvYs19C+dXA50x7Qalejsw5yaU8YFXUhNy3xC25k1NaHrWapN5w/BFixpQwiYd\n" .
            "uc5Fjm/HdlTNntYrytjk6PNTklMD+ie1x9HSYIa0tLP65XMQRWThg1QtxNYE8ZXO\n" .
            "KwYPimo3qE7CMorz/grcDhIldOFxLQMWULtTz55sfcH6LwP6Aa7Qq9F2cTUsh2qQ\n" .
            "7wIDAQAB\n" .
            "-----END PUBLIC KEY-----";
        $token = $request->bearerToken();

        if (!$token) {
            //check if token is not null
            return response()->json(["error" => "Token is not present"], Response::HTTP_UNAUTHORIZED);
        }


        try {
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            //just check that azp is correct


            $request->attributes->set('userId', $decoded->sub);

            // return response()->json(["data" => $decoded->sub]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

    }
}
