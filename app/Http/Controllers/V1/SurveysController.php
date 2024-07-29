<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SurveysController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $survey_id)
    {
        $survey = Survey::find($survey_id)->loadCount(["questions", "pages", "responses"]);

        if (!$survey) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        return new SurveyResource($survey);
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
