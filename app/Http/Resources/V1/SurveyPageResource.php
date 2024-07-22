<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyPageResource extends JsonResource
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
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
            "displayNumber" => $this->display_number,
            "totalQuestions" => $this->whenCounted("questions", $this->questions_count),
        ];
    }
}
