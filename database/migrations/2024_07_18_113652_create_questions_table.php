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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid("id");
            $table->timestamps();
            $table->softDeletes();
            $table->text("description");
            $table->string("description_image")->nullable();
            $table->string("type");
            $table->integer("display_number");
            $table->foreign("survey_id")->references("id")->on("surveys");
            $table->foreign("survey_page_id")->references("id")->on("survey_pages");
            $table->boolean("required");
            $table->boolean("randomize")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
