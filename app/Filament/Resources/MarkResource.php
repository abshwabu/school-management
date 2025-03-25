<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarkResource\Pages;
use App\Models\Mark;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarkResource extends Resource
{
    protected static ?string $model = Mark::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationLabel = 'Assessments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
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
                    ->searchable(),
                Forms\Components\TextInput::make('max_score')
                    ->label('Maximum Score')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(100),
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->label('Maximum Score')
                    ->sortable(),
                Tables\Columns\TextColumn::make('studentMarks_count')
                    ->label('Submissions')
                    ->counts('studentMarks')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_marks')
                    ->label('Add Student Marks')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('student_id')
                            ->label('Student')
                            ->options(function (Mark $record) {
                                return User::query()
                                    ->role('student')
                                    ->whereDoesntHave('studentMarks', function ($query) use ($record) {
                                        $query->where('mark_id', $record->id);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn (Mark $record) => $record->max_score)
                            ->suffix(fn (Mark $record) => "/ {$record->max_score}"),
                    ])
                    ->action(function (array $data, Mark $record): void {
                        $record->studentMarks()->create([
                            'student_id' => $data['student_id'],
                            'score' => $data['score'],
                        ]);
                    })
                    ->visible(fn (Mark $record): bool => 
                        auth()->user()->hasRole('admin') || 
                        (auth()->user()->hasRole('teacher') && $record->subject->teacher_id === auth()->id())
                    ),
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
                $query->whereHas('subject', function ($q) {
                    $q->where('teacher_id', auth()->id());
                });
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarks::route('/'),
            'create' => Pages\CreateMark::route('/create'),
            'edit' => Pages\EditMark::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['admin', 'teacher']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('admin') || 
            (auth()->user()->hasRole('teacher') && $record->subject->teacher_id === auth()->id());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin') || 
            (auth()->user()->hasRole('teacher') && $record->subject->teacher_id === auth()->id());
    }
} 