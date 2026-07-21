<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('professional_summary')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('cv_templates')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_master')->default(true);
            $table->foreignId('parent_cv_id')->nullable()->constrained('cvs')->nullOnDelete();
            $table->string('variant_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('target_job_title')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('is_master');
            $table->index('variant_name');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cvs');
    }
};
