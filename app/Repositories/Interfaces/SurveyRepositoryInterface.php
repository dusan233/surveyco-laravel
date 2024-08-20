<?php

namespace App\Repositories\Interfaces;


interface SurveyRepositoryInterface extends RepositoryInterface
{
    public function findByCreatorId(string $authorId, string|null $sort);
}
