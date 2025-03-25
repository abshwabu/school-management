<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Subject;
use App\Models\Grade;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (auth()->user()->hasRole('admin')) {
            return [
                Stat::make('Total Students', User::role('student')->count())
                    ->description('Number of enrolled students')
                    ->icon('heroicon-o-users'),
                Stat::make('Total Teachers', User::role('teacher')->count())
                    ->description('Number of active teachers')
                    ->icon('heroicon-o-academic-cap'),
                Stat::make('Total Subjects', Subject::count())
                    ->description('Number of subjects offered')
                    ->icon('heroicon-o-book-open'),
                Stat::make('Average Grade', function () {
                    $avg = Grade::avg('total_score');
                    return $avg ? number_format($avg, 1) : 'N/A';
                })
                    ->description('Overall student performance')
                    ->icon('heroicon-o-chart-bar'),
            ];
        }

        if (auth()->user()->hasRole('teacher')) {
            $teacherId = auth()->id();
            return [
                Stat::make('My Subjects', Subject::where('teacher_id', $teacherId)->count())
                    ->description('Subjects you teach')
                    ->icon('heroicon-o-book-open'),
                Stat::make('My Students', function () use ($teacherId) {
                    return User::role('student')
                        ->whereHas('grades.subject', function ($query) use ($teacherId) {
                            $query->where('teacher_id', $teacherId);
                        })
                        ->count();
                })
                    ->description('Students in your subjects')
                    ->icon('heroicon-o-users'),
                Stat::make('Average Score', function () use ($teacherId) {
                    $avg = Grade::whereHas('subject', function ($query) use ($teacherId) {
                        $query->where('teacher_id', $teacherId);
                    })->avg('total_score');
                    return $avg ? number_format($avg, 1) : 'N/A';
                })
                    ->description('Average student performance')
                    ->icon('heroicon-o-chart-bar'),
            ];
        }

        if (auth()->user()->hasRole('student')) {
            $studentId = auth()->id();
            $grades = Grade::where('student_id', $studentId)->get();
            
            return [
                Stat::make('Subjects Enrolled', $grades->count())
                    ->description('Number of subjects taken')
                    ->icon('heroicon-o-book-open'),
                Stat::make('Average Score', function () use ($grades) {
                    $avg = $grades->avg('total_score');
                    return $avg ? number_format($avg, 1) : 'N/A';
                })
                    ->description('Your overall performance')
                    ->icon('heroicon-o-chart-bar'),
                Stat::make('Best Grade', function () use ($grades) {
                    return $grades->sortByDesc('total_score')->first()?->letter_grade ?? 'N/A';
                })
                    ->description('Your highest grade')
                    ->icon('heroicon-o-trophy'),
            ];
        }

        return [];
    }
} 