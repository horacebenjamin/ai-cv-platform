<?php

namespace App\Filament\Resources\CvLanguages;

use App\Filament\Resources\CvLanguages\Pages\CreateCvLanguage;
use App\Filament\Resources\CvLanguages\Pages\EditCvLanguage;
use App\Filament\Resources\CvLanguages\Pages\ListCvLanguages;
use App\Filament\Resources\CvLanguages\Pages\ViewCvLanguage;
use App\Filament\Resources\CvLanguages\Schemas\CvLanguageForm;
use App\Filament\Resources\CvLanguages\Schemas\CvLanguageInfolist;
use App\Filament\Resources\CvLanguages\Tables\CvLanguagesTable;
use App\Models\CvLanguage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvLanguageResource extends Resource
{
    protected static ?string $model = CvLanguage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Languages';

    protected static ?string $recordTitleAttribute = 'language';

    public static function form(Schema $schema): Schema
    {
        return CvLanguageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvLanguageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvLanguagesTable::configure($table);
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
            'index' => ListCvLanguages::route('/'),
            'create' => CreateCvLanguage::route('/create'),
            'view' => ViewCvLanguage::route('/{record}'),
            'edit' => EditCvLanguage::route('/{record}/edit'),
        ];
    }
}
