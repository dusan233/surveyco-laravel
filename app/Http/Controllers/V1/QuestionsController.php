<?php

namespace App\Http\Controllers\V1;

use App\Enums\QuestionTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReplaceQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\Question;
use App\Models\QuestionChoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class QuestionsController extends Controller
{
    public function update(ReplaceQuestionRequest $request, string $question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("update", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $questionData = $request->validated();

        // trying to change question type which is not supported right now
        if ($question->type !== $questionData["type"]) {
            throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            Question::where("id", $question_id)
                ->update([
                    "description" => $questionData["description"],
                    "description_image" => $questionData["descriptionImage"],
                    "type" => $questionData["type"],
                    "required" => $questionData["required"],
                    "randomize" => $questionData["type"] !== QuestionTypeEnum::TEXTBOX->value
                        ? $questionData["randomize"]
                        : null,
                ]);

            if (isset($questionData["choices"])) {
                $choices = $questionData["choices"];
                //if question has responses u have to provide all choices that are already saved.
                $choicesWithId = array_filter($choices, function ($choice) {
                    return isset($choice["id"]);
                });
                $choicesIds = array_map(function ($choice) {
                    return $choice["id"];
                }, $choicesWithId);

                $providedChoices = QuestionChoice::whereIn("id", $choicesIds)
                    ->where("question_id", $question_id)
                    ->lockForUpdate()
                    ->get();

                if (count($choicesIds) !== count($providedChoices)) {
                    throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
                }
                // remove choices that are not included - maybe we can still not run this if question has responses.
                QuestionChoice::where("question_id", $question_id)
                    ->whereNotIn("id", $choicesIds)
                    ->forceDelete();

                //update or create choicess
                QuestionChoice::upsert(
                    array_map(function ($choice) use ($question_id) {
                        $choiceData = [
                            "description" => $choice["description"],
                            "description_image" => $choice["descriptionImage"],
                            "display_number" => $choice["displayNumber"],
                            "question_id" => $question_id
                        ];
                        if (isset($choice["id"])) {
                            $choiceData["id"] = $choice["id"];
                        }

                        return $choiceData;
                    }, $choices),
                    ["id"],
                    ['description', 'description_image', 'display_number', "question_id"]
                );
            }

            $updatedQuestion = Question::find($question_id);
            if (isset($questionData["choices"])) {
                $updatedQuestion->load("choices");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new QuestionResource($updatedQuestion);
    }


    public function destroy(Request $request, string $question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("delete", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            DB::beginTransaction();

            $question = Question::find($question_id);
            $surveyId = $question->surveyPage->survey_id;

            //delete choices softly
            if ($question->type !== QuestionTypeEnum::TEXTBOX->value) {
                QuestionChoice::where("question_id", $question_id)->delete();
            }

            //delete question softly
            Question::where("id", $question_id)->delete();

            //update position for questions after deleted one
            Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                $query->where('survey_id', $surveyId);
            })
                ->where('display_number', '>', $question->display_number)
                ->decrement('display_number');

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()
            ->json([
                "message" => "Question has been successfully removed"
            ]);
    }
}
