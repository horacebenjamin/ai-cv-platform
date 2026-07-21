<?php

namespace App\Filament\Resources\CVS;

use App\Filament\Resources\CVS\Pages\CreateCV;
use App\Filament\Resources\CVS\Pages\EditCV;
use App\Filament\Resources\CVS\Pages\ListCVS;
use App\Filament\Resources\CVS\Pages\ViewCV;
use App\Filament\Resources\CVS\RelationManagers\CertificationsRelationManager;
use App\Filament\Resources\CVS\RelationManagers\EducationRelationManager;
use App\Filament\Resources\CVS\RelationManagers\ExperiencesRelationManager;
use App\Filament\Resources\CVS\RelationManagers\HistoriesRelationManager;
use App\Filament\Resources\CVS\RelationManagers\LanguagesRelationManager;
use App\Filament\Resources\CVS\RelationManagers\ProjectsRelationManager;
use App\Filament\Resources\CVS\RelationManagers\ReferencesRelationManager;
use App\Filament\Resources\CVS\RelationManagers\SkillsRelationManager;
use App\Filament\Resources\CVS\Schemas\CVForm;
use App\Filament\Resources\CVS\Schemas\CVInfolist;
use App\Filament\Resources\CVS\Tables\CVSTable;
use App\Models\CV;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CVResource extends Resource
{
    protected static ?string $model = CV::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'cvs';

    protected static ?string $modelLabel = 'CV';

    protected static ?string $pluralModelLabel = 'CVs';

    protected static ?string $navigationLabel = 'CVs';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return CVForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CVInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CVSTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ExperiencesRelationManager::class,
            EducationRelationManager::class,
            SkillsRelationManager::class,
            ProjectsRelationManager::class,
            CertificationsRelationManager::class,
            LanguagesRelationManager::class,
            ReferencesRelationManager::class,
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCVS::route('/'),
            'create' => CreateCV::route('/create'),
            'view' => ViewCV::route('/{record}'),
            'edit' => EditCV::route('/{record}/edit'),
        ];
    }
}
