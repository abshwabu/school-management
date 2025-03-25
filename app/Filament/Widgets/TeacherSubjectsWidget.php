<?php

namespace App\Filament\Widgets;

use App\Models\Subject;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeacherSubjectsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subject::query()
                    ->where('teacher_id', auth()->id())
                    ->withCount('students')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->sortable(),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('teacher');
    }
} 