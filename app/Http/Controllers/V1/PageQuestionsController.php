<?php

namespace App\Http\Controllers\V1;

use App\Enums\QuestionTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePageQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\Question;
use App\Models\SurveyPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class PageQuestionsController extends Controller
{
    public function index(string $page_id)
    {
        $surveyPage = SurveyPage::find($page_id);

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        $questions = Question::where("survey_page_id", $page_id)
            ->orderBy("display_number", "asc")
            ->with("choices")
            ->get();

        return QuestionResource::collection($questions);
    }


    public function store(StorePageQuestionRequest $request, string $page_id)
    {
        $page = SurveyPage::find($page_id);

        if (!$page) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("create", [Question::class, $page])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $questionData = $request->validated();

        try {
            DB::beginTransaction();

            $targetPage = SurveyPage::withCount("questions")->lockForUpdate()->findOrFail($page_id);
            $surveyId = $targetPage->survey_id;
            $newQuestionPosition = 0;

            if ($targetPage->questions_count === 50) {
                throw new \Dotenv\Exception\ValidationException("Max questions per page exceeded", Response::HTTP_BAD_REQUEST);
            }

            if ($targetPage->questions_count === 0) {
                $previousPageWithQuestions = SurveyPage::where('display_number', '<', $targetPage->display_number)
                    ->where("survey_id", $surveyId)
                    ->whereHas('questions')
                    ->lockForUpdate()
                    ->orderBy('display_number', 'desc')
                    ->first();

                if ($previousPageWithQuestions) {
                    $previousPageLastQuestion = Question::where("survey_page_id", $previousPageWithQuestions->id)
                        ->lockForUpdate()
                        ->orderByDesc("display_number")
                        ->first();

                    $newQuestionPosition = $previousPageLastQuestion->display_number + 1;
                } else {
                    $newQuestionPosition = 1;
                }
            } else {
                $targetPageLastQuestion = Question::where("survey_page_id", $page_id)
                    ->lockForUpdate()
                    ->orderByDesc("display_number")
                    ->firstOrFail();
                $newQuestionPosition = $targetPageLastQuestion->display_number + 1;
            }

            Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                $query->where('survey_id', $surveyId);
            })
                ->where('display_number', '>=', $newQuestionPosition)
                ->increment('display_number');

            $newQuestion = Question::create([
                "description" => $questionData["description"],
                "description_image" => $questionData["descriptionImage"],
                "type" => $questionData["type"],
                "required" => $questionData["required"],
                "display_number" => $newQuestionPosition,
                "survey_page_id" => $targetPage->id,
                "randomize" => $questionData["type"] !== QuestionTypeEnum::TEXTBOX->value
                    ? $questionData["randomize"]
                    : null,
            ]);

            if (
                in_array($newQuestion->type, [
                    QuestionTypeEnum::DROPDOWN->value,
                    QuestionTypeEnum::CHECKBOX->value,
                    QuestionTypeEnum::SINGLE_CHOICE->value,
                ])
            ) {
                $newQuestion->choices()->createMany(
                    array_map(function ($choice) {
                        return [
                            "description" => $choice["description"],
                            "description_image" => $choice["descriptionImage"],
                            "display_number" => $choice["displayNumber"],
                        ];
                    }, $questionData["choices"])
                );

                $newQuestion->load("choices");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new QuestionResource($newQuestion);
    }
}
