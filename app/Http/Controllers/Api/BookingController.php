<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        // Date filter (default: server current time)
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : now()->startOfDay();

        $startOfDay = $date->copy();
        $endOfDay = $date->copy()->endOfDay();

        $bookings = Booking::with(['room:id,name,location', 'user:id,name'])
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->where('start_time', '<', $endOfDay)
                    ->where('end_time', '>', $startOfDay);
            })
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'data' => $bookings,
        ]);
    }

    public function store(Request $request)
    {
        // Input validation
        $rules = [
            'room_id' => ['required', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:225'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];

        // Rules if user is admin
        if ($request->user()->isAdmin()) {
            $rules['lecturer_id'] = ['required', 'exists:users,id'];
            $rules['admin_reason'] = ['required', 'string', 'max:1000'];
        }

        // validate
        $validated = $request->validate($rules);

        // Check room exists & active
        $room = Room::firstWhere([
            'id' => $validated['room_id'],
            'is_active' => true,
        ]);

        // If room not available
        if (! $room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not available',
            ], 422);
        }

        // Normalise dates
        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);

        // checking overlap
        $overlapExists = Booking::where('room_id', $room->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })->exists();

        if ($overlapExists) {
            return response()->json([
                'success' => false,
                'message' => 'This room is already booked for the selected time.',
            ], 422);
        }

        $user = $request->user();

        // create booking
        $booking = Booking::create([
            // Lecturer
            'user_id' => $user->isAdmin() ? $validated['lecturer_id'] : $user->id,

            // Creator (admin or lecturer)
            'created_by' => $user->id,

            'room_id' => $room->id,
            'title' => $validated['title'],
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'pending',

            'admin_reason' => $user->isAdmin() ? $validated['admin_reason'] : null,
        ]);

        // adding to audit logs
        audit(
            'booking_created',
            $booking,
            null,
            $booking->toArray(),
            $booking->admin_reason
        );

        // response
        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => [
                'id' => $booking->id,
                'title' => $booking->title,
                'room_id' => $booking->room_id,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => $booking->status,
            ],
        ], 201);
    }

    // User: request cancellation
    public function requestCancel(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved bookings can be requested for cancellation.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $booking->getOriginal();

        $booking->update([
            'status' => 'cancel_requested',
            'cancel_request_reason' => $validated['reason'],
        ]);

        audit(
            'booking_cancel_requested',
            $booking,
            $old,
            $booking->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Cancellation request submitted.',
        ]);
    }

    // Admin: cancel booking
    public function cancel(Booking $booking, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'cancel_requested') {
            return response()->json([
                'success' => false,
                'message' => 'Only cancellation requests can be cancelled.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $booking->getOriginal();

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => $validated['reason'],
        ]);

        audit(
            'booking_cancelled',
            $booking,
            $old,
            $booking->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled.',
        ]);
    }

    // Admin: approve
    public function approve(Booking $booking, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be approved.',
            ], 422);
        }

        $old = $booking->getOriginal();

        $booking->update([
            'status' => 'approved',
        ]);

        audit(
            'booking_approved',
            $booking,
            $old,
            $booking->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking approved.',
        ]);
    }

    // Admin: reject
    public function reject(Booking $booking, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $booking->getOriginal();

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        audit(
            'booking_rejected',
            $booking,
            $old,
            $booking->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking rejected',
        ]);
    }

    public function rejectCancel(Booking $booking, Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised.',
            ], 403);
        }

        if ($booking->status !== 'cancel_requested') {
            return response()->json([
                'success' => false,
                'message' => 'Only cancellation requests can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $booking->getOriginal();

        $booking->update([
            'status' => 'approved',
            'cancel_reject_reason' => $validated['reason'],
        ]);

        audit(
            'booking_cancel_rejected',
            $booking,
            $old,
            $booking->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Cancellation request rejected.',
        ]);
    }
}
