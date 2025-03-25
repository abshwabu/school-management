<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class StudentGradesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Grade::query()
                    ->where('student_id', auth()->id())
                    ->with(['subject'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->suffix(fn ($record) => "/{$record->max_score}")
                    ->sortable(),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('student');
    }
} 