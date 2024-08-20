<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StoreUserSurveyRequest;
use App\Http\Resources\V1\SurveyResource;
use App\Models\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class UserSurveysController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;

    public function __construct(SurveyRepositoryInterface $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }
    public function index(Request $request, string $author_id)
    {
        if ($request->user()->cannot("viewUserSurveys", [Survey::class, $author_id])) {
            throw new UnauthorizedException();
        }

        $querySort = $request->query("sort");

        $surveys = $this->surveyRepository->findByCreatorId($author_id, $querySort);

        return $this->resourceResponse(SurveyResource::class, $surveys);
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
