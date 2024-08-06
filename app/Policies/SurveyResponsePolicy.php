<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyResponsePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewSurveyResponses(User $user, Survey $survey): bool
    {
        return $user->id === $survey->author_id;
    }

}
