<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "type" => $this->type,
            "name" => $this->name,
            "status" => $this->status,
            "surveyId" => $this->survey_id,
            "totalResponses" => $this->whenCounted("responses", $this->responses_count)
        ];
    }
}
