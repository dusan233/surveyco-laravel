<?php

namespace App\Http\Controllers\V1;

use App\Enums\CollectorStatusEnum;
use App\Enums\CollectorTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StoreCollectorRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\Survey;
use App\Models\SurveyCollector;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SurveyCollectorsController extends BaseController
{
    private SurveyRepositoryInterface $surveyRepository;
    private SurveyCollectorRepositoryInterface $surveyCollectorRepository;

    public function __construct(
        SurveyRepositoryInterface $surveyRepository,
        SurveyCollectorRepositoryInterface $surveyCollectorRepository
    ) {
        $this->surveyRepository = $surveyRepository;
        $this->surveyCollectorRepository = $surveyCollectorRepository;
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
        $survey = Survey::find($survey_id);

        if (!$survey) {
            throw new ResourceNotFoundException("Survye resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("create", [SurveyCollector::class, $survey])) {
            throw new UnauthorizedException();
        }

        $createCollectorData = $request->validated();

        try {
            DB::beginTransaction();

            $newCollectorName = $createCollectorData["type"] === CollectorTypeEnum::WEB_LINK->value
                ? "Web Link " . ((SurveyCollector::where("survey_id", $survey_id)
                    ->where("type", CollectorTypeEnum::WEB_LINK->value)
                    ->count()) + 1)
                : "New Collector";

            $newCollector = SurveyCollector::create([
                "type" => $createCollectorData["type"],
                "name" => $newCollectorName,
                "status" => CollectorStatusEnum::OPEN->value,
                "survey_id" => $survey_id
            ]);

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return new CollectorResource($newCollector);
    }
}
