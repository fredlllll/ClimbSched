<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $userId = $request->user()->id;

        $events = Event::query()
            ->with('creator:id,name')
            ->withCount('participants')
            ->withExists(['participants as joined' => fn ($query) => $query->where('users.id', $userId)])
            ->whereBetween('starts_at_utc', [$data['start'], $data['end']])
            ->orderBy('starts_at_utc')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'creator_id' => $event->creator_id,
                'creator_name' => $event->creator?->name,
                'gym_name' => $event->gym_name,
                'starts_at_utc' => $event->starts_at_utc?->toAtomString(),
                'notes' => $event->notes,
                'participants' => $event->participants_count,
                'joined' => (bool) $event->joined,
            ]);

        return response()->json(['events' => $events]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'starts_at_utc' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $utc = CarbonImmutable::parse($data['starts_at_utc'])->setTimezone(new DateTimeZone('UTC'));

        DB::transaction(function () use ($request, $data, $utc): void {
            $event = Event::create([
                'creator_id' => $request->user()->id,
                'gym_name' => $data['gym_name'],
                'starts_at_utc' => $utc,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::table('event_participants')->insert([
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
                'joined_at' => now('UTC'),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function join(Request $request): JsonResponse
    {
        $data = $request->validate(['event_id' => ['required', 'integer', 'exists:climbing_events,id']]);

        DB::table('event_participants')->upsert([
            'event_id' => $data['event_id'],
            'user_id' => $request->user()->id,
            'joined_at' => now('UTC'),
        ], ['event_id', 'user_id'], ['joined_at']);

        return response()->json(['ok' => true]);
    }

    public function leave(Request $request): JsonResponse
    {
        $data = $request->validate(['event_id' => ['required', 'integer']]);
        DB::table('event_participants')
            ->where('event_id', $data['event_id'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate(['event_id' => ['required', 'integer']]);
        Event::query()
            ->whereKey($data['event_id'])
            ->where('creator_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
