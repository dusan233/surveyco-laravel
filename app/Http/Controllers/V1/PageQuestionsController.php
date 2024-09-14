<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StorePageQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\Question;
use App\Repositories\Eloquent\Value\Relationship;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\Question\CreateQuestionHandler;
use App\Services\Handlers\Question\DTO\CreateQuestionChoiceDTO;
use App\Services\Handlers\Question\DTO\CreateQuestionDTO;
use Symfony\Component\HttpFoundation\Response;

class PageQuestionsController extends BaseController
{
    private QuestionRepositoryInterface $questionRepository;
    private SurveyPageRepositoryInterface $surveyPageRepository;
    private CreateQuestionHandler $createQuestionHandler;

    public function __construct(
        QuestionRepositoryInterface $questionRepository,
        SurveyPageRepositoryInterface $surveyPageRepository,
        CreateQuestionHandler $createQuestionHandler,
    ) {
        $this->questionRepository = $questionRepository;
        $this->surveyPageRepository = $surveyPageRepository;
        $this->createQuestionHandler = $createQuestionHandler;
    }
    public function index(string $survey_id, string $page_id)
    {
        $page = $this->surveyPageRepository->findFirstWhere([
            "id" => $page_id,
            "survey_id" => $survey_id,
        ]);

        if (!$page) {
            return $this->notFoundResponse();
        }

        $questions = $this->questionRepository
            ->loadRelation(new Relationship(name: "choices"))
            ->findByPageId($page->id);

        return $this->resourceResponse(QuestionResource::class, $questions);
    }


    public function store(StorePageQuestionRequest $request, string $survey_id, string $page_id)
    {
        $page = $this->surveyPageRepository->findFirstWhere([
            "id" => $page_id,
            "survey_id" => $survey_id,
        ]);

        if (!$page) {
            return $this->notFoundResponse();
        }

        if ($request->user()->cannot("create", [Question::class, $page])) {
            throw new UnauthorizedException();
        }

        $questionData = $request->validated();

        $newQuestion = $this->createQuestionHandler->handle(new CreateQuestionDTO(
            $page->survey_id,
            $page_id,
            $questionData["description"],
            $questionData["required"],
            $questionData["type"],
            $questionData["randomize"],
            $questionData["descriptionImage"],
            isset($questionData["choices"]) ? collect(array_map(function ($choice) {
                return new CreateQuestionChoiceDTO(
                    $choice["description"],
                    $choice["displayNumber"],
                    $choice["descriptionImage"],
                );
            }, $questionData["choices"]))
            : null
        ));

        return $this->resourceResponse(QuestionResource::class, $newQuestion, Response::HTTP_CREATED);
    }
}
