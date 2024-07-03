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
        Schema::create('users', function (Blueprint $table) {
            $table->string("id")->unique();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('email_verification_status', ["verified", "unverified"]);
            $table->string('image_url')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz('deleted_at', precision: 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
