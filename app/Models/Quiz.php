<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'status',
        'layout_template',
        'starts_at',
        'ended_at',
        'created_by',
        'current_question_index',
        'settings',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ended_at' => 'datetime',
        'current_question_index' => 'integer',
        'settings' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quiz_user')
            ->withPivot([
                'score',
                'joined_at',
                'finished_at',
            ])
            ->withTimestamps();
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_quiz')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('question_quiz.sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function currentQuestion(): ?Question
    {
        return $this->questions()
            ->skip((int) $this->current_question_index)
            ->first();
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isRevealed(): bool
    {
        return (bool) data_get($this->settings, 'question_revealed', false);
    }

    public function totalQuestions(): int
    {
        if ($this->relationLoaded('questions')) {
            return $this->questions->count();
        }

        return $this->questions()->count();
    }
}