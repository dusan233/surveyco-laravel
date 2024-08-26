<?php

namespace App\Services\Handlers\SurveyPage;
use App\Enums\PlacementPositionEnum;
use App\Enums\QuestionTypeEnum;
use App\Models\Question;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\SurveyPage\DTO\CopySurveyPageDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;


class CopySurveyPageHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(CopySurveyPageDTO $copySurveyPageDTO): SurveyPage
    {
        return $this->databaseManager->transaction(function () use ($copySurveyPageDTO) {
            $sourcePage = $this->surveyPageRepository
                ->loadRelationCount(new Relationship(name: "questions"))
                ->findById($copySurveyPageDTO->source_page_id);

            $targetPage = $this->surveyPageRepository->findById($copySurveyPageDTO->target_page_id);

            if ($sourcePage->survey_id !== $targetPage->survey_id) {
                throw new BadRequestException("Bad request", Response::HTTP_BAD_REQUEST);
            }

            $newPagePosition = $copySurveyPageDTO->position === PlacementPositionEnum::AFTER->value
                ? $targetPage->display_number + 1
                : $targetPage->display_number;

            $previousPageWithQuestions = $this->surveyPageRepository->findFirstWhere([
                function (Builder $query) {
                    $query->has("questions");
                },
                ["display_number", "<", $newPagePosition],
                ["survey_id", "=", $copySurveyPageDTO->survey_id],
                function (Builder $query) {
                    $query->orderByDesc("display_number");
                },
            ]);

            $previousPageWithQuestionsLastQuestion = $this->questionRepository->findFirstWhere([
                ["survey_page_id", "=", $previousPageWithQuestions->id],
                function (Builder $query) {
                    $query->orderByDesc("display_number");
                },
            ]);

            $this->updatePagePositions($copySurveyPageDTO, $newPagePosition);

            $this->updateQuestionPositions(
                $copySurveyPageDTO,
                $previousPageWithQuestionsLastQuestion,
                $sourcePage->questions_count
            );

            $copiedPage = $this->copyPage(
                $copySurveyPageDTO,
                $previousPageWithQuestionsLastQuestion,
                $newPagePosition
            );

            return $copiedPage;
        });
    }

    private function copyPage(
        CopySurveyPageDTO $copySurveyPageDTO,
        Question $previousPageWithQuestionsLastQuestion,
        int $newPagePosition
    ) {
        $newPage = $this->surveyPageRepository->create([
            "display_number" => $newPagePosition,
            "survey_id" => $copySurveyPageDTO->survey_id
        ]);

        $sourcePageQuestions = $this->questionRepository
            ->loadRelation(new Relationship(name: "choices"))
            ->findByPageId($copySurveyPageDTO->source_page_id);

        $sourcePageQuestions->each(function ($question) use ($previousPageWithQuestionsLastQuestion, $newPage) {
            $newPageQuestionCopy = $this->questionRepository->create([
                "description" => $question->description,
                "description_image" => $question->description_image,
                "type" => $question->type,
                "display_number" => ($previousPageWithQuestionsLastQuestion
                    ? $previousPageWithQuestionsLastQuestion->display_number
                    : 0) + 1,
                "required" => $question->required,
                "randomize" => $question->randomize,
                "survey_page_id" => $newPage->id,
            ]);

            if ($question->type !== QuestionTypeEnum::TEXTBOX->value) {
                $question->choices->each(function ($choice) use ($newPageQuestionCopy) {
                    $this->questionChoiceRepository->create([
                        "description" => $choice->description,
                        "description_image" => $choice->description_image,
                        "display_number" => $choice->display_number,
                        "question_id" => $newPageQuestionCopy->id
                    ]);
                });
            }
        });

        return $newPage;
    }
    private function updatePagePositions(CopySurveyPageDTO $copySurveyPageDTO, int $newPagePosition)
    {
        $this->surveyPageRepository->incrementWhere([
            ["survey_id", "=", $copySurveyPageDTO->survey_id],
            ["display_number", ">=", $newPagePosition]
        ], "display_number");
    }
    private function updateQuestionPositions(
        CopySurveyPageDTO $copySurveyPageDTO,
        Question $previousPageWithQuestionsLastQuestion,
        int $amount
    ) {
        $surveyId = $copySurveyPageDTO->survey_id;
        $this->questionRepository->incrementWhere([
            function (Builder $query) use ($surveyId) {
                $query->whereHas("surveyPage", function (Builder $query) use ($surveyId) {
                    $query->where("survey_id", $surveyId);
                });
            },
            [
                "display_number",
                ">",
                $previousPageWithQuestionsLastQuestion
                ? $previousPageWithQuestionsLastQuestion->display_number
                : 0
            ]
        ], "display_number", $amount);
    }

}
