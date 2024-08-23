<?php

namespace App\Services\Handlers\Question;

use App\Exceptions\PageQuestionsCountExceededException;
use App\Models\Question;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\Question\DTO\CreateQuestionDTO;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\DatabaseManager;



class CreateQuestionHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
    ) {
    }

    public function handle(CreateQuestionDTO $createQuestionDTO): Question
    {
        return $this->databaseManager->transaction(function () use ($createQuestionDTO) {
            $targetPage = $this->surveyPageRepository
                ->loadRelationCount(new Relationship(name: "questions"))
                ->findById($createQuestionDTO->page_id);

            if ($targetPage->questions_count === 50) {
                throw new PageQuestionsCountExceededException("Max number of questions per page is 50");
            }

            $newQuestionPosition = $this->calculateQuestionPosition($createQuestionDTO, $targetPage);

            $this->incrementQuestionsAfterPosition($createQuestionDTO, $newQuestionPosition);

            $newQuestion = $this->createQuestion($createQuestionDTO, $newQuestionPosition);

            if (!is_null($createQuestionDTO->choices)) {
                $this->createQuestionChoices($createQuestionDTO, $newQuestion->id);
            }

            return $this->questionRepository
                ->loadRelation(new Relationship(name: "choices"))
                ->findById($newQuestion->id);
        });
    }

    private function incrementQuestionsAfterPosition(
        CreateQuestionDTO $createQuestionDTO,
        int $newQuestionPosition
    ) {
        $surveyId = $createQuestionDTO->survey_id;

        $this->questionRepository->incrementWhere([
            function (Builder $query) use ($surveyId) {
                $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                    $query->where('survey_id', $surveyId);
                });
            },
            ['display_number', '>=', $newQuestionPosition],
        ], "display_number");
    }

    private function createQuestionChoices(CreateQuestionDTO $createQuestionDTO, string $questionId)
    {
        $createQuestionDTO->choices->each(function ($choice) use ($questionId) {
            $this->questionChoiceRepository->create([
                "question_id" => $questionId,
                "description" => $choice->description,
                "description_image" => $choice->description_image,
                "display_number" => $choice->position
            ]);
        });
    }

    private function createQuestion(CreateQuestionDTO $createQuestionDTO, int $position): Question
    {
        return $this->questionRepository->create([
            "description" => $createQuestionDTO->description,
            "description_image" => $createQuestionDTO->description_image,
            "type" => $createQuestionDTO->type,
            "required" => $createQuestionDTO->required,
            "display_number" => $position,
            "survey_page_id" => $createQuestionDTO->page_id,
            "randomize" => $createQuestionDTO->randomize,
        ]);
    }
    private function calculateQuestionPosition(CreateQuestionDTO $createQuestionDTO, SurveyPage $targetPage): int
    {
        $newQuestionPosition = 0;

        if ($targetPage->questions_count === 0) {
            $previousPageWithQuestions = $this->surveyPageRepository
                ->findFirstWhere([
                    function (Builder $query) {
                        $query->orderBy('display_number', 'desc');
                    },
                    function (Builder $query) use ($targetPage) {
                        $query
                            ->where("survey_id", $targetPage->survey_id)
                            ->has("questions");
                    },
                    ["display_number", "<", $targetPage->display_number],
                ]);


            if ($previousPageWithQuestions) {
                $previousPageLastQuestion = $this->questionRepository
                    ->findLastByPageId($previousPageWithQuestions->id);

                $newQuestionPosition = $previousPageLastQuestion->display_number + 1;
            } else {
                $newQuestionPosition = 1;
            }
        } else {
            $targetPageLastQuestion = $this->questionRepository
                ->findLastByPageId($createQuestionDTO->page_id);
            $newQuestionPosition = $targetPageLastQuestion->display_number + 1;
        }

        return $newQuestionPosition;
    }
}
