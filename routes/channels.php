<?php

use App\Models\Quiz;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('quiz.{quizId}', function ($user, int $quizId) {
    $quiz = Quiz::find($quizId);

    if (! $quiz) {
        return false;
    }

    return $quiz->players()->where('users.id', $user->id)->exists()
        || (int) $quiz->created_by === (int) $user->id;
});