<?php

namespace App\Repositories\Interfaces;
use App\Repositories\Eloquent\Value\Relationship;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T
 */
interface RepositoryInterface
{
    public const DEFAULT_COLUMNS = ['*'];

    /** @var int */
    public const DEFAULT_PAGINATE_LIMIT = 15;

    /** @var int */
    public const MAX_PAGINATE_LIMIT = 100;


    /**
     * @param array $columns
     * @return Collection<int, T>
     */
    public function all(array $columns = self::DEFAULT_COLUMNS): Collection;


    /**
     * @param array $where
     * @param int $limit
     * @param array $columns
     * @return LengthAwarePaginator<T>
     */
    public function paginate(
        int $limit = self::DEFAULT_PAGINATE_LIMIT,
        array $columns = self::DEFAULT_COLUMNS
    ): LengthAwarePaginator;

    /**
     * @param int $id
     * @param array $columns
     * @return T
     *
     */
    public function findById(string $id, array $columns = self::DEFAULT_COLUMNS): Model;
    public function findWhere(array $where, array $columns = self::DEFAULT_COLUMNS): Collection;
    public function findFirstWhere(array $where, array $columns = self::DEFAULT_COLUMNS): ?Model;
    public function loadRelation(string|Relationship $relationship): static;
    public function loadRelationCount(Relationship $relationship): static;

    /**
     * @param array $attributes
     * @return T
     */
    public function create(array $attributes): Model;

    /**
     * @param int $id
     * @param array $attributes
     * @param array $where
     * @return T
     */
    public function updateByIdWhere(int $id, array $attributes, array $where): Model;

    public function deleteById(int $id): bool;

    public function deleteWhere(array $conditions): int;

    public function increment(int|float $id, string $column, int|float $amount = 1): int;

    public function decrement(int|float $id, string $column, int|float $amount = 1): int;

    public function incrementWhere(array $where, string $column, int|float $amount = 1): int;
}
