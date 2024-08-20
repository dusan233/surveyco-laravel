<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\SurveyResource;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Illuminate\Http\Request;

class SurveysController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;

    public function __construct(SurveyRepositoryInterface $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
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
