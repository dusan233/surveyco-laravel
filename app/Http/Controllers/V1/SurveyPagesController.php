<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\Survey;
use App\Models\SurveyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
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
                ->orderByDesc("display_number")->first()->lockForUpdate()->display_number + 1;

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
}
