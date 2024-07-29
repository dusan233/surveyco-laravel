<?php

namespace App\Http\Requests\V1;

use App\Enums\CollectorStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectorStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "status" => ["required", Rule::in(array_column(CollectorStatusEnum::cases(), 'value'))]
        ];
    }
}
