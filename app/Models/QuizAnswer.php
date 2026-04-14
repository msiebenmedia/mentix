<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_id',
        'user_id',
        'question_option_id',
        'answer_text',
        'answer_numeric',
        'answer_date',
        'answer_json',
        'is_correct',
        'points_awarded',
        'answered_at',
    ];

    protected $casts = [
        'answer_numeric' => 'decimal:2',
        'answer_date' => 'date',
        'answer_json' => 'array',
        'answered_at' => 'datetime',
        'is_correct' => 'boolean',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}