<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BadQueryParamsException;
use App\Models\SurveyResponse;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;


class SurveyResponseRepository extends BaseRepository implements SurveyResponseRepositoryInterface
{
    protected function getModel(): string
    {
        return SurveyResponse::class;
    }

    public function countBySurveyId(string $surveyId): int
    {
        return $this->model
            ->whereHas('surveyCollector', function ($query) use ($surveyId) {
                $query->where('survey_id', $surveyId);
            })->count();
    }

    public function findBySurveyId(string $surveyId, string|null $sort)
    {
        $builder = $this->model
            ->select('survey_responses.*', 'survey_collectors.name AS collector_name', 'survey_collectors.survey_id') // Select necessary columns
            ->join('survey_collectors', 'survey_responses.survey_collector_id', '=', 'survey_collectors.id')
            ->where("survey_id", $surveyId);

        if ($sort) {
            $allowedSortColumns = ["status", "updated_at", "ip_address", "survey_collectors.name"];
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
