<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\SurveyCollector;
use App\Models\User;

class SurveyCollectorPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SurveyCollector $surveyCollector): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Survey $survey): bool
    {
        return $user->id === $survey->author_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SurveyCollector $surveyCollector): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SurveyCollector $surveyCollector): bool
    {
        //
    }
}
