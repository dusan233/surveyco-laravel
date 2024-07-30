<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_response_answers', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->text("text_answer")->nullable();
            $table->uuid("question_choice_id")->nullable();
            $table->foreign("question_choice_id")->references("id")->on("question_choices");
            $table->uuid("question_id");
            $table->foreign("question_id")->references("id")->on("questions");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_response_answers');
    }
};
