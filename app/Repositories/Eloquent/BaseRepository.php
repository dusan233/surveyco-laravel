<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\LengthAwarePaginator;


abstract class BaseRepository implements RepositoryInterface
{
    protected Model|BaseModel|Builder $model;
    protected Application $app;
    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->model = $this->initModel();
    }

    abstract protected function getModel(): string;

    public function paginate(
        int $limit = null,
        array $columns = self::DEFAULT_COLUMNS
    ): LengthAwarePaginator {
        $results = $this->model->paginate($this->getPaginationPerPage($limit), $columns);
        $this->resetModel();

        return $results;
    }
    public function findById(int $id, array $columns = self::DEFAULT_COLUMNS): Model
    {
        return $this->model->findOrFail($id, $columns);
    }
    public function all(array $columns = self::DEFAULT_COLUMNS): Collection
    {
        return $this->model->all($columns);
    }

    public function create(array $attributes): Model
    {
        $model = $this->model->newInstance(collect($attributes)->toArray());
        $model->save();
        $this->resetModel();

        return $model;
    }

    public function updateByIdWhere(int $id, array $attributes, array $where): Model
    {
        $model = $this->model->where($where)->findOrFail($id);
        $model->update($attributes);
        $this->resetModel();

        return $model;
    }

    public function deleteById(int $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }


    public function increment(int|float $id, string $column, int|float $amount = 1): int
    {
        return $this->model->findOrFail($id)->increment($column, $amount);
    }

    public function incrementWhere(array $where, string $column, int|float $amount = 1): int
    {
        $this->applyConditions($where);
        $count = $this->model->increment($column, $amount);
        $this->resetModel();

        return $count;
    }

    public function decrement(int|float $id, string $column, int|float $amount = 1): int
    {
        return $this->model->findOrFail($id)?->decrement($column, $amount);
    }

    public function deleteWhere(array $conditions): int
    {
        $this->applyConditions($conditions);
        $deleted = $this->model->delete();
        $this->resetModel();

        return $deleted;
    }

    public function loadRelation(string|Relationship $relationship): static
    {
        if (is_string($relationship)) {
            $relationship = new Relationship(name: $relationship);
        }

        // $this->eagerLoads[] = $relationship;
        $this->model = $this->model->with($relationship->buildLaravelEagerLoadArray());

        return $this;
    }

    public function loadRelationCount(Relationship $relationship): static
    {
        $this->model = $this->model->withCount($relationship->buildLaravelEagerLoadArray());

        return $this;
    }

    protected function applyConditions(array $where): void
    {
        foreach ($where as $field => $value) {
            if (is_callable($value) && !is_string($value)) {
                $this->model = $this->model->where($value);
            } elseif (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }

    private function getPaginationPerPage(?int $perPage): int
    {
        if (is_null($perPage)) {
            $perPage = self::DEFAULT_PAGINATE_LIMIT;
        }

        return (int) min($perPage, self::MAX_PAGINATE_LIMIT);
    }
    protected function resetModel(): void
    {
        $model = $this->getModel();
        $this->model = new $model();
    }

    protected function initModel(string $model = null): Model
    {
        return $this->app->make($model ?: $this->getModel());
    }
}
