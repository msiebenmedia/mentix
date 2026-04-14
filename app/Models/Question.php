<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_NUMBER_GUESS = 'number_guess';
    public const TYPE_NUMERIC_GUESS = 'numeric_guess';
    public const TYPE_ESTIMATE = 'estimate';
    public const TYPE_DATE = 'date';
    public const TYPE_DATE_GUESS = 'date_guess';
    public const TYPE_IMAGE_CHOICE = 'image_choice';
    public const TYPE_SORTING = 'sorting';

    protected $fillable = [
        'question_catalog_id',
        'type',
        'question',
        'image_path',
        'correct_numeric_answer',
        'correct_date_answer',
        'explanation',
        'points',
        'is_active',
    ];

    protected $casts = [
        'correct_numeric_answer' => 'decimal:2',
        'correct_date_answer' => 'date',
        'points' => 'integer',
        'is_active' => 'boolean',
    ];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(QuestionCatalog::class, 'question_catalog_id');
    }

public function options(): HasMany
{
    return $this->hasMany(QuestionOption::class);
}

public function optionsSorted(): HasMany
{
    return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
}

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'question_quiz')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public static function types(): array
    {
        return [
            self::TYPE_SINGLE_CHOICE => 'Single Choice',
            self::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            self::TYPE_TRUE_FALSE => 'Wahr / Falsch',
            self::TYPE_TEXT => 'Text',
            self::TYPE_NUMBER => 'Zahl',
            self::TYPE_NUMBER_GUESS => 'Zahl schätzen',
            self::TYPE_NUMERIC_GUESS => 'Numerische Schätzfrage',
            self::TYPE_ESTIMATE => 'Schätzfrage',
            self::TYPE_DATE => 'Datum',
            self::TYPE_DATE_GUESS => 'Datumsfrage',
            self::TYPE_IMAGE_CHOICE => 'Bildfrage',
            self::TYPE_SORTING => 'Sortierfrage',
        ];
    }

    public function requiresOptions(): bool
    {
        return in_array($this->type, [
            self::TYPE_SINGLE_CHOICE,
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_TRUE_FALSE,
            self::TYPE_IMAGE_CHOICE,
            self::TYPE_SORTING,
        ], true);
    }

    public function isGuessType(): bool
    {
        return in_array($this->type, [
            self::TYPE_ESTIMATE,
            self::TYPE_NUMBER_GUESS,
            self::TYPE_NUMERIC_GUESS,
            self::TYPE_DATE_GUESS,
        ], true);
    }

    public function isTextBasedType(): bool
    {
        return in_array($this->type, [
            self::TYPE_TEXT,
        ], true);
    }

    public function isNumericType(): bool
    {
        return in_array($this->type, [
            self::TYPE_NUMBER,
            self::TYPE_NUMBER_GUESS,
            self::TYPE_NUMERIC_GUESS,
            self::TYPE_ESTIMATE,
        ], true);
    }

    public function isDateType(): bool
    {
        return in_array($this->type, [
            self::TYPE_DATE,
            self::TYPE_DATE_GUESS,
        ], true);
    }
}