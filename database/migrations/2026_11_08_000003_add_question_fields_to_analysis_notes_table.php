<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_notes', function (Blueprint $table): void {
            $table->string('question_kind')->nullable();
            $table->string('status')->nullable();
            $table->string('verdict')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
        });
    }
};
