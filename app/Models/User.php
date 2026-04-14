<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasRoles;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    public function createdQuizzes(): HasMany
{
    return $this->hasMany(Quiz::class, 'created_by');
}

public function quizzes(): BelongsToMany
{
    return $this->belongsToMany(Quiz::class, 'quiz_user')
        ->withPivot([
            'score',
            'joined_at',
            'finished_at',
        ])
        ->withTimestamps();
}
}