<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\BadQueryParamsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserSurveyRequest;
use App\Http\Resources\V1\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;



class UserSurveysController extends Controller
{
    public function index(Request $request, string $author_id)
    {
        if ($request->user()->cannot("viewUserSurveys", [Survey::class, $author_id])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $builder = Survey::where("author_id", $author_id)->withCount(["questions", "responses", "pages"]);
        $querySort = $request->query("sort");

        if ($querySort) {
            $allowedSortColumns = ["created_at", "updated_at", "title", "responses_count", "questions_count"];
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

        return SurveyResource::collection(
            $builder->paginate()->appends("sort", $querySort)
        );
    }

    public function store(StoreUserSurveyRequest $request, string $author_id)
    {

        if ($request->user()->cannot("create", Survey::class)) {
            return response()->json([
                "error" => "You can not create surveys"
            ]);
        }

        $surveyData = $request->validated();

        DB::beginTransaction();
        try {
            $survey = Survey::create([
                "title" => $surveyData["title"],
                "category" => $surveyData["category"],
                "author_id" => $author_id
            ]);

            $survey->pages()->create([
                "display_number" => 1,
                "survey_id" => $survey->id
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new SurveyResource($survey);
    }
}
