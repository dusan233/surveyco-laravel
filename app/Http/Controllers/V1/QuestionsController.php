<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\CopyQuestionRequest;
use App\Http\Requests\V1\MoveQuestionRequest;
use App\Http\Requests\V1\ReplaceQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\Question;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Services\Handlers\Question\CopyQuestionHandler;
use App\Services\Handlers\Question\DeleteQuestionHandler;
use App\Services\Handlers\Question\DTO\CopyQuestionDTO;
use App\Services\Handlers\Question\DTO\MoveQuestionDTO;
use App\Services\Handlers\Question\DTO\UpdateQuestionChoiceDTO;
use App\Services\Handlers\Question\DTO\UpdateQuestionDTO;
use App\Services\Handlers\Question\MoveQuestionHandler;
use App\Services\Handlers\Question\UpdateQuestionHandler;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class QuestionsController extends BaseController
{
    private QuestionRepositoryInterface $questionRepository;
    private UpdateQuestionHandler $updateQuestionHandler;
    private DeleteQuestionHandler $deleteQuestionHandler;
    private CopyQuestionHandler $copyQuestionHandler;
    private MoveQuestionHandler $moveQuestionHandler;

    public function __construct(
        QuestionRepositoryInterface $questionRepository,
        UpdateQuestionHandler $updateQuestionHandler,
        DeleteQuestionHandler $deleteQuestionHandler,
        CopyQuestionHandler $copyQuestionHandler,
        MoveQuestionHandler $moveQuestionHandler
    ) {
        $this->questionRepository = $questionRepository;
        $this->updateQuestionHandler = $updateQuestionHandler;
        $this->deleteQuestionHandler = $deleteQuestionHandler;
        $this->copyQuestionHandler = $copyQuestionHandler;
        $this->moveQuestionHandler = $moveQuestionHandler;
    }
    public function update(ReplaceQuestionRequest $request, string $survey_id, string $page_id, string $question_id)
    {
        $question = $this->questionRepository->findFirstWhere(
            [
                ["id", "=", $question_id],
                ["survey_page_id", "=", $page_id],
                function (Builder $query) use ($survey_id) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($survey_id) {
                        $query->where("survey_id", "=", $survey_id);
                    });
                }
            ]
        );

        if (!$question) {
            $this->notFoundResponse();
        }

        if ($request->user()->cannot("update", [Question::class, $question])) {
            throw new UnauthorizedException();
        }

        $questionData = $request->validated();

        if ($question->type !== $questionData["type"]) {
            throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
        }

        $updatedQuestion = $this->updateQuestionHandler->handle(new UpdateQuestionDTO(
            $question_id,
            $questionData["description"],
            $questionData["required"],
            $questionData["randomize"],
            $questionData["descriptionImage"],
            isset($questionData["choices"]) ? collect(array_map(function ($choice) {
                return new UpdateQuestionChoiceDTO(
                    isset($choice["id"]) ? $choice["id"] : null,
                    $choice["description"],
                    $choice["displayNumber"],
                    $choice["descriptionImage"],
                );
            }, $questionData["choices"]))
            : null
        ));

        return $this->resourceResponse(QuestionResource::class, $updatedQuestion);
    }


    public function destroy(Request $request, string $survey_id, string $page_id, string $question_id)
    {
        $question = $this->questionRepository->findFirstWhere(
            [
                ["id", "=", $question_id],
                ["survey_page_id", "=", $page_id],
                function (Builder $query) use ($survey_id) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($survey_id) {
                        $query->where("survey_id", "=", $survey_id);
                    });
                }
            ]
        );

        if (!$question) {
            $this->notFoundResponse();
        }

        if ($request->user()->cannot("delete", [Question::class, $question])) {
            throw new UnauthorizedException();
        }

        $surveyId = $question->surveyPage->survey_id;
        $this->deleteQuestionHandler->handle($question->id, $surveyId);

        return $this->deletedResponse();
    }


    public function copy(CopyQuestionRequest $request, string $survey_id, string $page_id, string $source_question_id)
    {
        $question = $this->questionRepository->findFirstWhere(
            [
                ["id", "=", $source_question_id],
                ["survey_page_id", "=", $page_id],
                function (Builder $query) use ($survey_id) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($survey_id) {
                        $query->where("survey_id", "=", $survey_id);
                    });
                }
            ]
        );

        if (!$question) {
            $this->notFoundResponse();
        }

        if ($request->user()->cannot("copy", [Question::class, $question])) {
            throw new UnauthorizedException();
        }

        $surveyId = $question->surveyPage->survey_id;
        $copyQuestionData = $request->validated();

        $newQuestion = $this->copyQuestionHandler->handle(new CopyQuestionDTO(
            $surveyId,
            $source_question_id,
            $copyQuestionData["targetPageId"],
            $copyQuestionData["position"] ?? null,
            $copyQuestionData["targetQuestionId"] ?? null
        ));

        return $this->resourceResponse(QuestionResource::class, $newQuestion, Response::HTTP_CREATED);
    }

    public function move(MoveQuestionRequest $request, string $survey_id, string $page_id, string $source_question_id)
    {
        $question = $this->questionRepository->findFirstWhere(
            [
                ["id", "=", $source_question_id],
                ["survey_page_id", "=", $page_id],
                function (Builder $query) use ($survey_id) {
                    $query->whereHas("surveyPage", function (Builder $query) use ($survey_id) {
                        $query->where("survey_id", "=", $survey_id);
                    });
                }
            ]
        );

        if (!$question) {
            $this->notFoundResponse();
        }

        if ($request->user()->cannot("move", [Question::class, $question])) {
            throw new UnauthorizedException();
        }

        $surveyId = $question->surveyPage->survey_id;
        $moveQuestionData = $request->validated();

        $updatedQuestion = $this->moveQuestionHandler->handle(new MoveQuestionDTO(
            $surveyId,
            $source_question_id,
            $moveQuestionData["targetPageId"],
            $moveQuestionData["position"] ?? null,
            $moveQuestionData["targetQuestionId"] ?? null
        ));

        return $this->resourceResponse(QuestionResource::class, $updatedQuestion);
    }
}
