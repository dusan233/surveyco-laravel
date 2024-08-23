<?php

namespace App\Services\Handlers\Question;
use App\Models\Question;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionChoiceRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\QuestionResponseRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\Question\DTO\UpdateQuestionDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;


class UpdateQuestionHandler
{

    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly SurveyPageRepositoryInterface $surveyPageRepository,
        private readonly QuestionResponseRepositoryInterface $questionResponseRepository,
    ) {
    }

    public function handle(UpdateQuestionDTO $updateQuestionDTO): Question
    {
        return $this->databaseManager->transaction(function () use ($updateQuestionDTO) {

            $this->updateQuestion($updateQuestionDTO);

            if (!is_null($updateQuestionDTO->choices)) {
                $this->updateQuestionChoices($updateQuestionDTO);
            }

            if (!is_null($updateQuestionDTO->choices)) {
                return $this->questionRepository
                    ->loadRelation(new Relationship(name: "choices"))
                    ->findById($updateQuestionDTO->question_id);
            }

            return $this->questionRepository
                ->findById($updateQuestionDTO->question_id);
        });
    }

    private function updateQuestionChoices(UpdateQuestionDTO $updateQuestionDTO)
    {
        $questionResponsesCount = $this->questionResponseRepository
            ->countByQuestionId($updateQuestionDTO->question_id);

        $choicesWithId = $updateQuestionDTO->choices->filter(function ($choice) {
            return isset($choice->id);
        });
        $choicesIds = $choicesWithId->map(function ($choice) {
            return $choice->id;
        });

        if ($questionResponsesCount > 0) {
            $savedQuestionChoices = $this->questionChoiceRepository->findWhere([
                "question_id" => $updateQuestionDTO->question_id
            ]);
            $savedChoicesIds = $savedQuestionChoices->map(function ($choice) {
                return $choice->id;
            });

            if (count($savedChoicesIds) !== count($choicesIds) || $choicesIds !== $savedChoicesIds) {
                throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
            }
        } else if ($questionResponsesCount === 0) {
            $providedChoices = $this->questionChoiceRepository->findWhere([
                function (Builder $query) use ($choicesIds) {
                    $query->whereIn("id", $choicesIds);
                },
                ["question_id", "=", $updateQuestionDTO->question_id]
            ]);

            if (count($choicesIds) !== count($providedChoices)) {
                throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
            }

            $this->questionChoiceRepository->forceDeleteWhere([
                ["question_id", "=", $updateQuestionDTO->question_id],
                function (Builder $query) use ($choicesIds) {
                    $query->whereNotIn("id", $choicesIds);
                },
            ]);
        }

        $choicesData = $updateQuestionDTO->choices->map(function ($choice) use ($updateQuestionDTO) {
            $choiceData = [
                "description" => $choice->description,
                "description_image" => $choice->description_image,
                "display_number" => $choice->position,
                "question_id" => $updateQuestionDTO->question_id
            ];
            if (isset($choice->id)) {
                $choiceData["id"] = $choice->id;
            }

            return $choiceData;
        })->toArray();

        $this->questionChoiceRepository->upsert(
            $choicesData,
            ["id"],
            ['description', 'description_image', 'display_number', "question_id"]
        );
    }

    private function updateQuestion(UpdateQuestionDTO $updateQuestionDTO)
    {
        return $this->questionRepository->updateById(
            $updateQuestionDTO->question_id,
            [
                "description" => $updateQuestionDTO->description,
                "description_image" => $updateQuestionDTO->description_image,
                "required" => $updateQuestionDTO->required,
                "randomize" => $updateQuestionDTO->randomize
            ]
        );
    }
}
