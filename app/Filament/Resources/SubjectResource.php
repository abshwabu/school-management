<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Academic';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'name', function (Builder $query) {
                        $query->whereHas('roles', function ($q) {
                            $q->whereIn('name', ['admin', 'teacher']);
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')->required()->email(),
                        Forms\Components\TextInput::make('password')->required()->password(),
                        Forms\Components\Hidden::make('roles')->default(['teacher']),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If user is a teacher, only show their subjects
        if (auth()->user()->hasRole('teacher')) {
            $query->where('teacher_id', auth()->id());
        }

        return $query;
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()->hasRole('admin') ? 'Academic' : null;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_subject');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_subject');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_subject');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_subject');
    }
} 