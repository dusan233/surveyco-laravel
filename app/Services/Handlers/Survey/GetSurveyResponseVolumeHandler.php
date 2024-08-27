<?php

namespace App\Services\Handlers\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Carbon\Carbon;



class GetSurveyResponseVolumeHandler
{
    public function __construct(
        private readonly SurveyRepositoryInterface $surveyRepository,
    ) {
    }

    public function handle($surveyId)
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(9);

        $dates = collect(range(0, 9))->mapWithKeys(function ($i) use ($today) {
            return [$today->copy()->subDays($i)->format('Y-m-d') => 0];
        });

        $responseCounts = $this->surveyRepository->responseVolumeById($surveyId, $startDate, $today);

        $finalResponse = $dates->merge($responseCounts->mapWithKeys(function ($item) {
            return [$item->date => $item->response_count];
        }))
            ->map(function ($item, $date) {
                return [
                    'date' => $date,
                    'responses_count' => $item,
                ];
            })->values();

        return $finalResponse->toArray();
    }
}
