<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentMarkResource\Pages;
use App\Models\StudentMark;
use App\Models\Mark;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentMarkResource extends Resource
{
    protected static ?string $model = StudentMark::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationLabel = 'Assessment Marks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('subject_id')
                    ->label('Subject')
                    ->options(function () {
                        return Subject::query()
                            ->when(auth()->user()->hasRole('teacher'), function ($query) {
                                $query->where('teacher_id', auth()->id());
                            })
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('mark_id', null)),
                Forms\Components\Select::make('mark_id')
                    ->label('Assessment')
                    ->options(function (Forms\Get $get) {
                        if (!$get('subject_id')) return [];
                        return Mark::query()
                            ->where('subject_id', $get('subject_id'))
                            ->when(auth()->user()->hasRole('teacher'), function ($query) {
                                $query->where('teacher_id', auth()->id());
                            })
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->live(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name', function (Builder $query) {
                        $query->role('student');
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(fn (Forms\Get $get) => Mark::find($get('mark_id'))?->max_score ?? 100)
                    ->suffix(fn (Forms\Get $get) => '/ ' . (Mark::find($get('mark_id'))?->max_score ?? '?')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mark.subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mark.name')
                    ->label('Assessment')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->suffix(fn (Model $record): string => "/{$record->mark->max_score}")
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject')
                    ->relationship('mark.subject', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(auth()->user()->hasRole('teacher'), function ($query) {
                $query->whereHas('mark', function ($q) {
                    $q->where('teacher_id', auth()->id());
                });
            })
            ->when(auth()->user()->hasRole('student'), function ($query) {
                $query->where('student_id', auth()->id());
            });
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['admin', 'teacher']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('admin') || 
            (auth()->user()->hasRole('teacher') && $record->mark->teacher_id === auth()->id());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin') || 
            (auth()->user()->hasRole('teacher') && $record->mark->teacher_id === auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentMarks::route('/'),
            'create' => Pages\CreateStudentMark::route('/create'),
            'edit' => Pages\EditStudentMark::route('/{record}/edit'),
        ];
    }
} 