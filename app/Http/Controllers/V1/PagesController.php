<?php

namespace App\Http\Controllers\V1;

use App\Enums\PlacementPositionEnum;
use App\Enums\QuestionTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CopyPageRequest;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\Survey;
use App\Models\SurveyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends Controller
{
    public function destroy(Request $request, string $page_id)
    {
        $surveyPage = SurveyPage::find($page_id);

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("delete", [SurveyPage::class, $surveyPage])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            DB::beginTransaction();

            $pages_count = Survey::lockForUpdate()
                ->findOrFail($surveyPage->survey_id)
                ->pages()
                ->count();
            if ($pages_count === 1) {
                throw new BadRequestException("Survey mus have at least 1 page", Response::HTTP_BAD_REQUEST);
            }

            $targetPage = SurveyPage::withCount("questions")
                ->lockForUpdate()
                ->find($page_id);
            if (!$targetPage) {
                throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
            }

            if ($targetPage->questions_count !== 0) {
                $targetPageLastQuestion = Question::where("survey_page_id", $targetPage->id)
                    ->lockForUpdate()
                    ->orderByDesc("display_number")
                    ->first();
                //delete question choices
                QuestionChoice::whereHas('question', function ($query) use ($targetPage) {
                    $query->where('survey_page_id', $targetPage->id);
                })->delete();

                //delete questions
                Question::where("survey_page_id", $targetPage->id)
                    ->delete();

                //update other questions positions
                Question::whereHas('surveyPage', function ($query) use ($targetPage) {
                    $query->where('survey_id', $targetPage->survey_id);
                })
                    ->where('display_number', '>', $targetPageLastQuestion->display_number)
                    ->decrement('display_number', $targetPage->questions_count);
            }

            SurveyPage::where("survey_id", $targetPage->survey_id)
                ->where("display_number", ">", $targetPage->display_number)
                ->decrement("display_number");

            SurveyPage::where("id", $targetPage->id)->delete();

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()
            ->json([
                "message" => "Page has been successfully removed"
            ]);
    }

    public function copy(CopyPageRequest $request, $source_page_id)
    {
        $surveyPage = SurveyPage::find($source_page_id);

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey page resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("copy", [SurveyPage::class, $surveyPage])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $copyPageData = $request->validated();

        try {
            DB::beginTransaction();

            $sourcePage = SurveyPage::withCount("questions")->find($source_page_id);
            if (!$sourcePage) {
                throw new ResourceNotFoundException("Survey page resource not found", Response::HTTP_NOT_FOUND);
            }

            $targetPage = SurveyPage::find($copyPageData["targetPageId"]);
            if (!$targetPage) {
                throw new ResourceNotFoundException("Survey page resource not found", Response::HTTP_NOT_FOUND);
            }
            if ($sourcePage->survey_id !== $targetPage->survey_id) {
                throw new BadRequestException("Bad request", Response::HTTP_BAD_REQUEST);
            }

            $newPagePosition = $copyPageData["position"] === PlacementPositionEnum::AFTER->value
                ? $targetPage->display_number + 1
                : $targetPage->display_number;

            $previousPageWithQuestions = SurveyPage::where('display_number', '<', $newPagePosition)
                ->where("survey_id", $sourcePage->survey_id)
                ->whereHas('questions')
                ->lockForUpdate()
                ->orderBy('display_number', 'desc')
                ->first();

            $previousPageWithQuestionsLastQuestion = Question::where("survey_page_id", $previousPageWithQuestions->id)
                ->lockForUpdate()
                ->orderByDesc("display_number")
                ->first();

            SurveyPage::where("survey_id", $sourcePage->survey_id)
                ->where("display_number", ">=", $newPagePosition)
                ->increment("display_number");

            Question::whereHas('surveyPage', function ($query) use ($sourcePage) {
                $query->where('survey_id', $sourcePage->survey_id);
            })
                ->where('display_number', '>', $previousPageWithQuestionsLastQuestion
                    ? $previousPageWithQuestionsLastQuestion->display_number
                    : 0)
                ->increment('display_number', $sourcePage->questions_count);

            $newPage = $sourcePage->replicate(["questions_count"])
                ->fill([
                    "display_number" => $newPagePosition,
                    "survey_id" => $sourcePage->survey_id
                ]);
            $newPage->save();

            $sourcePageQuestions = $sourcePage->questions()->with("choices")->get();
            $sourcePageQuestions->each(function ($question) use ($previousPageWithQuestionsLastQuestion, $newPage) {
                $newPageQuestionCopy = Question::create([
                    "description" => $question->description,
                    "description_image" => $question->description_image,
                    "type" => $question->type,
                    "display_number" => ($previousPageWithQuestionsLastQuestion
                        ? $previousPageWithQuestionsLastQuestion->display_number
                        : 0) + 1,
                    "required" => $question->required,
                    "randomize" => $question->randomize,
                    "survey_page_id" => $newPage->id,
                ]);

                if ($question->type !== QuestionTypeEnum::TEXTBOX->value) {
                    $newPageQuestionCopy->choices()->createMany($question->choices->map(function ($choice) {
                        return [
                            "description" => $choice->description,
                            "description_image" => $choice->description_image,
                            "display_number" => $choice->display_number,
                        ];
                    }));
                }
            });

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return new SurveyPageResource($newPage);
    }
}
