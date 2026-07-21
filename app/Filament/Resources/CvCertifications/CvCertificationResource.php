<?php

namespace App\Filament\Resources\CvCertifications;

use App\Filament\Resources\CvCertifications\Pages\CreateCvCertification;
use App\Filament\Resources\CvCertifications\Pages\EditCvCertification;
use App\Filament\Resources\CvCertifications\Pages\ListCvCertifications;
use App\Filament\Resources\CvCertifications\Pages\ViewCvCertification;
use App\Filament\Resources\CvCertifications\Schemas\CvCertificationForm;
use App\Filament\Resources\CvCertifications\Schemas\CvCertificationInfolist;
use App\Filament\Resources\CvCertifications\Tables\CvCertificationsTable;
use App\Models\CvCertification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvCertificationResource extends Resource
{
    protected static ?string $model = CvCertification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'CV Management';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Certifications';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CvCertificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CvCertificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvCertificationsTable::configure($table);
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
            'index' => ListCvCertifications::route('/'),
            'create' => CreateCvCertification::route('/create'),
            'view' => ViewCvCertification::route('/{record}'),
            'edit' => EditCvCertification::route('/{record}/edit'),
        ];
    }
}
