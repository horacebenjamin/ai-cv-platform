<?php

namespace App\Filament\Resources\Support;

use App\Filament\Resources\AiRequests\AiRequestResource;
use App\Filament\Resources\CreditTransactions\CreditTransactionResource;
use App\Jobs\ProcessAIRequest;
use App\Models\AiRequest;
use App\Models\CreditTransaction;
use App\Models\CV;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AiAdmin
{
    private const STATUSES = [
        'queued' => 'Queued',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    private static function actions(Table $table): Table
    {
        return $table
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    private static function dateFilter(): Filter
    {
        return Filter::make('created_at')
            ->label('Date')
            ->schema([
                DatePicker::make('from')->label('From')->native(false),
                DatePicker::make('until')->label('Until')->native(false),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)));
    }

    public static function provider(?string $model): string
    {
        $model = strtolower((string) $model);

        return match (true) {
            str_contains($model, 'claude') => 'Anthropic',
            str_contains($model, 'gemini') => 'Google',
            str_contains($model, 'mistral') => 'Mistral',
            str_contains($model, 'llama') => 'Meta',
            str_contains($model, 'gpt'), str_contains($model, 'o1'), str_contains($model, 'o3'), str_contains($model, 'o4') => 'OpenAI',
            default => 'Unknown',
        };
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Queued',
            'processing', 'running' => 'Processing',
            default => self::STATUSES[$status] ?? str((string) $status)->headline()->toString(),
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            'running', 'processing' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public static function prettyJson(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '{}';
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $state;
            }
            $state = $decoded;
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public static function aiRequestForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request')->description('Manually queue or maintain an AI operation for administrative testing.')->schema([
                Select::make('user_id')->label('User')->relationship('user', 'name')->searchable(['name', 'email'])->preload()->required(),
                Select::make('cv_id')->label('CV')->options(fn () => CV::query()->orderBy('title')->pluck('title', 'id'))->searchable()->preload()->helperText('Optional CV context for this operation.'),
                TextInput::make('feature')->label('Request Type')->required()->maxLength(255)->placeholder('professional_summary')->helperText('A stable operation identifier, such as cv_rewrite.'),
                TextInput::make('model')->label('AI Model')->required()->maxLength(255)->placeholder('gpt-5-mini')->helperText('Provider is inferred from the model name.'),
                Select::make('status')->options(self::STATUSES)->required()->default('queued'),
            ])->columns(2),
            Section::make('Payload')->schema([
                Textarea::make('prompt')->required()->rows(10)->maxLength(100000)->placeholder('Enter the prompt or JSON payload to process.')->columnSpanFull(),
                Textarea::make('response')->rows(10)->maxLength(500000)->placeholder('The provider response will appear here when available.')->columnSpanFull(),
            ]),
            Section::make('Usage')->schema([
                TextInput::make('tokens_used')->label('Credits Used')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('processing_time_ms')->label('Processing Time (ms)')->numeric()->minValue(0),
                TextInput::make('cost')->numeric()->minValue(0)->step(0.000001)->prefix('$')->default(0)->required(),
            ])->columns(3),
        ]);
    }

    public static function aiRequestTable(Table $table): Table
    {
        return self::actions($table
            ->columns([
                TextColumn::make('user.name')->label('User')->description(fn ($record) => $record->user?->email)->searchable()->sortable(),
                TextColumn::make('cv_id')->label('CV')->formatStateUsing(fn ($state): string => CV::query()->find($state)?->title ?? '—')->sortable()->toggleable(),
                TextColumn::make('feature')->label('Request Type')->badge()->searchable()->sortable(),
                TextColumn::make('provider')->label('AI Provider')->state(fn ($record): string => self::provider($record->model))->badge()->color('info'),
                TextColumn::make('model')->label('AI Model')->searchable()->sortable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state): string => self::statusLabel($state))->color(fn ($state): string => self::statusColor($state))->sortable(),
                TextColumn::make('tokens_used')->label('Credits Used')->numeric()->sortable()->summarize(Sum::make()->label('Total')),
                TextColumn::make('processing_time_ms')->label('Processing Time')->formatStateUsing(fn ($state): string => $state === null ? '—' : number_format($state).' ms')->sortable()->toggleable(),
                TextColumn::make('cost')->money('USD')->sortable()->summarize(Sum::make()->money('USD')),
                TextColumn::make('created_at')->label('Created At')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES + ['pending' => 'Queued (legacy)', 'running' => 'Processing (legacy)']),
                SelectFilter::make('provider')->options(['OpenAI' => 'OpenAI', 'Anthropic' => 'Anthropic', 'Google' => 'Google', 'Mistral' => 'Mistral', 'Meta' => 'Meta'])->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;
                    $patterns = ['OpenAI' => ['gpt', 'o1', 'o3', 'o4'], 'Anthropic' => ['claude'], 'Google' => ['gemini'], 'Mistral' => ['mistral'], 'Meta' => ['llama']];

                    return $query->when($value, function (Builder $query) use ($patterns, $value): void {
                        $query->where(function (Builder $query) use ($patterns, $value): void {
                            foreach ($patterns[$value] ?? [] as $pattern) {
                                $query->orWhere('model', 'like', "%{$pattern}%");
                            }
                        });
                    });
                }),
                SelectFilter::make('user')->relationship('user', 'name')->searchable()->preload(),
                SelectFilter::make('feature')->label('Request Type')->options(fn () => AiRequest::query()->distinct()->orderBy('feature')->pluck('feature', 'feature')->all())->searchable(),
                self::dateFilter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No AI requests yet')
            ->emptyStateDescription('Manually queue a request to test the future AI processing workflow.')
            ->emptyStateActions([CreateAction::make('create')->label('Create AI Request')->url(AiRequestResource::getUrl('create'))]))
            ->recordActions([
                Action::make('process')
                    ->label('Process Request')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (AiRequest $record): bool => in_array($record->status, ['queued', 'pending'], true))
                    ->action(function (AiRequest $record): void {
                        $record->forceFill(['status' => 'queued'])->save();
                        ProcessAIRequest::dispatch($record->getKey());
                        Notification::make()->title('AI request queued')->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function aiRequestInfolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request Details')->schema([
                TextEntry::make('user.name')->label('User'),
                TextEntry::make('cv_id')->label('CV')->formatStateUsing(fn ($state): string => CV::query()->find($state)?->title ?? '—'),
                TextEntry::make('feature')->label('Request Type')->badge(),
                TextEntry::make('provider')->label('AI Provider')->state(fn ($record): string => self::provider($record->model))->badge(),
                TextEntry::make('model')->label('AI Model'),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state): string => self::statusLabel($state))->color(fn ($state): string => self::statusColor($state)),
                TextEntry::make('tokens_used')->label('Credits Used')->numeric(),
                TextEntry::make('processing_time_ms')->label('Processing Time')->formatStateUsing(fn ($state): string => $state === null ? '—' : number_format($state).' ms'),
                TextEntry::make('cost')->money('USD'),
                TextEntry::make('created_at')->label('Created At')->dateTime('d M Y, H:i:s'),
            ])->columns(2),
            Section::make('Prompt JSON')->description('Formatted as JSON when possible; otherwise the original prompt is shown.')->schema([
                TextEntry::make('prompt')->label('')->formatStateUsing(fn ($state): string => self::prettyJson($state))->copyable()->columnSpanFull(),
            ]),
            Section::make('Response JSON')->schema([
                TextEntry::make('response')->label('')->formatStateUsing(fn ($state): string => self::prettyJson($state))->copyable()->columnSpanFull(),
            ]),
            Section::make('Metadata JSON')->description('Metadata storage is not available in the current schema.')->schema([
                TextEntry::make('metadata')->label('')->state('{}')->copyable()->columnSpanFull(),
            ]),
        ]);
    }

    public static function creditForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Credit Transaction')->description('Record an administrative credit adjustment for a user.')->schema([
                Select::make('user_id')->label('User')->relationship('user', 'name')->searchable(['name', 'email'])->preload()->required(),
                TextInput::make('amount')->label('Credits')->numeric()->required()->helperText('Use a positive value to add credits or a negative value to deduct them.'),
                TextInput::make('type')->label('Reason Type')->required()->maxLength(255)->placeholder('ai_usage'),
                Textarea::make('description')->label('Reason')->rows(4)->maxLength(255)->placeholder('Credits used for CV rewrite.')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function creditTable(Table $table): Table
    {
        return self::actions($table
            ->columns([
                TextColumn::make('user.name')->label('User')->description(fn ($record) => $record->user?->email)->searchable()->sortable(),
                TextColumn::make('amount')->label('Credits')->numeric()->sortable()->color(fn ($state): string => $state < 0 ? 'danger' : 'success')->summarize(Sum::make()->label('Net Total')),
                TextColumn::make('description')->label('Reason')->state(fn ($record): string => $record->description ?: str($record->type)->headline())->searchable()->wrap(),
                TextColumn::make('balance_after')->label('Balance After')->state('—')->toggleable(),
                TextColumn::make('created_at')->label('Created At')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('user')->relationship('user', 'name')->searchable()->preload(),
                SelectFilter::make('type')->label('Reason')->options(fn () => CreditTransaction::query()->distinct()->orderBy('type')->pluck('type', 'type')->map(fn ($type) => str($type)->headline())->all())->searchable(),
                self::dateFilter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No credit transactions yet')
            ->emptyStateDescription('Credit additions and deductions will appear here.')
            ->emptyStateActions([CreateAction::make('create')->label('Create Credit Transaction')->url(CreditTransactionResource::getUrl('create'))]));
    }
}
