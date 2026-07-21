<?php

namespace App\Filament\Resources\CvHistories;

use App\Filament\Resources\CvHistories\Pages\ListCvHistories;
use App\Filament\Resources\CvHistories\Pages\ViewCvHistory;
use App\Filament\Resources\CvHistories\Schemas\CvHistoryForm;
use App\Filament\Resources\CvHistories\Schemas\CvHistoryInfolist;
use App\Filament\Resources\CvHistories\Tables\CvHistoriesTable;
use App\Models\CvHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvHistoryResource extends Resource
{
    protected static ?string $model = CvHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'History';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CvHistoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvHistoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvHistoriesTable::configure($table);
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
            'index' => ListCvHistories::route('/'),
            'view' => ViewCvHistory::route('/{record}'),
        ];
    }
}
