<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $survey = Survey::create([
            "title" => "Survey 111",
            "category" => "market_research",
            "author_id" => "user_2k624iEdoUcRVIhU0QHIw0cWE6J"
        ]);

        $page1 = SurveyPage::create(["display_number" => 1, "survey_id" => $survey->id]);
        $page2 = SurveyPage::create(["display_number" => 2, "survey_id" => $survey->id]);

        $page2->questions()->createMany([
            [
                "description" => "Textbox",
                "description_image" => null,
                "required" => false,
                "type" => "textbox",
                "display_number" => 3,
                "survey_page_id" => $page2->id
            ],
            [
                "description" => "Textbox",
                "description_image" => null,
                "required" => false,
                "type" => "textbox",
                "display_number" => 4,
                "survey_page_id" => $page2->id
            ]
        ]);

        $question1 = Question::create([
            "description" => "Textbox",
            "description_image" => null,
            "required" => false,
            "type" => "textbox",
            "display_number" => 1,
            "survey_page_id" => $page1->id
        ]);

        $question2 = Question::create([
            "description" => "Checkbox",
            "description_image" => null,
            "required" => false,
            "type" => "checkbox",
            "randomize" => false,
            "display_number" => 2,
            "survey_page_id" => $page1->id
        ]);

        $question2->choices()->createMany([
            [
                "description" => "Choice 1",
                "description_image" => null,
                "display_number" => 1,
                "question_id" => $question2->id
            ],
            [
                "description" => "Choice 2",
                "description_image" => null,
                "display_number" => 2,
                "question_id" => $question2->id
            ],
            [
                "description" => "Choice 3",
                "description_image" => null,
                "display_number" => 3,
                "question_id" => $question2->id
            ]
        ]);

        $survey->collectors()->create(["type" => "web_link", "survey_id" => $survey->id, "name" => "Collector 1"]);
    }
}
