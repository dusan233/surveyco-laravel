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
        Schema::create('survey_pages', function (Blueprint $table) {
            $table->uuid("id");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("survey_id")->references("id")->on("surveys");
            $table->integer("display_number");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_pages');
    }
};
