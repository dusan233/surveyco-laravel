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
        Schema::create('survey_collectors', function (Blueprint $table) {
            $table->uuid("id");
            $table->timestamps();
            $table->softDeletes();
            $table->string("type");
            $table->enum("status", ["open", "closed"])->default("open");
            $table->string("name");
            $table->foreign("survey_id")->references("id")->on("surveys");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_collectors');
    }
};
