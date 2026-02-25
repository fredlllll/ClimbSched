<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('climbing_events', function (Blueprint $table): void {
            $table->unsignedInteger('duration_minutes')->default(120)->after('starts_at_utc');
        });
    }

    public function down(): void
    {
        Schema::table('climbing_events', function (Blueprint $table): void {
            $table->dropColumn('duration_minutes');
        });
    }
};
