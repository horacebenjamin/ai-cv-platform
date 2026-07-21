<?php

namespace App\Filament\Resources\JobDescriptions;

use App\Filament\Resources\JobDescriptions\Pages\CreateJobDescription;
use App\Filament\Resources\JobDescriptions\Pages\EditJobDescription;
use App\Filament\Resources\JobDescriptions\Pages\ListJobDescriptions;
use App\Filament\Resources\JobDescriptions\Pages\ViewJobDescription;
use App\Filament\Resources\JobDescriptions\Schemas\JobDescriptionForm;
use App\Filament\Resources\JobDescriptions\Schemas\JobDescriptionInfolist;
use App\Filament\Resources\JobDescriptions\Tables\JobDescriptionsTable;
use App\Models\JobDescription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JobDescriptionResource extends Resource
{
    protected static ?string $model = JobDescription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Job Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return JobDescriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JobDescriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobDescriptionsTable::configure($table);
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
            'index' => ListJobDescriptions::route('/'),
            'create' => CreateJobDescription::route('/create'),
            'view' => ViewJobDescription::route('/{record}'),
            'edit' => EditJobDescription::route('/{record}/edit'),
        ];
    }
}
