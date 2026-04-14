<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->enum('status', [
                'draft',
                'scheduled',
                'live',
                'ended',
            ])->default('draft');

            $table->string('layout_template')->default('classic');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedInteger('current_question_index')->default(0);

            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};