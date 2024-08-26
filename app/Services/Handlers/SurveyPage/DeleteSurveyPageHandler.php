<?php

namespace App\Services\Handlers\SurveyPage;
use App\Models\SurveyPage;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionAnswerRepositoryInterface;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\QuestionResponseRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;


class DeleteSurveyPageHandler
{
    public function __construct(
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionAnswerRepositoryInterface $questionAnswerRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly QuestionResponseRepositoryInterface $questionResponseRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(string $pageId): void
    {
        return $this->databaseManager->transaction(function () use ($pageId) {
            $targetPage = $this->surveyPageRepository
                ->loadRelationCount(new Relationship(name: "questions"))
                ->findById($pageId);

            $surveyPagesCount = $this->surveyPageRepository->countBySurveyId($targetPage->survey_id);

            if ($surveyPagesCount === 1) {
                throw new BadRequestException("Survey mus have at least 1 page", Response::HTTP_BAD_REQUEST);
            }

            $this->deleteSurveyPage($targetPage);
        });
    }

    private function deleteSurveyPage(SurveyPage $page)
    {
        if ($page->questions_count !== 0) {
            $targetPageLastQuestion = $this->questionRepository->findFirstWhere([
                ["survey_page_id", "=", $page->id],
                function (Builder $query) {
                    $query->orderByDesc("display_number");
                }
            ]);

            $this->questionAnswerRepository->deleteWhere([
                function (Builder $query) use ($page) {
                    $query->whereHas("questionResponse", function (Builder $query) use ($page) {
                        $query->whereHas("question", function (Builder $query) use ($page) {
                            $query->where("survey_page_id", $page->id);
                        });
                    });
                }
            ]);

            $this->questionResponseRepository->deleteWhere([
                function (Builder $query) use ($page) {
                    $query->whereHas("question", function (Builder $query) use ($page) {
                        $query->where("survey_page_id", $page->id);
                    });
                }
            ]);

            $this->questionChoiceRepository->deleteWhere([
                function (Builder $query) use ($page) {
                    $query->whereHas("question", function (Builder $query) use ($page) {
                        $query->where("survey_page_id", $page->id);
                    });
                }
            ]);

            $this->questionRepository->deleteWhere([
                "survey_page_id" => $page->id
            ]);

            $this->questionRepository->decrementWhere(
                [
                    function (Builder $query) use ($page) {
                        $query->whereHas("surveyPage", function (Builder $query) use ($page) {
                            $query->where("survey_id", $page->survey_id);
                        });
                    },
                    ["display_number", ">", $targetPageLastQuestion->display_number]
                ],
                "display_number",
                $page->questions_count
            );
        }

        $this->surveyPageRepository->decrementWhere([
            ["survey_id", "=", $page->survey_id],
            ["display_number", ">", $page->display_number]
        ], "display_number");

        $this->surveyPageRepository->deleteById($page->id);
    }
}
