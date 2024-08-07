<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionChoiceRollupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $array = [
            "id" => $this->id,
            "answered" => $this->question_response_answers_count,
        ];

        return $array;
    }
}
