<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Grade extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'total_score',
        'max_score',
        'letter_grade',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public static function calculateLetterGrade($percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 85 => 'A',
            $percentage >= 80 => 'A-',
            $percentage >= 75 => 'B+',
            $percentage >= 70 => 'B',
            $percentage >= 65 => 'B-',
            $percentage >= 60 => 'C+',
            $percentage >= 55 => 'C',
            $percentage >= 50 => 'C-',
            $percentage >= 45 => 'D+',
            $percentage >= 40 => 'D',
            default => 'F',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($grade) {
            // Check if score exceeds max_score
            if ($grade->total_score > $grade->max_score) {
                throw ValidationException::withMessages([
                    'total_score' => ["Score cannot exceed the maximum score of {$grade->max_score}"],
                ]);
            }

            $grade->letter_grade = self::calculateLetterGrade($grade->total_score / $grade->max_score * 100);
        });
    }
} 