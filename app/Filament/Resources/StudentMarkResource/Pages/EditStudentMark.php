<?php

namespace App\Filament\Resources\StudentMarkResource\Pages;

use App\Filament\Resources\StudentMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentMark extends EditRecord
{
    protected static string $resource = StudentMarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 