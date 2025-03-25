<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Grade;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TeacherStudentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->role('student')
                    ->whereHas('grades', function (Builder $query) {
                        $query->whereHas('subject', function ($q) {
                            $q->where('teacher_id', auth()->id());
                        });
                    })
                    ->withAvg(['grades' => function ($query) {
                        $query->whereHas('subject', function ($q) {
                            $q->where('teacher_id', auth()->id());
                        });
                    }], 'total_score')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('grades_avg_total_score')
                    ->label('Average Score')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : 'N/A')
                    ->sortable(),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('teacher');
    }
}