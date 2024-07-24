<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\SurveyPage;
use App\Models\User;

class QuestionPolicy
{

    public function create(User $user, SurveyPage $page): bool
    {
        return $user->id === $page->survey->author_id;
    }
    public function update(User $user, Question $question): bool
    {
        //
    }
    public function delete(User $user, Question $question): bool
    {
        //
    }
}
