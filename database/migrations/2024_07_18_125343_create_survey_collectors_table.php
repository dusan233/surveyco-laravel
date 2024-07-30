<?php

use App\Enums\CollectorStatusEnum;
use App\Enums\CollectorTypeEnum;
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
            $table->uuid("id")->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->enum("type", array_column(CollectorTypeEnum::cases(), 'value'));
            $table->enum("status", array_column(CollectorStatusEnum::cases(), 'value'))->default(CollectorStatusEnum::OPEN->value);
            $table->string("name");
            $table->uuid("survey_id");
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
