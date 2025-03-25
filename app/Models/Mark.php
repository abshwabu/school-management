<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mark extends Model
{
    protected $fillable = [
        'name',
        'subject_id',
        'teacher_id',
        'max_score',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute()
    {
        $count = static::where('subject_id', $this->subject_id)
            ->where('name', $this->name)
            ->where('id', '<', $this->id)
            ->count();

        return $count > 0 ? "{$this->name} " . ($count + 1) : $this->name;
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function studentMarks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }
} 