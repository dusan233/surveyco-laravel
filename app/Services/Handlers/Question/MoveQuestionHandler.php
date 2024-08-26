<?php

namespace App\Services\Handlers\Question;
use App\Enums\PlacementPositionEnum;
use App\Models\Question;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\Question\DTO\MoveQuestionDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class MoveQuestionHandler
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
    ) {
    }

    public function handle(MoveQuestionDTO $moveQuestionDTO): Question
    {
        return $this->databaseManager->transaction(function () use ($moveQuestionDTO) {
            $targetPage = $this->surveyPageRepository
                ->loadRelationCount(new Relationship(name: "questions"))
                ->findById($moveQuestionDTO->target_page_id);

            if ($targetPage->survey_id !== $moveQuestionDTO->survey_id) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            $sourceQuestion = $this->questionRepository->findById($moveQuestionDTO->source_question_id);

            $this->moveQuestion($moveQuestionDTO, $targetPage, $sourceQuestion);

            return $this->questionRepository
                ->loadRelation(new Relationship(name: "choices"))
                ->findById($sourceQuestion->id);
        });
    }

    private function moveQuestion(
        MoveQuestionDTO $moveQuestionDTO,
        SurveyPage $targetPage,
        Question $sourceQuestion
    ) {
        $surveyId = $moveQuestionDTO->survey_id;
        $newQuestionPosition = 0;
        if ($targetPage->questions_count !== 0) {
            if (!is_null($moveQuestionDTO->target_question_id)) {
                $targetQuestion = $this->questionRepository->findById($moveQuestionDTO->target_question_id);

                if ($targetQuestion->surveyPage->id !== $targetPage->id) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                if ($targetQuestion->display_number > $sourceQuestion->display_number) {
                    $newQuestionPosition = $moveQuestionDTO->position === PlacementPositionEnum::AFTER->value
                        ? $targetQuestion->display_number
                        : $targetQuestion->display_number - 1;


                    $this->questionRepository->decrementWhere(
                        [
                            function (Builder $query) use ($surveyId) {
                                $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                    $query->where("survey_id", $surveyId);
                                });
                            },
                            ["display_number", ">", $sourceQuestion->display_number],
                            ["display_number", "<=", $newQuestionPosition]
                        ],
                        "display_number"
                    );
                } else {
                    $newQuestionPosition = $moveQuestionDTO->position === PlacementPositionEnum::AFTER->value
                        ? $targetQuestion->display_number + 1
                        : $targetQuestion->display_number;

                    $this->questionRepository->incrementWhere(
                        [
                            function (Builder $query) use ($surveyId) {
                                $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                    $query->where("survey_id", $surveyId);
                                });
                            },
                            ["display_number", "<", $sourceQuestion->display_number],
                            ["display_number", ">=", $newQuestionPosition]
                        ],
                        "display_number"
                    );
                }
            } else {
                $targetPageLastQuestion = $this->questionRepository->findFirstWhere([
                    ["survey_page_id", "=", $targetPage->id],
                    function (Builder $query) {
                        $query->orderByDesc("display_number");
                    }
                ]);

                if (!$targetPageLastQuestion) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                if ($targetPageLastQuestion->display_number > $sourceQuestion->display_number) {
                    $newQuestionPosition = $moveQuestionDTO->position === PlacementPositionEnum::AFTER->value
                        ? $targetPageLastQuestion->display_number
                        : $targetPageLastQuestion->display_number - 1;

                    $this->questionRepository->decrementWhere([
                        function (Builder $query) use ($surveyId) {
                            $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                $query->where("survey_id", $surveyId);
                            });
                        },
                        ["display_number", ">", $sourceQuestion->display_number],
                        ["display_number", "<=", $newQuestionPosition]
                    ], "display_number");
                } else {
                    $newQuestionPosition = $moveQuestionDTO->position === PlacementPositionEnum::AFTER->value
                        ? $targetPageLastQuestion->display_number + 1
                        : $targetPageLastQuestion->display_number;

                    $this->questionRepository->incrementWhere([
                        function (Builder $query) use ($surveyId) {
                            $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                $query->where("survey_id", $surveyId);
                            });
                        },
                        ["display_number", "<", $sourceQuestion->display_number],
                        ["display_number", ">=", $newQuestionPosition]
                    ], "display_number");
                }
            }
        } else {
            $previousPageWithQuestions = $this->surveyPageRepository
                ->findFirstWhere([
                    ["display_number", "<", $targetPage->display_number],
                    ["survey_id", "=", $surveyId],
                    function (Builder $query) {
                        $query->has("questions");
                    },
                    function (Builder $query) {
                        $query->orderByDesc("display_number");
                    }
                ]);

            if ($previousPageWithQuestions) {
                $previousPageWithQuestionsLastQuestion = $this->questionRepository
                    ->findFirstWhere([
                        ["survey_page_id", "=", $previousPageWithQuestions->id],
                        function (Builder $query) {
                            $query->orderByDesc("display_number");
                        }
                    ]);

                if (!$previousPageWithQuestionsLastQuestion) {
                    throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                }

                if ($sourceQuestion->display_number > $previousPageWithQuestionsLastQuestion->display_number) {
                    $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number + 1;

                    $this->questionRepository->incrementWhere([
                        function (Builder $query) use ($surveyId) {
                            $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                $query->where("survey_id", $surveyId);
                            });
                        },
                        ["display_number", ">=", $newQuestionPosition],
                        ["display_number", "<", $sourceQuestion->display_number]
                    ], "display_number");
                } else {
                    if ($sourceQuestion->id === $previousPageWithQuestionsLastQuestion->id) {
                        $newQuestionPosition = 1;
                    } else {
                        $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number;

                        $this->questionRepository->decrementWhere([
                            function (Builder $query) use ($surveyId) {
                                $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                                    $query->where("survey_id", $surveyId);
                                });
                            },
                            ["display_number", "<=", $newQuestionPosition],
                            ["display_number", ">", $sourceQuestion->display_number]
                        ], "display_number");
                    }
                }
            } else {
                $newQuestionPosition = 1;
                $this->questionRepository->incrementWhere([
                    function (Builder $query) use ($surveyId) {
                        $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                            $query->where("survey_id", $surveyId);
                        });
                    },
                    ["display_number", ">=", $newQuestionPosition],
                    ["display_number", "<", $sourceQuestion->display_number]
                ], "display_number");
            }
        }

        $this->questionRepository->updateById($sourceQuestion->id, [
            "display_number" => $newQuestionPosition,
            "survey_page_id" => $targetPage->id
        ]);
    }
}
