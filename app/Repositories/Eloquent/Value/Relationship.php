<?php

namespace App\Repositories\Eloquent\Value;

readonly class Relationship
{
    public function __construct(
        private ?array $nested = [],

        private string $name = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNested(): ?array
    {
        return $this->nested;
    }

    public function buildLaravelEagerLoadArray(): array
    {
        if (!$this->nested) {
            return [$this->getName()];
        }

        return $this->buildNested($this, '');
    }

    private function buildNested(Relationship $relationship, string $prefix): array
    {
        $results = [];

        if ($relationship->nested) {
            foreach ($relationship->nested as $nested) {
                $nestedPrefix = $prefix === '' ? $relationship->getName() : $prefix . '.' . $relationship->getName();
                $results[] = $nestedPrefix . '.' . $nested->getName();
                $results = array_merge($results, $this->buildNested($nested, $nestedPrefix));
            }
        }

        return $results;
    }
}
