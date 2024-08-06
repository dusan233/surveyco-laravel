<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SurveyResponseResource;
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
}
