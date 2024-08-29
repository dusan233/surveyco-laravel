<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "fileName" => "required|string",
            "image" => "required|file|image|mimes:jpeg,png,webp|max:4096",
            "mediaType" => "required|string"
        ];
    }
}
