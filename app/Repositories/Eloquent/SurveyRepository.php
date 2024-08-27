<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BadQueryParamsException;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class SurveyRepository extends BaseRepository implements SurveyRepositoryInterface
{
    protected function getModel(): string
    {
        return Survey::class;
    }

    public function responseVolumeById(string $surveyId, Carbon $startDate, Carbon $today): Collection
    {
        $responeVolumeData = SurveyResponse::select(
            DB::raw('DATE(survey_responses.created_at) as date'),
            DB::raw('COUNT(*) as response_count')
        )
            ->join('survey_collectors', 'survey_responses.survey_collector_id', '=', 'survey_collectors.id')
            ->where('survey_collectors.survey_id', $surveyId)
            ->whereBetween('survey_responses.created_at', [$startDate, $today->endOfDay()])
            ->groupBy(DB::raw('DATE(survey_responses.created_at)'))
            ->orderBy('date', 'desc')
            ->get();

        return $responeVolumeData;
    }
    public function findByCreatorId(string $authorId, string|null $sort)
    {
        $builder = $this->model
            ->where("author_id", $authorId);

        if ($sort) {
            $allowedSortColumns = ["created_at", "updated_at", "title", "responses_count", "questions_count"];
            foreach (explode(",", $sort) as $column) {
                $formatedColumn = $column[0] === "-"
                    ? substr($column, 1)
                    : $column;

                if (!in_array($formatedColumn, $allowedSortColumns)) {
                    throw new BadQueryParamsException("Bad params provided", Response::HTTP_BAD_REQUEST);
                }
            }

            $sortArr = array_map(function ($column) {
                return $column[0] === "-"
                    ? [substr($column, 1), "desc"]
                    : [$column, "asc"];
            }, explode(",", $sort));

            foreach ($sortArr as $sort) {
                $builder->orderBy($sort[0], $sort[1]);
            }
        } else {
            $builder->orderBy("created_at", "asc");
        }

        return $builder->paginate()->appends("sort", $sort);
    }
}
