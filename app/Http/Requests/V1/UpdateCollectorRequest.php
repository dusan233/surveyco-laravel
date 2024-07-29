<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollectorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string"
        ];
    }
}
