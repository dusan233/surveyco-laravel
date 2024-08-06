<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\BadQueryParamsException;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SurveyResponseResource;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class SurveyResponsesController extends Controller
{
    public function index(Request $request, string $survey_id)
    {
        $survey = Survey::find($survey_id);

        if (!$survey) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("viewSurveyResponses", [SurveyResponse::class, $survey])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $builder = DB::table('survey_responses')
            ->select('survey_responses.*', 'survey_collectors.name AS collector_name', 'survey_collectors.survey_id') // Select necessary columns
            ->join('survey_collectors', 'survey_responses.survey_collector_id', '=', 'survey_collectors.id')
            ->where("survey_id", $survey_id);

        $querySort = $request->query("sort");

        if ($querySort) {
            $allowedSortColumns = ["status", "updated_at", "ip_address", "survey_collectors.name"];
            foreach (explode(",", $querySort) as $column) {
                $formatedColumn = $column[0] === "-"
                    ? substr($column, 1)
                    : $column;

                if (!in_array($formatedColumn, $allowedSortColumns)) {
                    throw new BadQueryParamsException("Bad params provided", Response::HTTP_BAD_REQUEST);
                }
            }

            $sortArr = array_map(function ($column) {
                return $column[0] === "-"
                    ? [substr($column, 1), "desc"]
                    : [$column, "asc"];
            }, explode(",", $querySort));

            foreach ($sortArr as $sort) {
                $builder->orderBy($sort[0], $sort[1]);
            }
        } else {
            $builder->orderBy("created_at", "asc");
        }

        return SurveyResponseResource::collection(
            $builder->paginate()->appends("sort", $querySort)
        );
    }

}
