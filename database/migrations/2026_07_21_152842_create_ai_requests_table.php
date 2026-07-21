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
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('feature');
            $table->longText('prompt');
            $table->longText('response')->nullable();
            $table->string('model');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->string('status')->default('completed');
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
