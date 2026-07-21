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
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('location')->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency')->default('GBP');
            $table->string('employment_type')->nullable();
            $table->string('source_name');
            $table->string('source_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('saved');
            $table->timestamp('saved_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'saved_at']);
            $table->index(['user_id', 'applied_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};
