<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BadQueryParamsException;
use App\Models\Survey;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;


class SurveyRepository extends BaseRepository implements SurveyRepositoryInterface
{
    protected function getModel(): string
    {
        return Survey::class;
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
