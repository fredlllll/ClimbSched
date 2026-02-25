<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'climbing_events';

    protected $fillable = [
        'creator_id',
        'gym_name',
        'starts_at_utc',
        'duration_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_participants')
            ->withPivot('joined_at');
    }
}
