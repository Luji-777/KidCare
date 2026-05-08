<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->string('email');
            $table->string('phone_number');
            $table->string('password')->nullable();
            $table->integer('experience_years');
            $table->string('education');
            $table->string('profile_picture')->nullable();
            $table->string('rating')->nullable();
            $table->string('cv')->nullable();
            $table->string('fee');

            $table->string('otp_code')->nullable(); // رمز التحقق
            $table->timestamp('otp_expires_at')->nullable(); // وقت انتهاء رمز التحقق
            $table->text('fcm_token')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
