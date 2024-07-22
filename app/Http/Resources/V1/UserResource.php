<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "firstName" => $this->first_name,
            "lastName" => $this->last_name,
            "image" => $this->image_url,
            "profileImage" => $this->profile_image_url,
            "emailVerificationStatus" => $this->when($request->is("me"), $this->email_verification_status),
        ];
    }
}
