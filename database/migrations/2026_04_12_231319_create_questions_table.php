<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_catalog_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');
            $table->text('question');

            $table->string('image_path')->nullable();

            $table->decimal('correct_numeric_answer', 12, 2)->nullable();
            $table->date('correct_date_answer')->nullable();

            $table->text('explanation')->nullable();

            $table->unsignedInteger('points')->default(100);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};