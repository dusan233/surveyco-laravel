<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StoreUserSurveyRequest;
use App\Http\Resources\V1\SurveyResource;
use App\Models\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Services\Handlers\Survey\CreateSurveyHandler;
use App\Services\Handlers\Survey\DTO\CreateSurveyDTO;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;



class UserSurveysController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private CreateSurveyHandler $createSurveyHandler;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        CreateSurveyHandler $createSurveyHandler
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->createSurveyHandler = $createSurveyHandler;
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
            return new UnauthorizedException();
        }

        $surveyData = array_merge($request->validated(), [
            'author_id' => $author_id,
        ]);

        $survey = $this->createSurveyHandler->handle(new CreateSurveyDTO(
            $surveyData["title"],
            $surveyData["category"],
            $surveyData["author_id"],
        ));

        return $this->resourceResponse(SurveyResource::class, $survey, Response::HTTP_CREATED);
    }
}
