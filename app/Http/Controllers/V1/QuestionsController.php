<?php

namespace App\Http\Controllers\V1;

use App\Enums\PlacementPositionEnum;
use App\Enums\QuestionTypeEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CopyQuestionRequest;
use App\Http\Requests\V1\MoveQuestionRequest;
use App\Http\Requests\V1\ReplaceQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\SurveyPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class QuestionsController extends Controller
{
    public function update(ReplaceQuestionRequest $request, string $question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("update", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $questionData = $request->validated();

        // trying to change question type which is not supported right now
        if ($question->type !== $questionData["type"]) {
            throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            Question::where("id", $question_id)
                ->update([
                    "description" => $questionData["description"],
                    "description_image" => $questionData["descriptionImage"],
                    "type" => $questionData["type"],
                    "required" => $questionData["required"],
                    "randomize" => $questionData["type"] !== QuestionTypeEnum::TEXTBOX->value
                        ? $questionData["randomize"]
                        : null,
                ]);

            if (isset($questionData["choices"])) {
                $choices = $questionData["choices"];
                //if question has responses u have to provide all choices that are already saved.
                $choicesWithId = array_filter($choices, function ($choice) {
                    return isset($choice["id"]);
                });
                $choicesIds = array_map(function ($choice) {
                    return $choice["id"];
                }, $choicesWithId);

                $providedChoices = QuestionChoice::whereIn("id", $choicesIds)
                    ->where("question_id", $question_id)
                    ->lockForUpdate()
                    ->get();

                if (count($choicesIds) !== count($providedChoices)) {
                    throw new BadRequestException("Invalid data", Response::HTTP_BAD_REQUEST);
                }
                // remove choices that are not included - maybe we can still not run this if question has responses.
                QuestionChoice::where("question_id", $question_id)
                    ->whereNotIn("id", $choicesIds)
                    ->forceDelete();

                //update or create choicess
                QuestionChoice::upsert(
                    array_map(function ($choice) use ($question_id) {
                        $choiceData = [
                            "description" => $choice["description"],
                            "description_image" => $choice["descriptionImage"],
                            "display_number" => $choice["displayNumber"],
                            "question_id" => $question_id
                        ];
                        if (isset($choice["id"])) {
                            $choiceData["id"] = $choice["id"];
                        }

                        return $choiceData;
                    }, $choices),
                    ["id"],
                    ['description', 'description_image', 'display_number', "question_id"]
                );
            }

            $updatedQuestion = Question::find($question_id);
            if (isset($questionData["choices"])) {
                $updatedQuestion->load("choices");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return new QuestionResource($updatedQuestion);
    }


    public function destroy(Request $request, string $question_id)
    {
        $question = Question::find($question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("delete", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            DB::beginTransaction();

            $question = Question::lockForUpdate()->find($question_id);
            $surveyId = $question->surveyPage->survey_id;

            //delete choices softly
            if ($question->type !== QuestionTypeEnum::TEXTBOX->value) {
                QuestionChoice::where("question_id", $question_id)->delete();
            }

            //delete question softly
            $question->delete();

            //update position for questions after deleted one
            Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                $query->where('survey_id', $surveyId);
            })
                ->where('display_number', '>', $question->display_number)
                ->decrement('display_number');

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()
            ->json([
                "message" => "Question has been successfully removed"
            ]);
    }


    public function copy(CopyQuestionRequest $request, string $source_question_id)
    {
        $question = Question::find($source_question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("copy", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $surveyId = $question->surveyPage->survey_id;
        $copyQuestionData = $request->validated();
        $newQuestionPosition = 0;

        try {
            DB::beginTransaction();

            $targetPage = SurveyPage::withCount("questions")
                ->lockForUpdate()
                ->find($copyQuestionData["targetPageId"]);

            if (!$targetPage || $targetPage->survey_id !== $surveyId) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            $sourceQuestion = Question::lockForUpdate()->find($source_question_id);

            if (!$sourceQuestion) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            if ($targetPage->questions_count === 50) {
                throw new BadRequestException("Max Questions per page exceeded", Response::HTTP_BAD_REQUEST);
            }

            if ($targetPage->questions_count !== 0) {
                if ($copyQuestionData["targetQuestionId"]) {
                    $targetQuestion = Question::lockForUpdate()->find($copyQuestionData["targetQuestionId"]);

                    if (!$targetQuestion || $targetQuestion->surveyPage->id !== $targetPage->id) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    $newQuestionPosition = $copyQuestionData["position"] === PlacementPositionEnum::AFTER->value
                        ? $targetQuestion->display_number + 1
                        : $targetQuestion->display_number;
                } else {
                    $targetPageLastQuestion = Question::where("survey_page_id", $targetPage->id)
                        ->lockForUpdate()
                        ->orderByDesc("display_number")
                        ->first();

                    if (!$targetPageLastQuestion) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    $newQuestionPosition = $targetPageLastQuestion->display_number + 1;
                }
            } else {
                $previousPageWithQuestions = SurveyPage::where('display_number', '<', $targetPage->display_number)
                    ->where("survey_id", $surveyId)
                    ->whereHas('questions')
                    ->lockForUpdate()
                    ->orderBy('display_number', 'desc')
                    ->first();

                if ($previousPageWithQuestions) {
                    $previousPageWithQuestionsLastQuestion = Question::where("survey_page_id", $previousPageWithQuestions->id)
                        ->lockForUpdate()
                        ->orderByDesc("display_number")
                        ->first();

                    if (!$previousPageWithQuestionsLastQuestion) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number + 1;
                } else {
                    $newQuestionPosition = 1;
                }
            }

            Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                $query->where('survey_id', $surveyId);
            })
                ->where('display_number', '>=', $newQuestionPosition)
                ->increment('display_number');


            $newQuestion = $sourceQuestion->replicate()->fill([
                "display_number" => $newQuestionPosition,
                "survey_page_id" => $targetPage->id
            ]);
            $newQuestion->save();

            if ($sourceQuestion->type !== QuestionTypeEnum::TEXTBOX->value) {
                $newQuestion->choices()->saveMany($sourceQuestion->choices()->get()->map(function ($choice) {
                    return new QuestionChoice([
                        "description" => $choice->description,
                        "description_image" => $choice->description_image,
                        "display_number" => $choice->display_number,
                    ]);
                }));

                $newQuestion->load("choices");
            }

            $newQuestion->refresh();

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return new QuestionResource($newQuestion);
    }

    public function move(MoveQuestionRequest $request, string $source_question_id)
    {
        $question = Question::find($source_question_id);

        if (!$question) {
            throw new ResourceNotFoundException("Question resource not found", Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot("move", [Question::class, $question])) {
            throw new UnauthorizedException(
                "This action is unauthorized",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $surveyId = $question->surveyPage->survey_id;
        $moveQuestionData = $request->validated();
        $newQuestionPosition = 0;

        try {
            DB::beginTransaction();

            $targetPage = SurveyPage::withCount("questions")
                ->lockForUpdate()
                ->find($moveQuestionData["targetPageId"]);

            if (!$targetPage || $targetPage->survey_id !== $surveyId) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            $sourceQuestion = Question::lockForUpdate()->find($source_question_id);

            if (!$sourceQuestion) {
                throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
            }

            if ($targetPage->questions_count === 50) {
                throw new BadRequestException("Max Questions per page exceeded", Response::HTTP_BAD_REQUEST);
            }

            if ($targetPage->questions_count !== 0) {
                if ($moveQuestionData["targetQuestionId"]) {
                    $targetQuestion = Question::lockForUpdate()->find($moveQuestionData["targetQuestionId"]);

                    if (!$targetQuestion || $targetQuestion->surveyPage->id !== $targetPage->id) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    if ($targetQuestion->display_number > $sourceQuestion->display_number) {
                        $newQuestionPosition = $moveQuestionData["position"] === PlacementPositionEnum::AFTER->value
                            ? $targetQuestion->display_number
                            : $targetQuestion->display_number - 1;

                        Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                            $query->where('survey_id', $surveyId);
                        })
                            ->where('display_number', '>', $sourceQuestion->display_number)
                            ->where("display_number", "<=", $newQuestionPosition)
                            ->decrement('display_number');
                    } else {
                        $newQuestionPosition = $moveQuestionData["position"] === PlacementPositionEnum::AFTER->value
                            ? $targetQuestion->display_number + 1
                            : $targetQuestion->display_number;

                        Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                            $query->where('survey_id', $surveyId);
                        })
                            ->where('display_number', '>=', $newQuestionPosition)
                            ->where("display_number", "<", $sourceQuestion->display_number)
                            ->increment('display_number');
                    }
                } else {
                    $targetPageLastQuestion = Question::where("survey_page_id", $targetPage->id)
                        ->lockForUpdate()
                        ->orderByDesc("display_number")
                        ->first();

                    if (!$targetPageLastQuestion) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    if ($targetPageLastQuestion->display_number > $sourceQuestion->display_number) {
                        $newQuestionPosition = $moveQuestionData["position"] === PlacementPositionEnum::AFTER->value
                            ? $targetPageLastQuestion->display_number
                            : $targetPageLastQuestion->display_number - 1;

                        Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                            $query->where('survey_id', $surveyId);
                        })
                            ->where('display_number', '>', $sourceQuestion->display_number)
                            ->where("display_number", "<=", $newQuestionPosition)
                            ->decrement('display_number');
                    } else {
                        $newQuestionPosition = $moveQuestionData["position"] === PlacementPositionEnum::AFTER->value
                            ? $targetPageLastQuestion->display_number + 1
                            : $targetPageLastQuestion->display_number;

                        Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                            $query->where('survey_id', $surveyId);
                        })
                            ->where('display_number', '>=', $newQuestionPosition)
                            ->where("display_number", "<", $sourceQuestion->display_number)
                            ->increment('display_number');
                    }
                }
            } else {
                $previousPageWithQuestions = SurveyPage::where('display_number', '<', $targetPage->display_number)
                    ->where("survey_id", $surveyId)
                    ->whereHas('questions')
                    ->lockForUpdate()
                    ->orderBy('display_number', 'desc')
                    ->first();

                if ($previousPageWithQuestions) {
                    $previousPageWithQuestionsLastQuestion = Question::where("survey_page_id", $previousPageWithQuestions->id)
                        ->lockForUpdate()
                        ->orderByDesc("display_number")
                        ->first();

                    if (!$previousPageWithQuestionsLastQuestion) {
                        throw new BadRequestException("Invalid data provided", Response::HTTP_BAD_REQUEST);
                    }

                    if ($sourceQuestion->display_number > $previousPageWithQuestionsLastQuestion->display_number) {
                        $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number + 1;

                        Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                            $query->where('survey_id', $surveyId);
                        })
                            ->where('display_number', '>=', $newQuestionPosition)
                            ->where("display_number", "<", $sourceQuestion->display_number)
                            ->increment('display_number');
                    } else {
                        if ($sourceQuestion->id === $previousPageWithQuestionsLastQuestion->id) {
                            $newQuestionPosition = 1;
                        } else {
                            $newQuestionPosition = $previousPageWithQuestionsLastQuestion->display_number;

                            Question::whereHas('surveyPage', function ($query) use ($surveyId) {
                                $query->where('survey_id', $surveyId);
                            })
                                ->where('display_number', '>', $sourceQuestion->display_number)
                                ->where("display_number", "<=", $newQuestionPosition)
                                ->decrement('display_number');
                        }
                    }
                } else {
                    $newQuestionPosition = 1;
                }
            }

            $sourceQuestion->display_number = $newQuestionPosition;
            $sourceQuestion->surveyPage()->associate($targetPage);

            $sourceQuestion->save();
            $sourceQuestion->refresh();
            if ($sourceQuestion->type !== QuestionTypeEnum::TEXTBOX->value) {
                $sourceQuestion->load("choices");
            }

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return new QuestionResource($sourceQuestion);
    }
}
