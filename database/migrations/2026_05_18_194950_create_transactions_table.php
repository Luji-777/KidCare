<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            if (!Schema::hasColumn('transactions', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');
            } else {

                $table->foreignId('appointment_id')->nullable()->change();
            }
            $table->string('stripe_payment_intent_id')->unique()->nullable();
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status');
            $table->enum('payment_method', ['cash', 'stripe'])->default('stripe');
            $table->enum('type', ['fixed', 'additions'])->default('fixed');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable(false)->change();
        });
    }
};
