<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForumAPIController extends Controller
{
    /**
     * Display a listing of forums.
     */
    public function index(Request $request)
    {
        $query = Forum::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $forums = $query->paginate(12);

        return response()->json([
            'data' => $forums->map(function ($forum) {
                return [
                    'id' => $forum->id,
                    'title' => $forum->title,
                    'content' => $forum->content,
                    'user' => $forum->user,
                    'category' => $forum->category ?? 'general',
                    'view_count' => $forum->views_count ?? 0,
                    'reply_count' => 0, // Will implement later
                    'like_count' => 0, // Not implemented yet
                    'created_at' => $forum->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $forums->currentPage(),
                'last_page' => $forums->lastPage(),
                'per_page' => $forums->perPage(),
                'total' => $forums->total(),
            ]
        ]);
    }

    /**
     * Display the specified forum.
     */
    public function show($id)
    {
        $forum = Forum::findOrFail($id);

        return response()->json([
            'id' => $forum->id,
            'title' => $forum->title,
            'content' => $forum->content,
            'course_id' => $forum->course_id,
            'user_id' => $forum->user_id,
            'category' => $forum->category,
            'is_pinned' => $forum->is_pinned,
            'is_locked' => $forum->is_locked,
            'views_count' => $forum->views_count,
            'replies_count' => $forum->replies_count(),
            'created_at' => $forum->created_at,
            'updated_at' => $forum->updated_at,
        ]);
    }

    /**
     * Store a newly created forum.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'category' => 'required|string|max:100',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $forum = Forum::create([
            'title' => $request->title,
            'content' => $request->content,
            'course_id' => $request->course_id,
            'user_id' => $request->user()->id,
            'category' => $request->category,
            'is_pinned' => $request->is_pinned ?? false,
            'is_locked' => $request->is_locked ?? false,
        ]);

        return response()->json([
            'message' => 'Forum created successfully',
            'forum' => $forum
        ], 201);
    }

    /**
     * Update the specified forum.
     */
    public function update(Request $request, $id)
    {
        $forum = Forum::findOrFail($id);

        // Check if user owns this forum or is admin
        if ($forum->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $forum->update($request->all());

        return response()->json([
            'message' => 'Forum updated successfully',
            'forum' => $forum
        ]);
    }

    /**
     * Remove the specified forum.
     */
    public function destroy(Request $request, $id)
    {
        $forum = Forum::findOrFail($id);

        // Check if user owns this forum or is admin
        if ($forum->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $forum->delete();

        return response()->json([
            'message' => 'Forum deleted successfully'
        ]);
    }

    /**
     * Get replies for a forum.
     */
    public function replies($forumId)
    {
        $forum = Forum::findOrFail($forumId);
        
        $replies = ForumReply::where('forum_id', $forumId)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json([
            'data' => $replies->items(),
            'pagination' => [
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
            ]
        ]);
    }

    /**
     * Reply to a forum.
     */
    public function reply(Request $request, $forumId)
    {
        $forum = Forum::findOrFail($forumId);

        // Check if forum is locked
        if ($forum->is_locked) {
            return response()->json([
                'message' => 'Forum is locked for new replies'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reply = ForumReply::create([
            'forum_id' => $forumId,
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Reply added successfully',
            'reply' => $reply
        ], 201);
    }

    /**
     * Update a reply.
     */
    public function updateReply(Request $request, $replyId)
    {
        $reply = ForumReply::findOrFail($replyId);

        // Check if user owns this reply
        if ($reply->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reply->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Reply updated successfully',
            'reply' => $reply
        ]);
    }

    /**
     * Delete a reply.
     */
    public function deleteReply(Request $request, $replyId)
    {
        $reply = ForumReply::findOrFail($replyId);

        // Check if user owns this reply or is forum owner
        if ($reply->user_id !== $request->user()->id && $reply->forum->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $reply->delete();

        return response()->json([
            'message' => 'Reply deleted successfully'
        ]);
    }
}
