<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('source', 2048)->nullable();
            $table->string('normalized_url', 2048)->nullable();
            $table->string('url_hash', 64)->nullable()->index();
            $table->string('content_hash', 64)->index();
            $table->string('state')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('submission_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('not_started');
            $table->longText('annotated_html')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'type']);
        });

        Schema::create('analysis_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('annotation_id')->nullable();
            $table->text('label')->nullable();
            $table->string('aspect_label')->nullable();
            $table->text('text');
            $table->string('ethics')->default('neutral');
            $table->unsignedTinyInteger('strength')->default(5);
            $table->timestamps();
        });
    }
};
