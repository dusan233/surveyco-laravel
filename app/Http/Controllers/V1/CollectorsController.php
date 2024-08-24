<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\UpdateCollectorRequest;
use App\Http\Requests\V1\UpdateCollectorStatusRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorDTO;
use App\Services\Handlers\SurveyCollector\DTO\UpdateCollectorStatusDTO;
use App\Services\Handlers\SurveyCollector\UpdateCollectorHandler;
use App\Services\Handlers\SurveyCollector\UpdateCollectorStatusHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CollectorsController extends BaseController
{
    private SurveyCollectorRepositoryInterface $surveyCollectorRepository;
    private UpdateCollectorStatusHandler $updateCollectorStatusHandler;
    private UpdateCollectorHandler $updateCollectorHandler;

    public function __construct(
        SurveyCollectorRepositoryInterface $surveyCollectorRepository,
        UpdateCollectorStatusHandler $updateCollectorStatusHandler,
        UpdateCollectorHandler $updateCollectorHandler
    ) {
        $this->surveyCollectorRepository = $surveyCollectorRepository;
        $this->updateCollectorStatusHandler = $updateCollectorStatusHandler;
        $this->updateCollectorHandler = $updateCollectorHandler;
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
        $collector = SurveyCollector::find($collector_id);

        if (!$collector) {
            throw new ResourceNotFoundException("Collector resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("delete", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException();
        }

        try {
            DB::beginTransaction();

            SurveyCollector::where("id", $collector_id)
                ->delete();

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()
            ->json([
                "message" => "Collector has been successfully removed"
            ]);
    }
}
