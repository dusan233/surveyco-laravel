<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\Survey;
use App\Models\SurveyPage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SurveyPagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $survey_id)
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
