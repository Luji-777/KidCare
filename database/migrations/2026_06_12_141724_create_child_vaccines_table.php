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
        Schema::create('child_vaccines', function (Blueprint $table) {
            $table->id();
             $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('vaccine_id') ->constrained('vaccines') ->cascadeOnDelete();
            $table->date('taken_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_vaccines');
    }
};
