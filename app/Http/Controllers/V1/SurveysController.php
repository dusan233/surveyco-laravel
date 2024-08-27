<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\SurveyResource;
use App\Models\Survey;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Services\Handlers\Survey\GetSurveyResponseVolumeHandler;
use Illuminate\Http\Request;

class SurveysController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private GetSurveyResponseVolumeHandler $getSurveyResponseVolumeHandler;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        GetSurveyResponseVolumeHandler $getSurveyResponseVolumeHandler
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->getSurveyResponseVolumeHandler = $getSurveyResponseVolumeHandler;
    }
    /**
     * Display the specified resource.
     */
    public function show(string $survey_id)
    {
        $survey = $this->surveyRepository
            ->loadRelationCount(new Relationship(name: "questions"))
            ->loadRelationCount(new Relationship(name: "pages"))
            ->loadRelationCount(new Relationship(name: "responses"))
            ->findById($survey_id);

        return $this->resourceResponse(SurveyResource::class, $survey);
    }

    public function responseVolume(Request $request, string $survey_id)
    {
        $survey = $this->surveyRepository->findById($survey_id);

        if ($request->user()->cannot("viewSurveyResponsesVolume", [Survey::class, $survey])) {
            throw new UnauthorizedException();
        }

        $responseVolume = $this->getSurveyResponseVolumeHandler->handle($survey_id);
        return response()->json($responseVolume);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
