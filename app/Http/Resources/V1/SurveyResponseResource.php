<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $array = [
            "id" => $this->id,
            "status" => $this->status,
            "ipAddress" => $this->ip_address,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "displayNumber" => $this->display_number,
        ];

        if ($this->collector_name) {
            $array["collectorName"] = $this->collector_name;
        }

        return $array;
    }
}
