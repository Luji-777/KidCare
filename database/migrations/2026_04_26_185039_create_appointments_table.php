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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled_by_patient', 'cancelled_by_clinic', 'checked_in', 'missed', 'completed'])->default('pending');
            $table->decimal('price', 8, 2)->nullable();

           // $table->decimal('base_price', 8, 2);

           
            $table->string('currency', 3)->default('USD');
            $table->text('required_tests')->nullable();
            $table->text('required_imaging')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid_online', 'partially_paid', 'fully_paid'])->default('unpaid');
            $table->decimal('doctor_earnings', 8, 2)->default(0);
            $table->boolean('reminder_24_sent')->default(false);
            $table->boolean('reminder_2h_sent')->default(false);
            $table->boolean('test_reminder_sent')->default(false);
            $table->enum('booking_source', ['online', 'reception'])->default('online');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
