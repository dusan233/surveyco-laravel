<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    public function viewUserSurveys(User $user, string $author_id): bool
    {
        return $user->id === $author_id;
    }

    public function viewSurveyResponsesVolume(User $user, Survey $survey)
    {
        return $user->id === $survey->author_id;
    }
    public function view(?User $user, Survey $survey): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, Survey $survey): true
    {
        return $user->id === $survey->author_id;
    }
    public function delete(User $user, Survey $survey): bool
    {
        //
    }
    public function restore(User $user, Survey $survey): bool
    {
        //
    }
    public function forceDelete(User $user, Survey $survey): bool
    {
        //
    }
}
