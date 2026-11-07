<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('analysis_note_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('stance');
            $table->string('url', 2048);
            $table->text('quote');
            $table->text('explanation')->nullable();
            $table->string('verification')->default('pending');
            $table->string('page_title')->nullable();
            $table->text('snapshot')->nullable();
            $table->string('assessment')->nullable();
            $table->text('assessment_reason')->nullable();
            $table->timestamps();
        });
    }
};
