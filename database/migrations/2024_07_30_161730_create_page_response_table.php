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
        Schema::create('page_response', function (Blueprint $table) {
            $table->primary(["survey_page_id", "survey_response_id"]);
            $table->uuid("survey_page_id");
            $table->foreign("survey_page_id")->references("id")->on("survey_pages");
            $table->uuid("survey_response_id");
            $table->foreign("survey_response_id")->references("id")->on("survey_responses");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_response');
    }
};
