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
        Schema::create('parent_models', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->text('address');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->string('password');

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
        Schema::dropIfExists('parent_models');
    }
};
