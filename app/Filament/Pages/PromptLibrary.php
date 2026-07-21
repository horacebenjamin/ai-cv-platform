<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PromptLibrary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'AI Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Prompt Library';

    protected static ?string $title = 'Prompt Library';

    protected string $view = 'filament.pages.prompt-library';

    /** @return array<int, array{name: string, description: string, provider: string, model: string, status: string}> */
    public function getPrompts(): array
    {
        return [
            ['name' => 'CV Generation', 'description' => 'Generate a structured CV draft from profile and career information.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
            ['name' => 'CV Rewrite', 'description' => 'Rewrite selected CV content for clarity, relevance, and impact.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
            ['name' => 'Professional Summary', 'description' => 'Create a concise summary tailored to a target role.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
            ['name' => 'Skills Optimisation', 'description' => 'Prioritise and phrase skills against a supplied job description.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
            ['name' => 'Cover Letter', 'description' => 'Draft a role-specific cover letter using CV and company context.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
            ['name' => 'Job Match Analysis', 'description' => 'Compare a CV with a vacancy and identify strengths and gaps.', 'provider' => 'OpenAI', 'model' => 'To be configured', 'status' => 'Planned'],
        ];
    }
}
