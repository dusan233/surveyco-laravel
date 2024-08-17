<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;

class SurveyResponsePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewSurveyResponses(User $user, Survey $survey): bool
    {
        return $user->id === "dw";
    }

    public function view(User $user, SurveyResponse $surveyResponse): bool
    {
        return $user->id === $surveyResponse->surveyCollector->survey->author_id;
    }

}
