<?php

namespace App\Http\Requests\V1;

use App\Enums\CollectorTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "type" => ["required", Rule::in(array_column(CollectorTypeEnum::cases(), 'value'))]
        ];
    }
}
