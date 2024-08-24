<?php


namespace App\Services\Handlers\SurveyCollector\DTO;

class UpdateCollectorStatusDTO
{
    public function __construct(
        public readonly string $collector_id,
        public readonly string $status
    ) {
    }
}
