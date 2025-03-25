<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMark extends Model
{
    protected $fillable = [
        'student_id',
        'mark_id',
        'score',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($studentMark) {
            // Update the grade when a mark is added or updated
            $student = $studentMark->student;
            $subject = $studentMark->mark->subject;

            $totalScore = $student->studentMarks()
                ->whereHas('mark', fn($q) => $q->where('subject_id', $subject->id))
                ->sum('score');

            $maxScore = Mark::where('subject_id', $subject->id)->sum('max_score');

            $percentage = ($totalScore / $maxScore) * 100;
            $letterGrade = Grade::calculateLetterGrade($percentage);

            Grade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'total_score' => $totalScore,
                    'max_score' => $maxScore,
                    'letter_grade' => $letterGrade,
                ]
            );
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function mark(): BelongsTo
    {
        return $this->belongsTo(Mark::class);
    }
} 