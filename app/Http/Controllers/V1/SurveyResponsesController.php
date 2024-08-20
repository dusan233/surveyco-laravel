<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\SurveyResponseResource;
use App\Models\SurveyResponse;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Http\Request;

class SurveyResponsesController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private SurveyResponseRepositoryInterface $surveyResponseRepository;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        SurveyResponseRepositoryInterface $surveyResponseRepository
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->surveyResponseRepository = $surveyResponseRepository;
    }
    public function index(Request $request, string $survey_id)
    {
        $survey = $this->surveyRepository->findById($survey_id);

        if ($request->user()->cannot("viewSurveyResponses", [SurveyResponse::class, $survey])) {
            throw new UnauthorizedException();
        }
        $querySort = $request->query("sort");

        $responses = $this->surveyResponseRepository
            ->findBySurveyId($survey_id, $querySort);

        return $this->resourceResponse(SurveyResponseResource::class, $responses);
    }

}
