<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\QuestionResource;
use App\Http\Resources\V1\SurveyResponseResource;
use App\Models\SurveyResponse;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Http\Request;

class ResponsesController extends BaseController
{
    private SurveyResponseRepositoryInterface $surveyResponseRepository;
    private SurveyPageRepositoryInterface $surveyPageRepository;
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        SurveyResponseRepositoryInterface $surveyResponseRepository,
        SurveyPageRepositoryInterface $surveyPageRepository,
        QuestionRepositoryInterface $questionRepository,
    ) {
        $this->surveyResponseRepository = $surveyResponseRepository;
        $this->surveyPageRepository = $surveyPageRepository;
        $this->questionRepository = $questionRepository;
    }
    public function show(Request $request, string $survey_id, string $response_id)
    {
        $surveyResponse = $this->surveyResponseRepository->findFirstWhere([
            "id" => $response_id,
            "survey_id" => $survey_id,
        ]);

        if (!$surveyResponse) {
            return $this->notFoundResponse();
        }

        if ($request->user()->cannot("view", [SurveyResponse::class, $surveyResponse])) {
            throw new UnauthorizedException();
        }

        return $this->resourceResponse(SurveyResponseResource::class, $surveyResponse);
    }

    public function details(Request $request, string $survey_id, string $response_id)
    {
        $surveyResponse = $this->surveyResponseRepository->findFirstWhere([
            "id" => $response_id,
            "survey_id" => $survey_id,
        ]);

        if (!$surveyResponse) {
            return $this->notFoundResponse();
        }

        if ($request->user()->cannot("view", [SurveyResponse::class, $surveyResponse])) {
            throw new UnauthorizedException();
        }

        $page = $request->query("page") ? (int) $request->query("page") : 1;
        $surveyId = $surveyResponse->surveyCollector->survey_id;

        $surveyPage = $this->surveyPageRepository
            ->findFirstWhere([
                "survey_id" => $surveyId,
                "display_number" => $page,
            ]);

        if (!$surveyPage) {
            return $this->notFoundResponse();
        }

        $pageQuestions = $this->questionRepository->findWithAnswers($surveyPage->id, $surveyResponse->id);

        return response()->json([
            "data" => [
                "pageId" => $surveyPage->id,
                "pageNumber" => $page,
                "surveyResponse" => new SurveyResponseResource($surveyResponse),
                "questions" => QuestionResource::collection($pageQuestions),
            ]
        ]);
    }
}
