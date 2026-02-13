<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VirtualClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VirtualClassAPIController extends Controller
{
    /**
     * Display a listing of virtual classes.
     */
    public function index(Request $request)
    {
        $query = VirtualClass::with(['instructor'])
            ->orderBy('start_time', 'asc');

        // Apply filters
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $classes = $query->paginate(12);

        return response()->json([
            'data' => $classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'description' => $class->description,
                    'scheduled_at' => $class->start_time,
                    'duration_minutes' => $class->duration_minutes,
                    'meeting_link' => $class->meeting_link,
                    'instructor' => $class->instructor,
                    'participants_count' => $class->current_participants ?? 0,
                ];
            }),
            'pagination' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ]
        ]);
    }

    /**
     * Display the specified virtual class.
     */
    public function show($id)
    {
        $class = VirtualClass::with(['instructor'])->findOrFail($id);

        return response()->json([
            'id' => $class->id,
            'title' => $class->title,
            'description' => $class->description,
            'course_id' => $class->course_id,
            'instructor' => $class->instructor,
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'duration_minutes' => $class->duration_minutes,
            'meeting_link' => $class->meeting_link,
            'meeting_id' => $class->meeting_id,
            'password' => $class->password,
            'max_participants' => $class->max_participants,
            'current_participants' => $class->current_participants ?? 0,
            'status' => $class->status,
            'is_recorded' => $class->is_recorded,
            'recording_url' => $class->recording_url,
            'created_at' => $class->created_at,
            'updated_at' => $class->updated_at,
        ]);
    }

    /**
     * Store a newly created virtual class.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'required|url',
            'meeting_id' => 'required|string|max:255',
            'password' => 'nullable|string|max:50',
            'max_participants' => 'required|integer|min:1',
            'is_recorded' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $class = VirtualClass::create([
            'title' => $request->title,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'instructor_id' => $request->user()->id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $request->duration_minutes ?? 
                $request->start_time->diffInMinutes($request->end_time),
            'meeting_link' => $request->meeting_link,
            'meeting_id' => $request->meeting_id,
            'password' => $request->password,
            'max_participants' => $request->max_participants,
            'current_participants' => 0,
            'status' => 'scheduled',
            'is_recorded' => $request->is_recorded ?? false,
        ]);

        return response()->json([
            'message' => 'Virtual class created successfully',
            'class' => $class
        ], 201);
    }

    /**
     * Update the specified virtual class.
     */
    public function update(Request $request, $id)
    {
        $class = VirtualClass::findOrFail($id);

        // Check if user owns this class or is admin
        if ($class->instructor_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_time' => 'sometimes|date|after:now',
            'end_time' => 'sometimes|date|after:start_time',
            'meeting_link' => 'sometimes|url',
            'meeting_id' => 'sometimes|string|max:255',
            'password' => 'nullable|string|max:50',
            'max_participants' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
            'is_recorded' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $class->update($request->all());

        return response()->json([
            'message' => 'Virtual class updated successfully',
            'class' => $class
        ]);
    }

    /**
     * Remove the specified virtual class.
     */
    public function destroy(Request $request, $id)
    {
        $class = VirtualClass::findOrFail($id);

        // Check if user owns this class or is admin
        if ($class->instructor_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $class->delete();

        return response()->json([
            'message' => 'Virtual class deleted successfully'
        ]);
    }

    /**
     * Join a virtual class.
     */
    public function join(Request $request, $classId)
    {
        $user = $request->user();
        $class = VirtualClass::findOrFail($classId);

        // Check if class is joinable
        if ($class->status !== 'scheduled' && $class->status !== 'ongoing') {
            return response()->json([
                'message' => 'Class is not available for joining',
                'status' => $class->status
            ], 422);
        }

        // Check if there's space
        if ($class->current_participants >= $class->max_participants) {
            return response()->json([
                'message' => 'Class is full'
            ], 422);
        }

        // Check if already joined
        $existingParticipant = \DB::table('virtual_class_participants')
            ->where('virtual_class_id', $classId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingParticipant) {
            return response()->json([
                'message' => 'Already joined this class'
            ], 422);
        }

        // Add participant
        \DB::table('virtual_class_participants')->insert([
            'virtual_class_id' => $classId,
            'user_id' => $user->id,
            'joined_at' => now(),
            'status' => 'active',
        ]);

        // Increment participants count
        $class->increment('current_participants');

        return response()->json([
            'message' => 'Joined class successfully',
            'meeting_link' => $class->meeting_link,
            'meeting_id' => $class->meeting_id,
            'password' => $class->password,
        ]);
    }

    /**
     * Leave a virtual class.
     */
    public function leave(Request $request, $classId)
    {
        $user = $request->user();
        $class = VirtualClass::findOrFail($classId);

        // Decrement participants count (simplified - in real app, track actual participants)
        if ($class->current_participants > 0) {
            $class->decrement('current_participants');
        }

        return response()->json([
            'message' => 'Left class successfully'
        ]);
    }

    /**
     * Get user's virtual classes.
     */
    public function myClasses(Request $request)
    {
        $user = $request->user();

        $classes = VirtualClass::where('instructor_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->paginate(12);

        return response()->json([
            'data' => $classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'description' => $class->description,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'duration_minutes' => $class->duration_minutes,
                    'status' => $class->status,
                    'current_participants' => $class->current_participants ?? 0,
                    'max_participants' => $class->max_participants,
                    'created_at' => $class->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ]
        ]);
    }
}
