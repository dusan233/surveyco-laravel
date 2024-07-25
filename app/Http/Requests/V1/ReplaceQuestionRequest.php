<?php

namespace App\Http\Requests\V1;

use App\Enums\QuestionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReplaceQuestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "description" => "required|string",
            "descriptionImage" => "nullable|url",
            "required" => "required|boolean",
            "type" => ["required", Rule::in(array_column(QuestionTypeEnum::cases(), 'value'))],
            "randomize" => [
                Rule::excludeIf(fn() => $this->input("type") === QuestionTypeEnum::TEXTBOX->value),
                "required",
                "boolean",
            ],
            "choices" => [
                Rule::excludeIf(fn() => $this->input("type") === QuestionTypeEnum::TEXTBOX->value),
                "array",
                "min:1",
            ],
            "choices.*.id" => "sometimes|uuid",
            "choices.*.description" => "required|string",
            "choices.*.descriptionImage" => "nullable|url",
            "choices.*.displayNumber" => "required|integer|min:1"
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $choices = $this->input('choices', []);
            if (!empty($choices)) {
                $displayNumbers = array_column($choices, 'displayNumber');

                sort($displayNumbers);
                $expectedNumbers = range(1, count($choices));

                if ($displayNumbers !== $expectedNumbers) {
                    $validator->errors()->add('choices', 'The displayNumber fields must be a sequential set starting from 1.');
                }
            }
        });
    }


    // public function after(): array
    // {
    //     return [
    //         function (Validator $validator) {
    //             $choices = $validator->getData()["choices"];
    //             $choicesWithId = array_filter($choices, function ($choice) {
    //                 return isset($choice["id"]);
    //             });
    //             $choiceIds = array_map(function ($choice) {
    //                 return $choice["id"];
    //             }, $choicesWithId);

    //             $retrievdChoices = QuestionChoice::where("question");

    //             if (true) {
    //                 $validator->errors()->add(
    //                     'choices',
    //                     'Something is wrong with this field!'
    //                 );
    //             }
    //         }
    //     ];
    // }
}

