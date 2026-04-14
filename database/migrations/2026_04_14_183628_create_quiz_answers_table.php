<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('question_option_id')
                ->nullable()
                ->constrained('question_options')
                ->nullOnDelete();

            $table->text('answer_text')->nullable();
            $table->decimal('answer_numeric', 12, 2)->nullable();
            $table->date('answer_date')->nullable();

            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points_awarded')->default(0);

            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            $table->unique(['quiz_id', 'question_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};