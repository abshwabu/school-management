<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeResource\Pages;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationLabel = 'Student Marks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name', function (Builder $query) {
                        $query->role('student');
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('subject_id')
                    ->options(function () {
                        return Subject::query()
                            ->when(auth()->user()->hasRole('teacher'), function ($query) {
                                $query->where('teacher_id', auth()->id());
                            })
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('total_score')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                Forms\Components\TextInput::make('max_score')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->suffix(fn (Model $record): string => "/{$record->max_score}")
                    ->numeric(),
                Tables\Columns\TextColumn::make('letter_grade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state[0]) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Grade $record) => "Marks for {$record->student->name} in {$record->subject->name}")
                    ->modalContent(function (Grade $record): HtmlString {
                        $marks = $record->student->studentMarks()
                            ->whereHas('mark', function ($query) use ($record) {
                                $query->where('subject_id', $record->subject_id);
                            })
                            ->with('mark')
                            ->get();

                        $html = "<div class='space-y-4'>";
                        
                        foreach ($marks as $mark) {
                            $html .= "<div class='flex justify-between'>";
                            $html .= "<span>{$mark->mark->name}</span>";
                            $html .= "<span>{$mark->score}/{$mark->mark->max_score}</span>";
                            $html .= "</div>";
                        }

                        $html .= "<div class='border-t pt-4 mt-4'>";
                        $html .= "<div class='flex justify-between font-bold'>";
                        $html .= "<span>Total</span>";
                        $html .= "<span>{$record->total_score}/{$record->max_score}</span>";
                        $html .= "</div>";
                        $html .= "<div class='flex justify-between font-bold'>";
                        $html .= "<span>Grade</span>";
                        $html .= "<span>{$record->letter_grade}</span>";
                        $html .= "</div>";
                        $html .= "</div>";
                        $html .= "</div>";

                        return new HtmlString($html);
                    }),
                Tables\Actions\Action::make('add_mark')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('mark_id')
                            ->label('Assessment')
                            ->options(function (Grade $record) {
                                return Mark::query()
                                    ->where('subject_id', $record->subject_id)
                                    ->where('teacher_id', auth()->id())
                                    ->pluck('name', 'id');
                            })
                            ->required(),
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn (Forms\Get $get) => Mark::find($get('mark_id'))?->max_score ?? 100)
                            ->suffix(fn (Forms\Get $get) => '/ ' . (Mark::find($get('mark_id'))?->max_score ?? '?')),
                    ])
                    ->action(function (array $data, Grade $record): void {
                        $record->student->studentMarks()->updateOrCreate(
                            ['mark_id' => $data['mark_id']],
                            ['score' => $data['score']]
                        );
                    })
                    ->visible(fn (Grade $record): bool => 
                        auth()->user()->hasRole('teacher') && 
                        $record->subject->teacher_id === auth()->id()
                    ),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(auth()->user()->hasRole('teacher'), function ($query) {
                $query->whereHas('subject', function ($q) {
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
            (auth()->user()->hasRole('teacher') && $record->subject->teacher_id === auth()->id());
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrades::route('/'),
            'create' => Pages\CreateGrade::route('/create'),
            'edit' => Pages\EditGrade::route('/{record}/edit'),
        ];
    }
} 