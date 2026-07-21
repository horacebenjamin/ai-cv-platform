<?php

namespace App\Filament\Resources\SavedJobs;

use App\Filament\Resources\SavedJobs\Pages\CreateSavedJob;
use App\Filament\Resources\SavedJobs\Pages\EditSavedJob;
use App\Filament\Resources\SavedJobs\Pages\ListSavedJobs;
use App\Filament\Resources\SavedJobs\Pages\ViewSavedJob;
use App\Filament\Resources\SavedJobs\Schemas\SavedJobForm;
use App\Filament\Resources\SavedJobs\Schemas\SavedJobInfolist;
use App\Filament\Resources\SavedJobs\Tables\SavedJobsTable;
use App\Models\SavedJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SavedJobResource extends Resource
{
    protected static ?string $model = SavedJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Job Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SavedJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SavedJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavedJobsTable::configure($table);
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
            'index' => ListSavedJobs::route('/'),
            'create' => CreateSavedJob::route('/create'),
            'view' => ViewSavedJob::route('/{record}'),
            'edit' => EditSavedJob::route('/{record}/edit'),
        ];
    }
}
