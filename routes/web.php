<?php

use App\Http\Controllers\CareerCertificationController;
use App\Http\Controllers\CareerEducationController;
use App\Http\Controllers\CareerExperienceController;
use App\Http\Controllers\CareerProfileController;
use App\Http\Controllers\CareerProjectController;
use App\Http\Controllers\CareerSkillController;
use App\Http\Controllers\CvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/career-profile', [CareerProfileController::class, 'edit'])->name('career-profile.edit');
    Route::patch('/career-profile', [CareerProfileController::class, 'update'])->name('career-profile.update');

    Route::post('/career-profile/experiences', [CareerExperienceController::class, 'store'])
        ->name('career-profile.experiences.store');
    Route::patch('/career-profile/experiences/{experience}', [CareerExperienceController::class, 'update'])
        ->whereNumber('experience')->name('career-profile.experiences.update');
    Route::delete('/career-profile/experiences/{experience}', [CareerExperienceController::class, 'destroy'])
        ->whereNumber('experience')->name('career-profile.experiences.destroy');

    Route::post('/career-profile/skills', [CareerSkillController::class, 'store'])
        ->name('career-profile.skills.store');
    Route::patch('/career-profile/skills/{skill}', [CareerSkillController::class, 'update'])
        ->whereNumber('skill')->name('career-profile.skills.update');
    Route::delete('/career-profile/skills/{skill}', [CareerSkillController::class, 'destroy'])
        ->whereNumber('skill')->name('career-profile.skills.destroy');

    Route::post('/career-profile/projects', [CareerProjectController::class, 'store'])
        ->name('career-profile.projects.store');
    Route::patch('/career-profile/projects/{project}', [CareerProjectController::class, 'update'])
        ->whereNumber('project')->name('career-profile.projects.update');
    Route::delete('/career-profile/projects/{project}', [CareerProjectController::class, 'destroy'])
        ->whereNumber('project')->name('career-profile.projects.destroy');

    Route::post('/career-profile/education', [CareerEducationController::class, 'store'])
        ->name('career-profile.education.store');
    Route::patch('/career-profile/education/{education}', [CareerEducationController::class, 'update'])
        ->whereNumber('education')->name('career-profile.education.update');
    Route::delete('/career-profile/education/{education}', [CareerEducationController::class, 'destroy'])
        ->whereNumber('education')->name('career-profile.education.destroy');

    Route::post('/career-profile/certifications', [CareerCertificationController::class, 'store'])
        ->name('career-profile.certifications.store');
    Route::patch('/career-profile/certifications/{certification}', [CareerCertificationController::class, 'update'])
        ->whereNumber('certification')->name('career-profile.certifications.update');
    Route::delete('/career-profile/certifications/{certification}', [CareerCertificationController::class, 'destroy'])
        ->whereNumber('certification')->name('career-profile.certifications.destroy');

    Route::post('/career-profile/imports', [CvImportController::class, 'store'])
        ->name('career-profile.imports.store');
    Route::get('/career-profile/imports/{import}', [CvImportController::class, 'show'])
        ->whereNumber('import')
        ->name('career-profile.imports.show');
    Route::post('/career-profile/imports/{import}/apply', [CvImportController::class, 'apply'])
        ->whereNumber('import')
        ->name('career-profile.imports.apply');
    Route::delete('/career-profile/imports/{import}', [CvImportController::class, 'destroy'])
        ->whereNumber('import')
        ->name('career-profile.imports.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
