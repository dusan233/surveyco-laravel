<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\SurveyPage;
use App\Models\User;

class SurveyPagePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Survey $survey): bool
    {
        return $user->id === $survey->author_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SurveyPage $surveyPage): bool
    {
        return $user->id === $surveyPage->survey->author_id;
    }

    public function copy(User $user, SurveyPage $surveyPage): bool
    {
        return $user->id === $surveyPage->survey->author_id;
    }

    public function move(User $user, SurveyPage $surveyPage): bool
    {
        return $user->id === $surveyPage->survey->author_id;
    }
}
