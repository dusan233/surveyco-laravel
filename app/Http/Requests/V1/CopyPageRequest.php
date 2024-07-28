<?php

namespace App\Http\Requests\V1;

use App\Enums\PlacementPositionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyPageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "targetPageId" => "required|uuid",
            "position" => [
                "required",
                Rule::in(array_column(PlacementPositionEnum::cases(), 'value')),
            ]
        ];
    }
}
