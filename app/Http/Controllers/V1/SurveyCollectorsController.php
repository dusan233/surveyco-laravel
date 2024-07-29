<?php

namespace App\Http\Controllers\V1;

use App\Enums\CollectorStatusEnum;
use App\Enums\CollectorTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCollectorRequest;
use App\Http\Resources\V1\CollectorResource;
use App\Models\Survey;
use App\Models\SurveyCollector;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class SurveyCollectorsController extends Controller
{
    public function index()
    {
        //
    }
    public function store(StoreCollectorRequest $request, string $survey_id)
    {
        $survey = Survey::find($survey_id);

        if (!$survey) {
            throw new ResourceNotFoundException("Survye resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("create", [SurveyCollector::class, $survey])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
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
