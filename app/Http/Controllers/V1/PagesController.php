<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\Survey;
use App\Models\SurveyPage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends Controller
{
    public function destroy(string $page_id)
    {
        $surveyPage = SurveyPage::find($page_id);

        if (!$surveyPage) {
            throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $pages_count = Survey::lockForUpdate()
                ->findOrFail($surveyPage->survey_id)
                ->pages()
                ->count();
            if ($pages_count === 1) {
                throw new BadRequestException("Survey mus have at least 1 page", Response::HTTP_BAD_REQUEST);
            }

            $targetPage = SurveyPage::withCount("questions")
                ->lockForUpdate()
                ->find($page_id);
            if (!$targetPage) {
                throw new ResourceNotFoundException("Survey resource not found", Response::HTTP_NOT_FOUND);
            }

            if ($targetPage->questions_count !== 0) {
                $targetPageLastQuestion = Question::where("survey_page_id", $targetPage->id)
                    ->lockForUpdate()
                    ->orderByDesc("display_number")
                    ->first();
                //delete question choices
                QuestionChoice::whereHas('question', function ($query) use ($targetPage) {
                    $query->where('survey_page_id', $targetPage->id);
                })->delete();

                //delete questions
                Question::where("survey_page_id", $targetPage->id)
                    ->delete();

                //update other questions positions
                Question::whereHas('surveyPage', function ($query) use ($targetPage) {
                    $query->where('survey_id', $targetPage->survey_id);
                })
                    ->where('display_number', '>', $targetPageLastQuestion->display_number)
                    ->decrement('display_number', $targetPage->questions_count);
            }

            SurveyPage::where("survey_id", $targetPage->survey_id)
                ->where("display_number", ">", $targetPage->display_number)
                ->decrement("display_number");

            SurveyPage::where("id", $targetPage->id)->delete();

            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            throw $err;
        }

        return response()
            ->json([
                "message" => "Page has been successfully removed"
            ]);
    }
}
