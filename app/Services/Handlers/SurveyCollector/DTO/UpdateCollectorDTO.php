<?php


namespace App\Services\Handlers\SurveyCollector\DTO;

class UpdateCollectorDTO
{
    public function __construct(
        public readonly string $collector_id,
        public readonly string $name
    ) {
    }
}
