<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StoreCollectorRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\SurveyCollector;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Services\Handlers\SurveyCollector\CreateSurveyCollectorHandler;
use App\Services\Handlers\SurveyCollector\DTO\CreateSurveyCollectorDTO;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SurveyCollectorsController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private SurveyCollectorRepositoryInterface $surveyCollectorRepository;
    private CreateSurveyCollectorHandler $createSurveyCollectorHandler;
    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        SurveyCollectorRepositoryInterface $surveyCollectorRepository,
        CreateSurveyCollectorHandler $createSurveyCollectorHandler,
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->surveyCollectorRepository = $surveyCollectorRepository;
        $this->createSurveyCollectorHandler = $createSurveyCollectorHandler;
    }
    public function index(Request $request, string $survey_id)
    {
        $survey = $this->surveyRepository->findById($survey_id);

        if ($request->user()->cannot("viewSurveyCollectors", [SurveyCollector::class, $survey])) {
            throw new UnauthorizedException();
        }
        $querySort = $request->query("sort");

        $collectors = $this->surveyCollectorRepository
            ->loadRelationCount(new Relationship(name: "surveyResponses"))
            ->findBySurveyId($survey_id, $querySort);

        return $this->resourceResponse(CollectorResource::class, $collectors);
    }

    public function store(StoreCollectorRequest $request, string $survey_id)
    {
        $survey = $this->surveyRepository->findById($survey_id);

        if ($request->user()->cannot("create", [SurveyCollector::class, $survey])) {
            throw new UnauthorizedException();
        }

        $createCollectorData = array_merge($request->validated(), [
            'survey_id' => $survey_id,
        ]);

        $newCollector = $this->createSurveyCollectorHandler->handle(new CreateSurveyCollectorDTO(
            $createCollectorData["type"],
            $createCollectorData["survey_id"]
        ));

        return $this->resourceResponse(CollectorResource::class, $newCollector, Response::HTTP_CREATED);
    }
}
