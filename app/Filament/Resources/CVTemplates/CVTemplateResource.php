<?php

namespace App\Filament\Resources\CVTemplates;

use App\Filament\Resources\CVTemplates\Pages\CreateCVTemplate;
use App\Filament\Resources\CVTemplates\Pages\EditCVTemplate;
use App\Filament\Resources\CVTemplates\Pages\ListCVTemplates;
use App\Filament\Resources\CVTemplates\Pages\ViewCVTemplate;
use App\Filament\Resources\CVTemplates\Schemas\CVTemplateForm;
use App\Filament\Resources\CVTemplates\Schemas\CVTemplateInfolist;
use App\Filament\Resources\CVTemplates\Tables\CVTemplatesTable;
use App\Models\CVTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CVTemplateResource extends Resource
{
    protected static ?string $model = CVTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'cv-templates';

    protected static ?string $modelLabel = 'CV Template';

    protected static ?string $pluralModelLabel = 'CV Templates';

    protected static ?string $navigationLabel = 'CV Templates';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('active', true)->count();
    }

    public static function form(Schema $schema): Schema
    {
        return CVTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CVTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CVTemplatesTable::configure($table);
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
            'index' => ListCVTemplates::route('/'),
            'create' => CreateCVTemplate::route('/create'),
            'view' => ViewCVTemplate::route('/{record}'),
            'edit' => EditCVTemplate::route('/{record}/edit'),
        ];
    }
}
