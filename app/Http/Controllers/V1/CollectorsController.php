<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\SurveyCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CollectorsController extends Controller
{
    public function show(string $id)
    {
        //
    }
    public function update(Request $request, string $id)
    {
        //
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
