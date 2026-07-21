<?php

namespace App\Filament\Resources\Support;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Filament\Resources\JobDescriptions\JobDescriptionResource;
use App\Filament\Resources\SavedJobs\SavedJobResource;
use App\Models\CV;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class JobAdmin
{
    private const EMPLOYMENT_TYPES = [
        'full-time' => 'Full-time',
        'part-time' => 'Part-time',
        'contract' => 'Contract',
        'temporary' => 'Temporary',
        'internship' => 'Internship',
        'freelance' => 'Freelance',
    ];

    private const SAVED_JOB_STATUSES = [
        'saved' => 'Saved',
        'interested' => 'Interested',
        'applied' => 'Applied',
        'rejected' => 'Rejected',
        'closed' => 'Closed',
    ];

    private const APPLICATION_STATUSES = [
        'draft' => 'Draft',
        'ready' => 'Ready',
        'applied' => 'Applied',
        'interview' => 'Interview',
        'technical_test' => 'Technical Test',
        'final_interview' => 'Final Interview',
        'offer' => 'Offer',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    private static function rowActions(Table $table): Table
    {
        return $table
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    private static function createdDateFilter(): Filter
    {
        return Filter::make('created_at')
            ->label('Created Date')
            ->schema([
                DatePicker::make('created_from')->label('Created from')->native(false),
                DatePicker::make('created_until')->label('Created until')->native(false),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['created_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                ->when($data['created_until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)));
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'analysed', 'interested', 'ready' => 'info',
            'applied' => 'primary',
            'interview', 'technical_test', 'final_interview' => 'warning',
            'offer', 'accepted' => 'success',
            'rejected', 'closed', 'withdrawn', 'archived' => 'danger',
            default => 'gray',
        };
    }

    public static function companyForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company Details')->description('Core employer information used across saved jobs and applications.')->schema([
                TextInput::make('name')->label('Company Name')->required()->maxLength(255)->placeholder('Acme Ltd'),
                TextInput::make('website')->url()->maxLength(255)->placeholder('https://www.example.com')->helperText('Include https:// so the link can be opened from the table.'),
                TextInput::make('industry')->maxLength(255)->placeholder('Technology'),
                TextInput::make('location')->maxLength(255)->placeholder('London, UK'),
                TextInput::make('company_size')->label('Size')->maxLength(255)->placeholder('51–200 employees'),
            ])->columns(2),
            Section::make('Notes')->schema([
                Textarea::make('description')->label('Notes')->rows(6)->maxLength(10000)->placeholder('Hiring context, contacts, culture notes, or useful research.')->columnSpanFull(),
            ]),
        ]);
    }

    public static function companyTable(Table $table): Table
    {
        return self::rowActions($table
            ->columns([
                TextColumn::make('name')->label('Company')->searchable()->sortable()->weight('medium'),
                TextColumn::make('industry')->badge()->placeholder('—')->searchable()->sortable(),
                TextColumn::make('location')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('website')->url(fn ($record) => $record->website, true)->openUrlInNewTab()->copyable()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y')->sortable(),
                TextColumn::make('updated_at')->label('Updated')->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([self::createdDateFilter()])
            ->defaultSort('name')
            ->emptyStateHeading('No companies yet.')
            ->emptyStateDescription('Add employers to connect job descriptions, saved jobs, and applications.')
            ->emptyStateActions([CreateAction::make('create')->label('Create Company')->url(CompanyResource::getUrl('create'))]));
    }

    public static function jobDescriptionForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Job Details')->description('Store the original vacancy details for later analysis.')->schema([
                Select::make('company_id')->label('Company')->relationship('company', 'name')->searchable()->preload()->required(),
                TextInput::make('title')->label('Job Title')->required()->maxLength(255)->placeholder('Senior Software Engineer'),
                TextInput::make('source_url')->label('Source URL')->url()->maxLength(255)->placeholder('https://jobs.example.com/role')->helperText('Link to the original vacancy.'),
                TextInput::make('location')->maxLength(255)->placeholder('London / Hybrid'),
                Select::make('employment_type')->options(self::EMPLOYMENT_TYPES)->searchable()->placeholder('Select employment type'),
                TextInput::make('salary')->maxLength(255)->placeholder('£45,000–£55,000'),
            ])->columns(2),
            Section::make('Original Job Description')->description('Paste the complete source text without summarising it.')->schema([
                RichEditor::make('description')->required()->columnSpanFull(),
            ]),
        ]);
    }

    public static function jobDescriptionTable(Table $table): Table
    {
        return self::rowActions($table
            ->columns([
                TextColumn::make('title')->label('Job Title')->searchable()->sortable()->weight('medium'),
                TextColumn::make('company.name')->label('Company')->searchable()->sortable(),
                TextColumn::make('location')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('employment_type')->label('Employment Type')->badge()->placeholder('—')->sortable()->toggleable(),
                TextColumn::make('source_url')->label('Source')->url(fn ($record) => $record->source_url, true)->openUrlInNewTab()->copyable()->limit(35)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('company')->relationship('company', 'name')->searchable()->preload(),
                SelectFilter::make('employment_type')->options(self::EMPLOYMENT_TYPES),
                self::createdDateFilter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Store job descriptions ready for AI analysis.')
            ->emptyStateDescription('Keep the original vacancy text and source details together for future analysis.')
            ->emptyStateActions([CreateAction::make('create')->label('Create Job Description')->url(JobDescriptionResource::getUrl('create'))]));
    }

    public static function savedJobForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ownership & Source')->schema([
                Select::make('user_id')->label('User')->relationship('user', 'name')->searchable(['name', 'email'])->preload()->required(),
                Select::make('company_id')->label('Company')->relationship('company', 'name')->searchable()->preload(),
                Select::make('job_description_id')->label('Job Description')->relationship('jobDescription', 'title')->searchable()->preload(),
                TextInput::make('source_name')->label('Source Name')->required()->maxLength(255)->placeholder('LinkedIn'),
                TextInput::make('source_url')->label('Source URL')->url()->maxLength(255)->placeholder('https://…'),
            ])->columns(2),
            Section::make('Opportunity')->schema([
                TextInput::make('title')->required()->maxLength(255)->placeholder('Senior Software Engineer'),
                TextInput::make('location')->maxLength(255)->placeholder('Remote / London'),
                Select::make('employment_type')->options(self::EMPLOYMENT_TYPES)->searchable()->placeholder('Select employment type'),
                Select::make('status')->options(self::SAVED_JOB_STATUSES)->required()->default('saved'),
            ])->columns(2),
            Fieldset::make('Salary')->schema([
                TextInput::make('salary_min')->label('Salary Min')->numeric()->minValue(0)->step(0.01)->placeholder('45000'),
                TextInput::make('salary_max')->label('Salary Max')->numeric()->minValue(0)->gte('salary_min')->step(0.01)->placeholder('55000'),
                TextInput::make('salary_currency')->label('Currency')->maxLength(3)->default('GBP')->placeholder('GBP')->helperText('Three-letter currency code.'),
            ])->columns(3),
            Section::make('Timeline & Notes')->schema([
                DateTimePicker::make('saved_at')->label('Saved At')->required()->default(now())->native(false)->seconds(false),
                DateTimePicker::make('applied_at')->label('Applied At')->native(false)->seconds(false)->afterOrEqual('saved_at'),
                Textarea::make('notes')->rows(5)->maxLength(10000)->placeholder('Why this role is interesting, contacts, or follow-up notes.')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function savedJobTable(Table $table): Table
    {
        return self::rowActions($table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('medium'),
                TextColumn::make('company.name')->label('Company')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('location')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('salary')->state(fn ($record): string => self::formatSalary($record->salary_min, $record->salary_max, $record->salary_currency))->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => self::SAVED_JOB_STATUSES[$state] ?? str($state)->headline())->color(fn (?string $state): string => self::statusColor($state))->sortable(),
                TextColumn::make('saved_at')->label('Saved Date')->dateTime('d M Y')->sortable(),
                TextColumn::make('applied_at')->label('Applied Date')->dateTime('d M Y')->placeholder('Not applied')->sortable(),
                TextColumn::make('source_url')->label('Source')->url(fn ($record) => $record->source_url, true)->openUrlInNewTab()->copyable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::SAVED_JOB_STATUSES),
                SelectFilter::make('company')->relationship('company', 'name')->searchable()->preload(),
                SelectFilter::make('employment_type')->options(self::EMPLOYMENT_TYPES),
                self::createdDateFilter(),
            ])
            ->defaultSort('saved_at', 'desc')
            ->emptyStateHeading('Save jobs before deciding whether to apply.')
            ->emptyStateDescription('Build a shortlist and keep useful vacancy details in one place.')
            ->emptyStateActions([CreateAction::make('create')->label('Save a Job')->url(SavedJobResource::getUrl('create'))]));
    }

    public static function jobApplicationForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application')->description('Connect this application to its owner, employer, role, and CV.')->schema([
                Select::make('user_id')->label('User')->relationship('user', 'name')->searchable(['name', 'email'])->preload()->required(),
                Select::make('company_id')->label('Company')->relationship('company', 'name')->searchable()->preload()->required(),
                Select::make('job_description_id')->label('Job Description')->relationship('jobDescription', 'title')->searchable()->preload(),
                Select::make('cv_id')->label('CV')->options(fn () => CV::query()->orderBy('title')->pluck('title', 'id'))->searchable()->preload(),
            ])->columns(2),
            Section::make('Progress')->schema([
                Select::make('status')->options(self::APPLICATION_STATUSES)->required()->default('draft'),
                DateTimePicker::make('applied_at')->label('Application Date')->native(false)->seconds(false)->helperText('Leave empty until the application is submitted.'),
                Textarea::make('notes')->rows(6)->maxLength(10000)->placeholder('Follow-ups, interview feedback, contacts, and next actions.')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function jobApplicationTable(Table $table): Table
    {
        return self::rowActions($table
            ->columns([
                TextColumn::make('jobDescription.title')->label('Job Title')->placeholder('Unlinked role')->searchable()->sortable()->weight('medium'),
                TextColumn::make('company.name')->label('Company')->searchable()->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable()->toggleable(),
                TextColumn::make('cv_id')->label('CV')->formatStateUsing(fn ($state): string => CV::query()->find($state)?->title ?? '—')->sortable()->toggleable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => self::APPLICATION_STATUSES[$state] ?? str($state)->headline())->color(fn (?string $state): string => self::statusColor($state))->sortable(),
                TextColumn::make('applied_at')->label('Application Date')->dateTime('d M Y')->placeholder('Not submitted')->sortable(),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::APPLICATION_STATUSES),
                SelectFilter::make('company')->relationship('company', 'name')->searchable()->preload(),
                self::createdDateFilter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Track every application from submission to offer.')
            ->emptyStateDescription('Record each application and keep its current stage visible.')
            ->emptyStateActions([CreateAction::make('create')->label('Create Application')->url(JobApplicationResource::getUrl('create'))]));
    }

    private static function formatSalary(mixed $minimum, mixed $maximum, ?string $currency): string
    {
        if ($minimum === null && $maximum === null) {
            return '—';
        }

        $symbol = match (strtoupper((string) $currency)) {
            'GBP' => '£', 'USD' => '$', 'EUR' => '€', default => $currency ? strtoupper($currency).' ' : '',
        };
        $format = fn ($value): string => $symbol.number_format((float) $value);

        return match (true) {
            $minimum !== null && $maximum !== null => $format($minimum).' - '.$format($maximum),
            $minimum !== null => 'From '.$format($minimum),
            default => 'Up to '.$format($maximum),
        };
    }
}
