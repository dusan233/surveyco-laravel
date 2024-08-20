<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\UpdateCollectorRequest;
use App\Http\Requests\V1\UpdateCollectorStatusRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CollectorsController extends BaseController
{
    private SurveyCollectorRepositoryInterface $surveyCollectorRepository;

    public function __construct(
        SurveyCollectorRepositoryInterface $surveyCollectorRepository
    ) {
        $this->surveyCollectorRepository = $surveyCollectorRepository;
    }
    public function show(string $collector_id)
    {
        $collector = $this->surveyCollectorRepository->findById($collector_id);

        return $this->resourceResponse(CollectorResource::class, $collector);
    }
    public function update(UpdateCollectorRequest $request, string $collector_id)
    {
        $collector = SurveyCollector::find($collector_id);

        if (!$collector) {
            throw new ResourceNotFoundException("Collector resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("update", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $updateCollectorData = $request->validated();

        $collector->update([
            "name" => $updateCollectorData["name"]
        ]);
        $collector->refresh();

        return new CollectorResource($collector);
    }

    public function updateStatus(UpdateCollectorStatusRequest $request, string $collector_id)
    {
        $collector = SurveyCollector::find($collector_id);

        if (!$collector) {
            throw new ResourceNotFoundException("Collector resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("update", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $updateCollectorStatusData = $request->validated();

        $collector->update([
            "status" => $updateCollectorStatusData["status"]
        ]);
        $collector->refresh();

        return new CollectorResource($collector);
    }

    public function destroy(Request $request, string $collector_id)
    {
        $collector = SurveyCollector::find($collector_id);

        if (!$collector) {
            throw new ResourceNotFoundException("Collector resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("delete", [SurveyCollector::class, $collector])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
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
