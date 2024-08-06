<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\QuestionResource;
use App\Http\Resources\V1\SurveyResponseResource;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class ResponsesController extends Controller
{
    public function show(Request $request, string $response_id)
    {
        $surveyResponse = SurveyResponse::find($response_id);

        if (!$surveyResponse) {
            throw new ResourceNotFoundException("Survey response resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("view", [SurveyResponse::class, $surveyResponse])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new SurveyResponseResource($surveyResponse);
    }

    public function details(Request $request, string $response_id)
    {
        $surveyResponse = SurveyResponse::find($response_id);

        if (!$surveyResponse) {
            throw new ResourceNotFoundException("Survey response resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("view", [SurveyResponse::class, $surveyResponse])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $page = $request->query("page") ? (int) $request->query("page") : 1;
        $surveyId = $surveyResponse->surveyCollector->survey_id;

        $surveyPage = SurveyPage::where("survey_id", $surveyId)
            ->where("display_number", $page)
            ->first();

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey page resource not found", Response::HTTP_NOT_FOUND);
        }

        $pageQuestions = $surveyPage->questions()->with([
            "questionResponses" => function ($query) use ($response_id) {
                $query->where("survey_response_id", $response_id)
                    ->with("questionResponseAnswers");
            },
            "choices"
        ])->get();

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
