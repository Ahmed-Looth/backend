<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RoomController extends Controller
{
    public function available(Request $request)
    {
        // Require time range
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $start = Carbon::parse($request->query('start'));
        $end = Carbon::parse($request->query('end'));

        // Rooms booked in this time slot
        $bookedRoomIds = Booking::whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->pluck('room_id');

        // Available rooms
        $rooms = Room::where('is_active', true)
            ->whereNotIn('id', $bookedRoomIds)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    public function index()
    {
        $rooms = Room::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    public function show(Room $room)
    {
        return response()->json([
            'success' => true,
            'data' => $room,
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['string', 'max:225'],
            'capacity' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'capacity' => $validated['capacity'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => $room,
        ], 201);
    }

    public function update(Room $room, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully.',
            'data' => $room,
        ]);
    }

    public function deactivate(Room $room, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $room->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room deactivated.',
        ]);
    }
}
