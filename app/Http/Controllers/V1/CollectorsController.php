<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\UpdateCollectorRequest;
use App\Http\Requests\V1\UpdateCollectorStatusRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Services\Handlers\SurveyCollector\DeleteCollectorHandler;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorDTO;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorStatusDTO;
use App\Services\Handlers\SurveyCollector\UpdateCollectorHandler;
use App\Services\Handlers\SurveyCollector\UpdateCollectorStatusHandler;
use Illuminate\Http\Request;

class CollectorsController extends BaseController
{
    private SurveyCollectorRepositoryInterface $surveyCollectorRepository;
    private UpdateCollectorStatusHandler $updateCollectorStatusHandler;
    private UpdateCollectorHandler $updateCollectorHandler;
    private DeleteCollectorHandler $deleteCollectorHandler;

    public function __construct(
        SurveyCollectorRepositoryInterface $surveyCollectorRepository,
        UpdateCollectorStatusHandler $updateCollectorStatusHandler,
        UpdateCollectorHandler $updateCollectorHandler,
        DeleteCollectorHandler $deleteCollectorHandler
    ) {
        $this->surveyCollectorRepository = $surveyCollectorRepository;
        $this->updateCollectorStatusHandler = $updateCollectorStatusHandler;
        $this->updateCollectorHandler = $updateCollectorHandler;
        $this->deleteCollectorHandler = $deleteCollectorHandler;
    }
    public function show(string $collector_id)
    {
        $collector = $this->surveyCollectorRepository->findById($collector_id);

        return $this->resourceResponse(CollectorResource::class, $collector);
    }
    public function update(UpdateCollectorRequest $request, string $collector_id)
    {
        $collector = $this->surveyCollectorRepository->findById($collector_id);

        if ($request->user()->cannot("update", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException();
        }

        $updateCollectorData = $request->validated();

        $updatedCollector = $this->updateCollectorHandler->handle(new UpdateCollectorDTO(
            $collector_id,
            $updateCollectorData["name"]
        ));

        return $this->resourceResponse(CollectorResource::class, $updatedCollector);
    }

    public function updateStatus(UpdateCollectorStatusRequest $request, string $collector_id)
    {
        $collector = $this->surveyCollectorRepository->findById($collector_id);

        if ($request->user()->cannot("update", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException();
        }

        $updateCollectorStatusData = $request->validated();

        $updatedCollector = $this->updateCollectorStatusHandler->handle(new UpdateCollectorStatusDTO(
            $collector->id,
            $updateCollectorStatusData["status"]
        ));

        return $this->resourceResponse(CollectorResource::class, $updatedCollector);
    }

    public function destroy(Request $request, string $collector_id)
    {
        $collector = $this->surveyCollectorRepository->findById($collector_id);

        if ($request->user()->cannot("delete", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException();
        }

        $this->deleteCollectorHandler->handle($collector_id);

        return $this->deletedResponse();
    }
}
