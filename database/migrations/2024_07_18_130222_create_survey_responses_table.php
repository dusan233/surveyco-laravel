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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->enum("status", ["incomplete", "complete"])->default("incomplete");
            $table->integer("display_number");
            $table->ipAddress("ip_address");
            //if we use many through or something this is not needed
            // $table->foreign("survey_id")->references("id")->on("surveys");
            $table->uuid("survey_collector_id");
            $table->foreign("survey_collector_id")->references("id")->on("survey_collectors");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
