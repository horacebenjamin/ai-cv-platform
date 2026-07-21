<?php

namespace App\Filament\Resources\CvExperiences;

use App\Filament\Resources\CvExperiences\Pages\CreateCvExperience;
use App\Filament\Resources\CvExperiences\Pages\EditCvExperience;
use App\Filament\Resources\CvExperiences\Pages\ListCvExperiences;
use App\Filament\Resources\CvExperiences\Pages\ViewCvExperience;
use App\Filament\Resources\CvExperiences\Schemas\CvExperienceForm;
use App\Filament\Resources\CvExperiences\Schemas\CvExperienceInfolist;
use App\Filament\Resources\CvExperiences\Tables\CvExperiencesTable;
use App\Models\CvExperience;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvExperienceResource extends Resource
{
    protected static ?string $model = CvExperience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Experience';

    protected static ?string $recordTitleAttribute = 'job_title';

    public static function form(Schema $schema): Schema
    {
        return CvExperienceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvExperienceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvExperiencesTable::configure($table);
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
            'index' => ListCvExperiences::route('/'),
            'create' => CreateCvExperience::route('/create'),
            'view' => ViewCvExperience::route('/{record}'),
            'edit' => EditCvExperience::route('/{record}/edit'),
        ];
    }
}
