<?php

namespace App\Services\Handlers\SurveyPage;

use App\Enums\PlacementPositionEnum;
use App\Models\SurveyPage;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\SurveyPage\DTO\MoveSurveyPageDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;


class MoveSurveyPageHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(MoveSurveyPageDTO $moveSurveyPageDTO)
    {
        return $this->databaseManager->transaction(function () use ($moveSurveyPageDTO) {
            $sourcePage = $this->surveyPageRepository->findById($moveSurveyPageDTO->source_page_id);

            $sourcePageQuestions = $this->questionRepository->findWhere([
                ["survey_page_id", "=", $sourcePage->id],
                function (Builder $query) {
                    $query->orderBy("display_number");
                }
            ]);

            $targetPage = $this->surveyPageRepository->findById($moveSurveyPageDTO->target_page_id);

            if ($sourcePage->survey_id !== $targetPage->survey_id) {
                throw new BadRequestException("Bad request", Response::HTTP_BAD_REQUEST);
            }

            $this->moveSurveyPage(
                $moveSurveyPageDTO,
                $sourcePage,
                $targetPage,
                $sourcePageQuestions
            );

            return $this->surveyPageRepository->findById($moveSurveyPageDTO->source_page_id);
        });
    }


    private function moveSurveyPage(
        MoveSurveyPageDTO $moveSurveyPageDTO,
        SurveyPage $sourcePage,
        SurveyPage $targetPage,
        Collection $sourcePageQuestions
    ) {
        $surveyId = $moveSurveyPageDTO->survey_id;
        $surveyQuestionsCount = $this->questionRepository->countBySurveyId($surveyId);

        $sourcePagePositionIsAfter = $sourcePage->display_number > $targetPage->display_number;
        $sourcePageNewPosition = $sourcePagePositionIsAfter
            ? ($moveSurveyPageDTO->position === PlacementPositionEnum::AFTER->value
                ? $targetPage->display_number + 1
                : $targetPage->display_number)
            : ($sourcePage->display_number < $targetPage->display_number
                ? ($moveSurveyPageDTO->position === PlacementPositionEnum::AFTER->value
                    ? $targetPage->display_number
                    : $targetPage->display_number - 1)
                : $sourcePage->display_number);

        if ($sourcePagePositionIsAfter) {
            $condition = $moveSurveyPageDTO->position === PlacementPositionEnum::AFTER->value
                ? ">" : ">=";

            $this->surveyPageRepository->incrementWhere([
                ["survey_id", "=", $surveyId],
                ["display_number", $condition, $targetPage->display_number],
                ["display_number", "<", $sourcePage->display_number]
            ], "display_number");

            $previousPageWithQuestions = $this->surveyPageRepository->findFirstWhere([
                ["display_number", "<", $sourcePageNewPosition],
                ["survey_id", "=", $surveyId],
                function (Builder $query) {
                    $query->has("questions");
                },
                function (Builder $query) {
                    $query->orderByDesc("display_number");
                }
            ]);

            $previousPageWithQuestionsLastQuestion = $previousPageWithQuestions
                ? $this->questionRepository->findFirstWhere([
                    ["survey_page_id", "=", $previousPageWithQuestions->id],
                    function (Builder $query) {
                        $query->orderByDesc("display_number");
                    }
                ]) : null;

            $this->questionRepository->incrementWhere([
                function (Builder $query) use ($surveyId) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                        $query->where("survey_id", $surveyId);
                    });
                },
                [
                    "display_number",
                    ">",
                    ($previousPageWithQuestionsLastQuestion
                        ? $previousPageWithQuestionsLastQuestion->display_number
                        : 0)
                ],
                ["display_number", "<", $sourcePageQuestions[0]->display_number]
            ], "display_number", count($sourcePageQuestions));

            $sourcePageQuestions->each(function ($question, $index) use ($previousPageWithQuestionsLastQuestion) {
                $this->questionRepository->updateById($question->id, [
                    'display_number' => ($previousPageWithQuestionsLastQuestion
                        ? ($previousPageWithQuestionsLastQuestion->display_number + $index + 1)
                        : $index + 1)
                ]);
            });
        } else if ($sourcePage->display_number < $targetPage->display_number) {
            $this->surveyPageRepository->decrementWhere([
                ["survey_id", "=", $surveyId],
                ["display_number", ">", $sourcePage->display_number],
                [
                    "display_number",
                    "<=",
                    ($moveSurveyPageDTO->position === PlacementPositionEnum::AFTER->value
                        ? $targetPage->display_number
                        : $targetPage->display_number - 1)
                ]
            ], "display_number");

            $nextPageWithQuestions = $this->surveyPageRepository->findFirstWhere([
                ["display_number", ">", $sourcePageNewPosition],
                ["survey_id", "=", $surveyId],
                function (Builder $query) {
                    $query->has("questions");
                },
                function (Builder $query) {
                    $query->orderBy("display_number");
                }
            ]);

            $nextPageWithQuestionsFirstQuestion = $nextPageWithQuestions
                ? $this->questionRepository->findFirstWhere([
                    ["survey_page_id", "=", $nextPageWithQuestions->id],
                    function (Builder $query) {
                        $query->orderBy("display_number");
                    }
                ]) : null;

            $this->questionRepository->decrementWhere([
                function (Builder $query) use ($surveyId) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                        $query->where("survey_id", $surveyId);
                    });
                },
                [
                    "display_number",
                    ">",
                    $sourcePageQuestions[count($sourcePageQuestions) - 1]->display_number
                ],
                [
                    "display_number",
                    "<",
                    ($nextPageWithQuestionsFirstQuestion
                        ? $nextPageWithQuestionsFirstQuestion->display_number
                        : $surveyQuestionsCount + 1)
                ]
            ], "display_number", count($sourcePageQuestions));

            $sourcePageQuestions->each(function ($question, $index) use ($nextPageWithQuestionsFirstQuestion, $sourcePageQuestions, $surveyQuestionsCount) {
                $this->questionRepository->updateById($question->id, [
                    "display_number" => ($nextPageWithQuestionsFirstQuestion
                        ? ($nextPageWithQuestionsFirstQuestion->display_number - count($sourcePageQuestions) + $index)
                        : ($surveyQuestionsCount + 1 - count($sourcePageQuestions) + $index))
                ]);
            });
        }

        $this->surveyPageRepository->updateById($sourcePage->id, [
            "display_number" => $sourcePageNewPosition
        ]);
    }
}
