<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
public const STATUS_DRAFT = 'draft';
public const STATUS_LIVE = 'live';
public const STATUS_PAUSED = 'paused';
public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'title',
        'description',
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
        'settings' => 'array',
        'current_question_index' => 'integer',
    ];

public static function statuses(): array
{
    return [
        self::STATUS_DRAFT => 'Nicht gestartet',
        self::STATUS_LIVE => 'Live',
        self::STATUS_PAUSED => 'Pausiert',
        self::STATUS_ENDED => 'Beendet',
    ];
}

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_quiz')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('question_quiz.sort_order');
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

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}