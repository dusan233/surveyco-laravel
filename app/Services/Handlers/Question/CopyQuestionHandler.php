<?php

namespace App\Services\Handlers\Question;
use App\Enums\PlacementPositionEnum;
use App\Enums\QuestionTypeEnum;
use App\Exceptions\PageQuestionsCountExceededException;
use App\Models\Question;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\Question\DTO\CopyQuestionDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class CopyQuestionHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
    ) {
    }

    public function handle(CopyQuestionDTO $copyQuestionDTO): Question
    {
        return $this->databaseManager->transaction(function () use ($copyQuestionDTO) {
            $targetPage = $this->surveyPageRepository
                ->loadRelationCount(new Relationship(name: "questions"))
                ->findById($copyQuestionDTO->target_page_id);

            if ($targetPage->survey_id !== $copyQuestionDTO->survey_id) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            $sourceQuestion = $this->questionRepository->findById($copyQuestionDTO->source_question_id);

            if ($targetPage->questions_count === 50) {
                throw new PageQuestionsCountExceededException("Max number of questions per page is 50");
            }

            $newQuestionPosition = $this->calculateQuestionPosition($copyQuestionDTO, $targetPage);

            $this->incrementQuestionsAfterPosition($copyQuestionDTO, $newQuestionPosition);

            $newQuestion = $this->copyQuestion($copyQuestionDTO, $sourceQuestion, $newQuestionPosition);

            return $newQuestion;
        });
    }

    private function copyQuestion(
        CopyQuestionDTO $copyQuestionDTO,
        Question $copyQuestion,
        int $position
    ) {
        $newQuestion = $this->questionRepository->create([
            "description" => $copyQuestion->description,
            "description_image" => $copyQuestion->description_image,
            "type" => $copyQuestion->type,
            "required" => $copyQuestion->required,
            "display_number" => $position,
            "survey_page_id" => $copyQuestionDTO->target_page_id,
            "randomize" => $copyQuestion->randomize,
        ]);

        $newQuestionId = $newQuestion->id;
        if ($newQuestion->type !== QuestionTypeEnum::TEXTBOX->value) {
            $sourceQuestionChoices = $this->questionChoiceRepository->findWhere([
                "question_id" => $copyQuestion->id,
            ]);
            $sourceQuestionChoices->each(function ($choice) use ($newQuestionId) {
                $this->questionChoiceRepository->create([
                    "question_id" => $newQuestionId,
                    "description" => $choice->description,
                    "description_image" => $choice->description_image,
                    "display_number" => $choice->display_number
                ]);
            });
        }

        return $this->questionRepository
            ->loadRelation(new Relationship(name: "choices"))
            ->findById($newQuestionId);
    }

    private function incrementQuestionsAfterPosition(
        CopyQuestionDTO $copyQuestionDTO,
        int $newQuestionPosition
    ) {
        $surveyId = $copyQuestionDTO->survey_id;
        $this->questionRepository->incrementWhere(
            [
                function (Builder $query) use ($surveyId) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                        $query->where("survey_id", $surveyId);
                    });
                },
                ["display_number", ">=", $newQuestionPosition]
            ],
            "display_number"
        );
    }

    private function calculateQuestionPosition(CopyQuestionDTO $copyQuestionDTO, SurveyPage $targetPage): int
    {
        $newQuestionPosition = 0;
        if ($targetPage->questions_count !== 0) {
            if (!is_null($copyQuestionDTO->target_question_id)) {
                $targetQuestion = $this->questionRepository->findById($copyQuestionDTO->target_question_id);

                if ($targetQuestion->surveyPage->id !== $targetPage->id) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                $newQuestionPosition = $copyQuestionDTO->position === PlacementPositionEnum::AFTER->value
                    ? $targetQuestion->display_number + 1
                    : $targetQuestion->display_number;
            } else {
                $targetPageLastQuestion = $this->questionRepository->findFirstWhere([
                    function (Builder $query) {
                        $query->orderByDesc("display_number");
                    },
                    ["survey_page_id", "=", $targetPage->id]
                ]);

                if (!$targetPageLastQuestion) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                $newQuestionPosition = $targetPageLastQuestion->display_number + 1;
            }
        } else {
            $previousPageWithQuestions = $this->surveyPageRepository->findFirstWhere([
                ["display_number", "<", $targetPage->display_number],
                ["survey_id", "=", $copyQuestionDTO->survey_id],
                function (Builder $query) {
                    $query->has("questions");
                },
                function (Builder $query) {
                    $query->orderByDesc("display_number");
                },
            ]);

            if ($previousPageWithQuestions) {
                $previousPageWithQuestionsLastQuestion = $this->questionRepository->findFirstWhere([
                    function (Builder $query) use ($previousPageWithQuestions) {
                        $query->where("survey_page_id", $previousPageWithQuestions->id)
                            ->orderByDesc("display_number");
                    }
                ]);
                if (!$previousPageWithQuestionsLastQuestion) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number + 1;
            } else {
                $newQuestionPosition = 1;
            }
        }

        return $newQuestionPosition;
    }
}
