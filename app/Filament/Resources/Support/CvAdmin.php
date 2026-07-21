<?php

namespace App\Filament\Resources\Support;

use App\Filament\Resources\CVS\CVResource;
use App\Models\CVTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CvAdmin
{
    private static function isAdministrator(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) $user->getAttribute('is_admin')
            || $user->getAttribute('role') === 'admin'
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (method_exists($user, 'hasRole') && $user->hasRole('admin'))
            || $user->can('manage-cvs');
    }

    private static function onlyActiveTemplateId(): ?int
    {
        $templateIds = CVTemplate::query()->where('active', true)->limit(2)->pluck('id');

        return $templateIds->count() === 1 ? (int) $templateIds->first() : null;
    }

    private static function cvSelect(): Select
    {
        return Select::make('cv_id')->label('CV')->relationship('cv', 'title')->searchable()->preload()->required()
            ->helperText('Choose the CV this entry belongs to.');
    }

    private static function dates(bool $startRequired = false): Fieldset
    {
        return Fieldset::make('Dates')->schema([
            DatePicker::make('start_date')->required($startRequired)->native(false),
            DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'),
        ])->columns(2);
    }

    private static function actions(Table $table): Table
    {
        return $table->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('No records yet')->emptyStateDescription('Create the first record to get started.');
    }

    public static function cvForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('General Information')->description('Ownership, presentation and headline details.')->schema([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->default(fn (): ?int => auth()->id())
                    ->hidden(fn (): bool => ! self::isAdministrator())
                    ->dehydrated()
                    ->required(),
                Select::make('template_id')
                    ->label('Template')
                    ->options(fn () => CVTemplate::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                    ->default(fn (): ?int => self::onlyActiveTemplateId())
                    ->searchable()
                    ->preload()
                    ->placeholder(fn (): string => CVTemplate::query()->where('active', true)->exists() ? 'Select an active CV template' : 'No active CV templates are available')
                    ->helperText(fn (): string => self::onlyActiveTemplateId()
                        ? 'The only active template has been selected automatically.'
                        : 'Choose the active template that controls this CV’s presentation.'),
                TextInput::make('title')->required()->maxLength(255)->placeholder('Senior Software Engineer CV'),
                TextInput::make('target_job_title')->maxLength(255)->placeholder('Senior Software Engineer'),
                Textarea::make('professional_summary')->rows(5)->maxLength(5000)->columnSpanFull()->placeholder('Concise professional profile and career highlights.'),
            ])->columns(2),
            Section::make('Variant Information')->schema([
                Toggle::make('is_master')
                    ->label('Master CV')
                    ->default(true)
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, ?bool $state): void {
                        if ($state) {
                            $set('parent_cv_id', null);
                            $set('variant_name', null);
                        }
                    })
                    ->helperText('Master CVs act as sources for tailored variants.'),
                Toggle::make('is_default')->label('Default CV')->helperText('Use this CV as the user’s default.'),
                Select::make('parent_cv_id')
                    ->label('Parent CV')
                    ->relationship('parent', 'title', modifyQueryUsing: fn ($query) => $query->where('is_master', true))
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => ! $get('is_master'))
                    ->visible(fn (Get $get): bool => ! $get('is_master'))
                    ->placeholder('Select the Master CV this variant is based on'),
                TextInput::make('variant_name')
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => ! $get('is_master'))
                    ->hidden(fn (Get $get): bool => (bool) $get('is_master'))
                    ->placeholder('Fintech application'),
            ])->columns(2),
            Section::make('Status')->schema([
                ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->colors(['draft' => 'gray', 'published' => 'success', 'archived' => 'warning'])->inline()->required()->default('draft'),
            ]),
        ]);
    }

    public static function cvTable(Table $table): Table
    {
        return self::actions($table->columns([
            TextColumn::make('title')->searchable()->sortable()->weight('medium'),
            TextColumn::make('user.name')->label('User')->description(fn ($record) => $record->user?->email)->searchable()->sortable(),
            TextColumn::make('template.name')->label('Template')->badge()->placeholder('Default')->searchable()->sortable(),
            TextColumn::make('variant_name')->label('Variant')->placeholder('—')->searchable()->sortable(),
            TextColumn::make('target_job_title')->placeholder('—')->searchable()->sortable()->toggleable(),
            TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                'published' => 'success', 'archived' => 'warning', default => 'gray'
            })->searchable()->sortable(),
            IconColumn::make('is_master')
                ->label('Master')
                ->boolean()
                ->trueIcon(Heroicon::CheckCircle)
                ->trueColor('success')
                ->falseIcon(Heroicon::MinusCircle)
                ->falseColor('gray')
                ->sortable(),
            TextColumn::make('last_used_at')->label('Last Used At')->dateTime('d M Y, H:i')->placeholder('Never')->sortable()->toggleable(),
            TextColumn::make('updated_at')->dateTime('d M Y, H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
            SelectFilter::make('template')->relationship('template', 'name')->searchable()->preload(),
            TernaryFilter::make('is_master')->label('Master CV'),
        ])->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Create your first CV')
            ->emptyStateDescription('Your first Master CV becomes the foundation for tailored AI-generated variants for individual job applications.')
            ->emptyStateActions([
                CreateAction::make('create')->label('Create CV')->url(CVResource::getUrl('create')),
            ]));
    }

    public static function templateForm(Schema $schema): Schema
    {
        return $schema->components([Section::make('Template Details')->schema([
            TextInput::make('name')->required()->maxLength(255)->placeholder('Modern Professional'),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true)->placeholder('modern-professional')->helperText('Unique identifier used by the application.'),
            TextInput::make('preview_image')->label('Preview Image')->maxLength(255)->placeholder('templates/modern.png')->helperText('Stored image path or URL.'),
            Fieldset::make('Availability')->schema([Toggle::make('premium')->label('Premium'), Toggle::make('active')->default(true)])->columns(2),
        ])->columns(2)]);
    }

    public static function templateTable(Table $table): Table
    {
        return self::actions($table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            IconColumn::make('premium')->boolean()->sortable(),
            IconColumn::make('active')->boolean()->trueIcon(Heroicon::CheckCircle)->trueColor('success')->falseIcon(Heroicon::MinusCircle)->falseColor('gray')->sortable(),
            TextColumn::make('created_at')->label('Created')->dateTime('d M Y')->sortable(),
        ])->filters([TernaryFilter::make('premium'), TernaryFilter::make('active')])->defaultSort('name'));
    }

    public static function experienceForm(Schema $schema, bool $withCv = true): Schema
    {
        $identity = [TextInput::make('job_title')->label('Job Title')->required()->maxLength(255)->placeholder('Senior Developer'), TextInput::make('company')->required()->maxLength(255)->placeholder('Acme Ltd'), TextInput::make('location')->maxLength(255)->placeholder('London, UK'), Select::make('employment_type')->options(['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'freelance' => 'Freelance', 'internship' => 'Internship'])->searchable()->placeholder('Select employment type')];
        if ($withCv) {
            array_unshift($identity, self::cvSelect());
        }

        return $schema->components([Section::make('Role')->schema($identity)->columns(2), Section::make('Timeline')->schema([DatePicker::make('start_date')->required()->native(false), DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'), Toggle::make('currently_working')->label('Current Position')->live()->helperText('Leave the end date empty for a current role.')])->columns(3), Section::make('Details')->schema([Textarea::make('description')->rows(6)->maxLength(10000)->columnSpanFull()->placeholder('Responsibilities, achievements and measurable impact.')])]);
    }

    public static function experienceTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('job_title')->searchable()->sortable(), TextColumn::make('company')->searchable()->sortable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable(), IconColumn::make('currently_working')->label('Current')->boolean()->sortable(), TextColumn::make('start_date')->label('Start')->date('M Y')->sortable(), TextColumn::make('end_date')->label('End')->date('M Y')->placeholder('Present')->sortable()])->filters([TernaryFilter::make('currently_working')->label('Current position'), SelectFilter::make('employment_type')->options(['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'freelance' => 'Freelance', 'internship' => 'Internship'])])->defaultSort('start_date', 'desc'));
    }

    public static function educationForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('institution')->required()->maxLength(255)->placeholder('University of Manchester'), TextInput::make('qualification')->required()->maxLength(255)->placeholder('BSc'), TextInput::make('field_of_study')->maxLength(255)->placeholder('Computer Science'), TextInput::make('grade')->maxLength(255)->placeholder('First Class')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Education')->schema($fields)->columns(2), self::dates(), Section::make('Details')->schema([Textarea::make('description')->rows(5)->maxLength(10000)->columnSpanFull()->placeholder('Relevant coursework, honours and activities.')])]);
    }

    public static function educationTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('institution')->searchable()->sortable(), TextColumn::make('qualification')->searchable()->sortable(), TextColumn::make('field_of_study')->searchable()->toggleable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable(), TextColumn::make('start_date')->label('Start')->date('M Y')->sortable(), TextColumn::make('end_date')->label('End')->date('M Y')->placeholder('Present')->sortable()])->filters([])->defaultSort('start_date', 'desc'));
    }

    public static function skillForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('name')->label('Skill Name')->required()->maxLength(255)->placeholder('Laravel'), TextInput::make('category')->maxLength(255)->placeholder('Backend Development'), Select::make('proficiency')->label('Level')->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'])->searchable()->placeholder('Select a level')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Skill Details')->schema($fields)->columns(2)]);
    }

    public static function skillTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('name')->label('Skill')->searchable()->sortable(), TextColumn::make('category')->badge()->placeholder('Uncategorised')->searchable()->sortable(), TextColumn::make('proficiency')->label('Level')->badge()->placeholder('—')->searchable()->sortable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable()])->filters([SelectFilter::make('proficiency')->label('Level')->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'])])->defaultSort('name'));
    }

    public static function projectForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('title')->label('Project Name')->required()->maxLength(255)->placeholder('Portfolio Platform')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Project')->schema($fields)->columns(2), Section::make('Description & Technology')->schema([Textarea::make('description')->rows(5)->maxLength(10000)->columnSpanFull(), TagsInput::make('technologies')->placeholder('Add a technology')->helperText('Press Enter after each technology.')]), Section::make('Links')->schema([TextInput::make('github_url')->label('Repository URL')->url()->maxLength(255)->placeholder('https://github.com/…'), TextInput::make('demo_url')->label('Live URL')->url()->maxLength(255)->placeholder('https://example.com')])->columns(2), self::dates()]);
    }

    public static function projectTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('title')->label('Project')->searchable()->sortable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable(), TextColumn::make('technologies')->badge()->separator(',')->limitList(3), TextColumn::make('start_date')->date('M Y')->sortable(), TextColumn::make('end_date')->date('M Y')->placeholder('Present')->sortable()])->filters([])->defaultSort('start_date', 'desc'));
    }

    public static function certificationForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('name')->label('Certification Name')->required()->maxLength(255)->placeholder('AWS Solutions Architect'), TextInput::make('organisation')->label('Issuing Organisation')->maxLength(255)->placeholder('Amazon Web Services')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Certification')->schema($fields)->columns(2), Fieldset::make('Validity')->schema([DatePicker::make('issue_date')->native(false), DatePicker::make('expiry_date')->native(false)->afterOrEqual('issue_date')])->columns(2), Section::make('Credential')->schema([TextInput::make('credential_id')->label('Credential ID')->maxLength(255), TextInput::make('credential_url')->label('Credential URL')->url()->maxLength(255)->placeholder('https://…')])->columns(2)]);
    }

    public static function certificationTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('name')->label('Certification')->searchable()->sortable(), TextColumn::make('organisation')->label('Issuer')->searchable()->sortable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable(), TextColumn::make('issue_date')->date('d M Y')->sortable(), TextColumn::make('expiry_date')->date('d M Y')->placeholder('No expiry')->sortable()])->filters([])->defaultSort('issue_date', 'desc'));
    }

    public static function languageForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('language')->required()->maxLength(255)->placeholder('English'), Select::make('proficiency')->options(['basic' => 'Basic', 'conversational' => 'Conversational', 'professional' => 'Professional', 'fluent' => 'Fluent', 'native' => 'Native'])->searchable()->placeholder('Select proficiency')->helperText('Choose “Native” for a native speaker.')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Language Details')->schema($fields)->columns(2)]);
    }

    public static function languageTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('language')->searchable()->sortable(), TextColumn::make('proficiency')->badge()->placeholder('—')->searchable()->sortable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable()])->filters([SelectFilter::make('proficiency')->options(['basic' => 'Basic', 'conversational' => 'Conversational', 'professional' => 'Professional', 'fluent' => 'Fluent', 'native' => 'Native'])])->defaultSort('language'));
    }

    public static function referenceForm(Schema $schema, bool $withCv = true): Schema
    {
        $fields = [TextInput::make('name')->required()->maxLength(255)->placeholder('Alex Smith'), TextInput::make('company')->maxLength(255), TextInput::make('job_title')->label('Position')->maxLength(255), TextInput::make('relationship')->maxLength(255)->placeholder('Former manager'), TextInput::make('email')->email()->maxLength(255)->placeholder('alex@example.com'), TextInput::make('phone')->tel()->maxLength(255)->placeholder('+44 20 1234 5678')];
        if ($withCv) {
            array_unshift($fields, self::cvSelect());
        }

        return $schema->components([Section::make('Reference Details')->schema($fields)->columns(2)]);
    }

    public static function referenceTable(Table $table): Table
    {
        return self::actions($table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('company')->searchable()->sortable(), TextColumn::make('job_title')->label('Position')->searchable()->sortable(), TextColumn::make('relationship')->badge()->searchable(), TextColumn::make('cv.title')->label('CV')->searchable()->sortable(), TextColumn::make('email')->searchable()->copyable()->toggleable()])->filters([])->defaultSort('name'));
    }
}
