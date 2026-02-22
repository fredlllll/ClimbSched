<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('climbing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('gym_name');
            $table->timestampTz('starts_at_utc');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('event_participants', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('climbing_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('joined_at')->useCurrent();
            $table->primary(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('climbing_events');
    }
};
