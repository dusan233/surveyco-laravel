<?php

namespace App\Services\Handlers\Question;
use App\Repositories\Interfaces\QuestionAnswerRepositoryInterface;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\QuestionResponseRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;

class DeleteQuestionHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly QuestionResponseRepositoryInterface $questionResponseRepository,
        private readonly QuestionAnswerRepositoryInterface $questionAnswerRepository,
    ) {
    }

    public function handle(string $questionId, string $surveyId): void
    {
        $this->databaseManager->transaction(function () use ($questionId, $surveyId) {
            $this->deleteQuestion($questionId, $surveyId);
        });
    }

    private function deleteQuestion(string $questionId, string $surveyId)
    {
        $question = $this->questionRepository->findById($questionId);

        $this->questionAnswerRepository->deleteWhere([
            function (Builder $query) use ($questionId) {
                $query->whereHas("questionResponse", function (Builder $builder) use ($questionId) {
                    $builder->where("question_id", $questionId);
                });
            }
        ]);

        $this->questionResponseRepository->deleteWhere([
            "question_id" => $questionId
        ]);

        $this->questionChoiceRepository->deleteWhere([
            "question_id" => $questionId
        ]);

        $this->questionRepository->deleteById($questionId);

        $this->questionRepository->decrementWhere(
            [
                function (Builder $query) use ($surveyId) {
                    $query->whereHas("surveyPage", function (Builder $builder) use ($surveyId) {
                        $builder->where("survey_id", $surveyId);
                    });
                },
                ["display_number", ">", $question->display_number]
            ],
            "display_number"
        );
    }
}
