<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Svix\Webhook;
use Svix\Exception\WebhookVerificationException;

class ClerkWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $headers = collect($request->headers->all())->transform(function ($item) {
            return $item[0];
        });

        $secret = env("CLERK_WEBHOOK_SECRET");

        try {
            $wh = new Webhook($secret);
            // verify the payload authenticity
            $payload = $wh->verify($payload, $headers);

            // Send payload to job for processing

            if ($payload["type"] === "user.created") {
                $createdAt = new DateTime();
                $realCreatedAt = $createdAt->setTimestamp(($payload["data"]["created_at"] / 1000));

                $updatedAt = new DateTime();
                $realUpdatedAt = $updatedAt->setTimestamp(($payload["data"]["updated_at"] / 1000));
                User::create([
                    "id" => $payload["data"]["id"],
                    "email" => $payload["data"]["email_addresses"][0]["email_address"],
                    "first_name" => $payload["data"]["first_name"],
                    "last_name" => $payload["data"]["last_name"],
                    "image_url" => $payload["data"]["image_url"],
                    "profile_image_url" => $payload["data"]["profile_image_url"],
                    "email_verification_status" => $payload["data"]["email_addresses"][0]["verification"]["status"],
                    "created_at" => $realCreatedAt,
                    "updated_at" => $realUpdatedAt,
                ]);
            }

            return response()->json(["success" => true]);
        } catch (WebhookVerificationException $e) {
            return response(null, 400);
        }
    }
}
