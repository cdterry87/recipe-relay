<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When the weekly digest goes out, in the member's own timezone. 0 is Sunday.
            $table->unsignedTinyInteger('digest_day')->default(0)->after('timezone');
            $table->unsignedTinyInteger('digest_hour')->default(9)->after('digest_day');
            $table->timestamp('last_digest_at')->nullable()->after('digest_hour');
            // On/off per App\Enums\EmailPreference. Missing keys use the enum's default.
            $table->json('email_preferences')->nullable()->after('last_digest_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['digest_day', 'digest_hour', 'last_digest_at', 'email_preferences']));
    }
};
