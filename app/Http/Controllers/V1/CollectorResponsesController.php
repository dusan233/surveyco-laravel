<?php

namespace App\Http\Controllers\V1;

use App\Enums\AnswerTypeEnum;
use App\Enums\CollectorStatusEnum;
use App\Enums\QuestionTypeEnum;
use App\Enums\ResponseStatusEnum;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCollectorResponseRequest;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\Survey;
use App\Models\SurveyCollector;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class CollectorResponsesController extends Controller
{
    public function store(StoreCollectorResponseRequest $request, string $collector_id)
    {
        $collector = SurveyCollector::find($collector_id);

        if (!$collector) {
            throw new ResourceNotFoundException("Collector resource not found", Response::HTTP_NOT_FOUND);
        }
        if ($collector->status === CollectorStatusEnum::CLOSED->value) {
            throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
        }

        $responseData = $request->validated();
        $pageId = $responseData["pageId"];
        $surveyId = $collector->survey_id;

        try {
            DB::beginTransaction();

            $responsePage = SurveyPage::lockForUpdate()->find($responseData["pageId"]);

            if (!$responsePage) {
                throw new ResourceNotFoundException("Page resource not found", Response::HTTP_NOT_FOUND);
            }

            if ($responsePage->survey_id !== $collector->survey_id) {
                throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
            }

            $providedStartTime = Carbon::parse($responseData["startTime"]);
            $surveyUpdated = Survey::lockForUpdate()->find($surveyId);

            if ($surveyUpdated->updated_at > $providedStartTime) {
                throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
            }

            $surveyLastPage = SurveyPage::where("survey_id", $surveyId)
                ->orderByDesc("display_number")
                ->lockForUpdate()
                ->first();
            $responseSubmitted = $surveyLastPage->id === $pageId ? true : false;

            if (isset($responseData["isPreview"]) && $responseData["isPreview"]) {
                return response()->json([
                    "submitted" => $responseSubmitted
                ]);
            }

            //response doesnt exist
            if (!isset($responseData["responseId"])) {
                if ($responsePage->display_number !== 1) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                //validate data
                $pageQuestionCount = Question::where("survey_page_id", $pageId)
                    ->count();

                if ($pageQuestionCount !== count($responseData["answers"])) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                $responseQuestionIds = array_map(function ($answer) {
                    return $answer["question"]["id"];
                }, $responseData["answers"]);

                $questions = Question::where("survey_page_id", $pageId)
                    ->whereIn("id", $responseQuestionIds)
                    ->lockForUpdate()
                    ->get();

                if (count($responseData["answers"]) !== count($questions)) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                $ipAddress = array_slice($request->ips(), -1)[0];
                $responseDisplayNumber = SurveyResponse::whereHas("surveyCollector", function ($query) use ($surveyId) {
                    $query->where('survey_id', $surveyId);
                })->count() + 1;
                $surveyResponse = SurveyResponse::create([
                    "display_number" => $responseDisplayNumber,
                    "survey_collector_id" => $collector_id,
                    "ip_address" => $ipAddress
                ]);
                $surveyResponse->pages()->attach($responsePage);

                foreach ($responseData["answers"] as $qResponseData) {
                    $questionResponse = QuestionResponse::create([
                        "question_id" => $qResponseData["question"]["id"],
                        "survey_response_id" => $surveyResponse->id,
                    ]);

                    $question = $questions->firstWhere("id", $qResponseData["question"]["id"]);

                    if ($question->required) {
                        if ($question->type === QuestionTypeEnum::TEXTBOX->value) {
                            if (empty($qResponseData["text"])) {
                                throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                            }
                        } else {
                            if (empty($qResponseData["choices"])) {
                                throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                            }
                            if (
                                ($qResponseData["question"]["type"] === QuestionTypeEnum::DROPDOWN->value
                                    || $qResponseData["question"]["type"] === QuestionTypeEnum::SINGLE_CHOICE->value)
                                && count($qResponseData["choices"]) > 1
                            ) {
                                throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                            }
                        }
                    }

                    if ($qResponseData["type"] === AnswerTypeEnum::TEXT->value) {
                        $answersArray = [
                            [
                                "question_response_id" => $questionResponse->id,
                                "question_id" => $qResponseData["question"]["id"],
                                "question_choice_id" => null,
                                "text_answer" => $qResponseData["text"]
                            ]
                        ];
                    } else if ($qResponseData["type"] === AnswerTypeEnum::CHOICES->value) {
                        $answersArray = array_map(function ($choice) use ($questionResponse, $qResponseData) {
                            return [
                                "question_response_id" => $questionResponse->id,
                                "question_id" => $qResponseData["question"]["id"],
                                "question_choice_id" => $choice,
                                "text_answer" => null
                            ];
                        }, $qResponseData["choices"]);
                    }

                    $questionResponse->answers()->createMany($answersArray);
                }
            } else {
                $responseId = $responseData["responseId"];
                $surveyResponse = SurveyResponse::find($responseId);

                if (!$surveyResponse || $surveyResponse->survey_collector_id !== $collector_id) {
                    throw new ResourceNotFoundException("Response resource not found", Response::HTTP_NOT_FOUND);
                }

                if ($surveyResponse->status === ResponseStatusEnum::COMPLETE->value) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                //validate data
                $pageQuestionCount = Question::where("survey_page_id", $pageId)
                    ->count();

                if ($pageQuestionCount !== count($responseData["answers"])) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                $responseQuestionIds = array_map(function ($answer) {
                    return $answer["question"]["id"];
                }, $responseData["answers"]);

                $questions = Question::where("survey_page_id", $pageId)
                    ->whereIn("id", $responseQuestionIds)
                    ->lockForUpdate()
                    ->get();

                if (count($responseData["answers"]) !== count($questions)) {
                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                }

                $questionResponses = QuestionResponse::whereHas("question", function ($query) use ($responsePage) {
                    $query->where('survey_page_id', $responsePage->id);
                })
                    ->where("survey_response_id", $responseId)
                    ->with("questionResponseAnswers")
                    ->lockForUpdate()
                    ->get();

                $currentPageResponse = $surveyResponse->pages()->where("id", $pageId)->first();

                if (!$currentPageResponse) {
                    foreach ($responseData["answers"] as $qResponseData) {
                        $questionResponse = QuestionResponse::create([
                            "question_id" => $qResponseData["question"]["id"],
                            "survey_response_id" => $surveyResponse->id,
                        ]);

                        $question = $questions->firstWhere("id", $qResponseData["question"]["id"]);

                        if ($question->required) {
                            if ($question->type === QuestionTypeEnum::TEXTBOX->value) {
                                if (empty($qResponseData["text"])) {
                                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                                }
                            } else {
                                if (empty($qResponseData["choices"])) {
                                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                                }
                            }
                        }

                        if (
                            ($qResponseData["question"]["type"] === QuestionTypeEnum::DROPDOWN->value
                                || $qResponseData["question"]["type"] === QuestionTypeEnum::SINGLE_CHOICE->value)
                            && count($qResponseData["choices"]) > 1
                        ) {
                            throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                        }

                        if ($qResponseData["type"] === AnswerTypeEnum::TEXT->value) {
                            $answersArray = [
                                [
                                    "question_response_id" => $questionResponse->id,
                                    "question_id" => $qResponseData["question"]["id"],
                                    "question_choice_id" => null,
                                    "text_answer" => $qResponseData["text"]
                                ]
                            ];
                        } else if ($qResponseData["type"] === AnswerTypeEnum::CHOICES->value) {
                            $answersArray = array_map(function ($choice) use ($questionResponse, $qResponseData) {
                                return [
                                    "question_response_id" => $questionResponse->id,
                                    "question_id" => $qResponseData["question"]["id"],
                                    "question_choice_id" => $choice,
                                    "text_answer" => null
                                ];
                            }, $qResponseData["choices"]);
                        }

                        $questionResponse->questionResponseAnswers()->createMany($answersArray);
                        $surveyResponse->pages()->attach($responsePage);
                    }
                } else {
                    foreach ($responseData["answers"] as $qResponseData) {
                        $questionResponse = isset($qResponseData["id"])
                            ? $questionResponses->firstWhere("id", "=", $qResponseData["id"])
                            : QuestionResponse::create([
                                "question_id" => $qResponseData["question"]["id"],
                                "survey_response_id" => $surveyResponse->id,
                            ]);

                        $question = $questions->firstWhere("id", $qResponseData["question"]["id"]);

                        if ($question->required) {
                            if ($question->type === QuestionTypeEnum::TEXTBOX->value) {
                                if (empty($qResponseData["text"])) {
                                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                                }
                            } else {
                                if (empty($qResponseData["choices"])) {
                                    throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                                }
                            }
                        }

                        if (
                            ($qResponseData["question"]["type"] === QuestionTypeEnum::DROPDOWN->value
                                || $qResponseData["question"]["type"] === QuestionTypeEnum::SINGLE_CHOICE->value)
                            && count($qResponseData["choices"]) > 1
                        ) {
                            throw new BadRequestException("Invalid request data provided", Response::HTTP_BAD_REQUEST);
                        }

                        if ($qResponseData["type"] === AnswerTypeEnum::TEXT->value) {
                            $answersArray = [
                                [
                                    "question_response_id" => $questionResponse->id,
                                    "question_id" => $qResponseData["question"]["id"],
                                    "question_choice_id" => null,
                                    "text_answer" => $qResponseData["text"]
                                ]
                            ];
                        } else if ($qResponseData["type"] === AnswerTypeEnum::CHOICES->value) {
                            $answersArray = array_map(function ($choice) use ($questionResponse, $qResponseData) {
                                return [
                                    "question_response_id" => $questionResponse->id,
                                    "question_id" => $qResponseData["question"]["id"],
                                    "question_choice_id" => $choice,
                                    "text_answer" => null
                                ];
                            }, $qResponseData["choices"]);
                        }

                        $questionResponse->questionResponseAnswers()->delete();
                        $questionResponse->questionResponseAnswers()->createMany($answersArray);
                    }
                }

            }



            if ($responseSubmitted) {
                $surveyResponse->update([
                    "status" => ResponseStatusEnum::COMPLETE->value
                ]);
            }
            //check for survey updated.

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()->json([
            "submitted" => $responseSubmitted,
            "responseId" => $surveyResponse->id,
            "pageId" => $pageId
        ]);
    }
}
