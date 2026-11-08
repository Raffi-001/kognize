<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('votable');
            $table->tinyInteger('value');
            $table->timestamps();

            $table->unique(['user_id', 'votable_type', 'votable_id']);
        });

        Schema::create('reputation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vote_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->integer('amount');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_badges', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge');
            $table->nullableMorphs('subject');
            $table->timestamps();

            $table->index(['user_id', 'badge']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->integer('reputation')->default(1)->index();
        });

        foreach (['user_notes', 'comments', 'evidence'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->integer('score')->default(0);
            });
        }
    }
};
