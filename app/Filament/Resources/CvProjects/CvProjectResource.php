<?php

namespace App\Filament\Resources\CvProjects;

use App\Filament\Resources\CvProjects\Pages\CreateCvProject;
use App\Filament\Resources\CvProjects\Pages\EditCvProject;
use App\Filament\Resources\CvProjects\Pages\ListCvProjects;
use App\Filament\Resources\CvProjects\Pages\ViewCvProject;
use App\Filament\Resources\CvProjects\Schemas\CvProjectForm;
use App\Filament\Resources\CvProjects\Schemas\CvProjectInfolist;
use App\Filament\Resources\CvProjects\Tables\CvProjectsTable;
use App\Models\CvProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvProjectResource extends Resource
{
    protected static ?string $model = CvProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CvProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvProjectsTable::configure($table);
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
            'index' => ListCvProjects::route('/'),
            'create' => CreateCvProject::route('/create'),
            'view' => ViewCvProject::route('/{record}'),
            'edit' => EditCvProject::route('/{record}/edit'),
        ];
    }
}
