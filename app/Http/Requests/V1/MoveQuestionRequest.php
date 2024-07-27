<?php

namespace App\Http\Requests\V1;

use App\Enums\PlacementPositionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "targetPageId" => "required|uuid",
            "position" => [
                Rule::in(array_column(PlacementPositionEnum::cases(), 'value')),
                "required_with:targetQuestionId",
            ],
            "targetQuestionId" => [
                "uuid",
                "required_with:position"
            ]
        ];
    }
}
