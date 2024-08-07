<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\QuestionRollupResource;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class SurveyPagesController extends Controller
{
    public function index(Request $request, string $survey_id)
    {
        $survey = Survey::find($survey_id);

        if (!$survey) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        $pages = SurveyPage::where("survey_id", $survey_id)
            ->orderBy("display_number", "asc")
            ->withCount("questions")
            ->get();

        return SurveyPageResource::collection($pages);
    }
    public function store(Request $request, string $survey_id)
    {
        $survey = Survey::find($survey_id);

        if (!$survey) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("create", [SurveyPage::class, $survey])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        DB::beginTransaction();
        try {
            $newPagePosition = $survey->pages()
                ->orderByDesc("display_number")
                ->lockForUpdate()
                ->first()->display_number + 1;

            $surveyPage = SurveyPage::create([
                "survey_id" => $survey_id,
                "display_number" => $newPagePosition,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new SurveyPageResource($surveyPage);
    }

    public function rollups(Request $request, string $survey_id, string $page_id)
    {
        $surveyPage = SurveyPage::find($page_id);

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($surveyPage->survey_id !== $survey_id) {
            throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
        }

        $surveyResponseCount = SurveyResponse::whereHas('surveyCollector', function ($query) use ($survey_id) {
            $query->where('survey_id', $survey_id);
        })->count();

        $pageQuestions = Question::where("survey_page_id", $page_id)
            ->withCount("questionResponses")
            ->with([
                "choices" => function ($query) {
                    $query->withCount("questionResponseAnswers");
                },
            ])
            ->get()
            ->map(function ($question) use ($surveyResponseCount) {
                $question->survey_responses_count = $surveyResponseCount;
                return $question;
            });

        return QuestionRollupResource::collection($pageQuestions);
    }
}
