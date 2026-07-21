<?php

namespace App\Filament\Resources\CvSkills;

use App\Filament\Resources\CvSkills\Pages\CreateCvSkill;
use App\Filament\Resources\CvSkills\Pages\EditCvSkill;
use App\Filament\Resources\CvSkills\Pages\ListCvSkills;
use App\Filament\Resources\CvSkills\Pages\ViewCvSkill;
use App\Filament\Resources\CvSkills\Schemas\CvSkillForm;
use App\Filament\Resources\CvSkills\Schemas\CvSkillInfolist;
use App\Filament\Resources\CvSkills\Tables\CvSkillsTable;
use App\Models\CvSkill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvSkillResource extends Resource
{
    protected static ?string $model = CvSkill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Skills';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CvSkillForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvSkillInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvSkillsTable::configure($table);
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
            'index' => ListCvSkills::route('/'),
            'create' => CreateCvSkill::route('/create'),
            'view' => ViewCvSkill::route('/{record}'),
            'edit' => EditCvSkill::route('/{record}/edit'),
        ];
    }
}
