<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\QuestionResource;
use App\Http\Resources\V1\QuestionRollupResource;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use App\Repositories\Eloquent\SurveyPageRepository;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class SurveyPagesController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private SurveyPageRepositoryInterface $surveyPageRepository;
    private SurveyResponseRepositoryInterface $surveyResponseRepository;
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        SurveyPageRepositoryInterface $surveyPageRepository,
        SurveyResponseRepositoryInterface $surveyResponseRepository,
        QuestionRepositoryInterface $questionRepository,
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->surveyPageRepository = $surveyPageRepository;
        $this->surveyResponseRepository = $surveyResponseRepository;
        $this->questionRepository = $questionRepository;
    }
    public function index(string $survey_id)
    {
        $this->surveyRepository->findById($survey_id);

        $pages = $this->surveyPageRepository
            ->loadRelationCount(new Relationship(name: "questions"))
            ->findBySurveyId($survey_id);

        return $this->resourceResponse(SurveyPageResource::class, $pages);
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
        $surveyPage = $this->surveyPageRepository->findById($page_id);

        if ($surveyPage->survey_id !== $survey_id) {
            throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
        }

        $surveyResponseCount = $this->surveyResponseRepository->countBySurveyId($survey_id);

        $pageQuestions = $this->questionRepository
            ->resultSummariesByPageId($page_id)
            ->map(function ($question) use ($surveyResponseCount) {
                $question->survey_responses_count = $surveyResponseCount;
                return $question;
            });

        return $this->resourceResponse(QuestionRollupResource::class, $pageQuestions);
    }
}
