<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\QuestionRollupResource;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use App\Services\Handlers\SurveyPage\CreateSurveyPageHandler;
use App\Services\Handlers\SurveyPage\DTO\CreateSurveyPageDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class SurveyPagesController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private SurveyPageRepositoryInterface $surveyPageRepository;
    private SurveyResponseRepositoryInterface $surveyResponseRepository;
    private QuestionRepositoryInterface $questionRepository;
    private CreateSurveyPageHandler $createSurveyPageHandler;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        SurveyPageRepositoryInterface $surveyPageRepository,
        SurveyResponseRepositoryInterface $surveyResponseRepository,
        QuestionRepositoryInterface $questionRepository,
        CreateSurveyPageHandler $createSurveyPageHandler,
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->surveyPageRepository = $surveyPageRepository;
        $this->surveyResponseRepository = $surveyResponseRepository;
        $this->questionRepository = $questionRepository;
        $this->createSurveyPageHandler = $createSurveyPageHandler;
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
        $survey = $this->surveyRepository->findById($survey_id);

        if ($request->user()->cannot("create", [SurveyPage::class, $survey])) {
            throw new UnauthorizedException();
        }

        $surveyPage = $this->createSurveyPageHandler->handle(new CreateSurveyPageDTO($survey_id));

        return $this->resourceResponse(SurveyPageResource::class, $surveyPage, Response::HTTP_CREATED);
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
