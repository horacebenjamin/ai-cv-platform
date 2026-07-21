<?php

namespace App\Filament\Resources\CvReferences;

use App\Filament\Resources\CvReferences\Pages\CreateCvReference;
use App\Filament\Resources\CvReferences\Pages\EditCvReference;
use App\Filament\Resources\CvReferences\Pages\ListCvReferences;
use App\Filament\Resources\CvReferences\Pages\ViewCvReference;
use App\Filament\Resources\CvReferences\Schemas\CvReferenceForm;
use App\Filament\Resources\CvReferences\Schemas\CvReferenceInfolist;
use App\Filament\Resources\CvReferences\Tables\CvReferencesTable;
use App\Models\CvReference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvReferenceResource extends Resource
{
    protected static ?string $model = CvReference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'References';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CvReferenceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvReferenceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvReferencesTable::configure($table);
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
            'index' => ListCvReferences::route('/'),
            'create' => CreateCvReference::route('/create'),
            'view' => ViewCvReference::route('/{record}'),
            'edit' => EditCvReference::route('/{record}/edit'),
        ];
    }
}
