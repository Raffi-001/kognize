<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table): void {
            $table->id();
            $table->string('subject');
            $table->text('intro')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_submission', function (Blueprint $table): void {
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->primary(['newsletter_id', 'submission_id']);
        });

        Schema::create('newsletter_poll', function (Blueprint $table): void {
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->primary(['newsletter_id', 'poll_id']);
        });

        Schema::create('analysis_note_newsletter', function (Blueprint $table): void {
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analysis_note_id')->constrained()->cascadeOnDelete();
            $table->primary(['newsletter_id', 'analysis_note_id']);
        });

        Schema::create('newsletter_user', function (Blueprint $table): void {
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['newsletter_id', 'user_id']);
        });
    }
};
