<?php

namespace App\Http\Requests\V1;

use App\Enums\SurveyCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "title" => "required",
            "category" => [
                "nullable",
                Rule::in(array_column(SurveyCategoryEnum::cases(), 'value'))
            ]
        ];
    }
}
