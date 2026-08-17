<?php

namespace App\Filament\Resources\CVS\Pages;

use App\Filament\Resources\CVS\CVResource;
use App\Models\CVTemplate;
use App\Models\JobDescription;
use App\Models\Profile;
use App\Services\CV\CVGenerationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;

class ListCVS extends ListRecords
{
    protected static string $resource = CVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateWithAi')
                ->label('Generate with AI')
                ->icon('heroicon-o-sparkles')
                ->steps([
                    Step::make('Select User')->schema([
                        Select::make('profile_id')
                            ->label('User')
                            ->options(fn (): array => Profile::query()->with('user')->get()
                                ->mapWithKeys(fn (Profile $profile): array => [
                                    $profile->getKey() => $profile->user->name.' — '.trim("{$profile->first_name} {$profile->last_name}"),
                                ])->all())
                            ->searchable()
                            ->required()
                            ->live(),
                    ]),
                    Step::make('Select Target Job')->schema([
                        Select::make('target_job_id')
                            ->label('Target job (optional)')
                            ->options(fn (): array => JobDescription::query()->orderBy('title')->pluck('title', 'id')->all())
                            ->searchable(),
                    ]),
                    Step::make('Select Template')->schema([
                        Select::make('template_id')
                            ->label('CV template')
                            ->options(fn (): array => CVTemplate::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                    ]),
                    Step::make('Review Profile')->schema([
                        Placeholder::make('profile_review')
                            ->label('Profile details')
                            ->content(function (Get $get): string {
                                $profile = Profile::query()->find($get('profile_id'));

                                return $profile
                                    ? collect([
                                        trim("{$profile->first_name} {$profile->last_name}"),
                                        $profile->headline,
                                        $profile->location,
                                        $profile->bio,
                                    ])->filter()->implode("\n")
                                    : 'Select a user to review their profile.';
                            }),
                    ]),
                    Step::make('Generate')->schema([
                        Placeholder::make('generation_notice')
                            ->hiddenLabel()
                            ->content('Generation will run safely in the background. The completed CV will appear in this list.'),
                    ]),
                ])
                ->modalSubmitActionLabel('Queue generation')
                ->action(function (array $data, CVGenerationService $generation): void {
                    $profile = Profile::query()->findOrFail($data['profile_id']);
                    $targetJob = filled($data['target_job_id'] ?? null)
                        ? JobDescription::query()->findOrFail($data['target_job_id'])
                        : null;
                    $template = CVTemplate::query()->findOrFail($data['template_id']);
                    $request = $generation->queue($profile, $targetJob, $template);

                    Notification::make()
                        ->success()
                        ->title('CV generation queued')
                        ->body("AI request #{$request->getKey()} is processing in the background.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
