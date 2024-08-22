<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BadQueryParamsException;
use App\Models\SurveyCollector;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class SurveyCollectorRepository extends BaseRepository implements SurveyCollectorRepositoryInterface
{
    protected function getModel(): string
    {
        return SurveyCollector::class;
    }

    public function countBySurveyId(string $surveyId, ?string $type): int
    {
        $this->model = $this->model->where("survey_id", $surveyId);

        if (isset($type)) {
            $this->model = $this->model->where("type", $type);
        }

        $count = $this->model->count();
        $this->resetModel();

        return $count;
    }

    public function findBySurveyId(string $surveyId, string|null $sort)
    {
        $builder = $this->model
            ->where("survey_id", $surveyId);

        if ($sort) {
            $allowedSortColumns = ["name", "updated_at", "status", "responses_count"];
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
