<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('referrer_id')->nullable()->after('is_admin')->constrained('users')->nullOnDelete();
        });

        Schema::table('invitations', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });

        // Users who signed up before referrers were stored get them from their invitation.
        DB::table('invitations')
            ->whereNotNull('invitee_id')
            ->orderBy('id')
            ->each(function (object $invitation): void {
                DB::table('users')
                    ->where('id', $invitation->invitee_id)
                    ->whereNull('referrer_id')
                    ->update(['referrer_id' => $invitation->inviter_id]);
            });
    }
};
