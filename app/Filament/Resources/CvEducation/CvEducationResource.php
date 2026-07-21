<?php

namespace App\Filament\Resources\CvEducation;

use App\Filament\Resources\CvEducation\Pages\CreateCvEducation;
use App\Filament\Resources\CvEducation\Pages\EditCvEducation;
use App\Filament\Resources\CvEducation\Pages\ListCvEducation;
use App\Filament\Resources\CvEducation\Pages\ViewCvEducation;
use App\Filament\Resources\CvEducation\Schemas\CvEducationForm;
use App\Filament\Resources\CvEducation\Schemas\CvEducationInfolist;
use App\Filament\Resources\CvEducation\Tables\CvEducationTable;
use App\Models\CvEducation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvEducationResource extends Resource
{
    protected static ?string $model = CvEducation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Education';

    protected static ?string $recordTitleAttribute = 'institution';

    public static function form(Schema $schema): Schema
    {
        return CvEducationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvEducationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvEducationTable::configure($table);
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
            'index' => ListCvEducation::route('/'),
            'create' => CreateCvEducation::route('/create'),
            'view' => ViewCvEducation::route('/{record}'),
            'edit' => EditCvEducation::route('/{record}/edit'),
        ];
    }
}
