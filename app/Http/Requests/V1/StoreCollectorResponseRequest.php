<?php

namespace App\Http\Requests\V1;

use App\Enums\AnswerTypeEnum;
use App\Enums\QuestionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectorResponseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "answers" => "array",
            "answers.*.id" => "sometimes|uuid",
            "answers.*.question" => "array",
            "answers.*.question.id" => "required|uuid",
            "answers.*.question.type" => [
                "required",
                Rule::in(array_column(QuestionTypeEnum::cases(), 'value'))
            ],
            "answers.*.type" => [
                "required",
                Rule::in(array_column(AnswerTypeEnum::cases(), 'value'))
            ],
            "answers.*.choices" => "exclude_unless:answers.*.type,choices|present|array",
            "answers.*.choices.*" => "uuid",
            "answers.*.text" => "exclude_unless:answers.*.type,text|required|string",
            "pageId" => "required|string",
            "responseId" => "sometimes|uuid",
            "isPreview" => "sometimes|boolean",
            "startTime" => "required|date"
        ];
    }
}
