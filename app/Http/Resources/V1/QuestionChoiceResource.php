<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionChoiceResource extends JsonResource
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
            "description" => $this->description,
            "descriptionImage" => $this->description_image,
            "displayNumber" => $this->display_number,
        ];
    }
}
