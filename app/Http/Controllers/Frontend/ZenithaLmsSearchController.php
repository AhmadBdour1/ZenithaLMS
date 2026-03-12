<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Course;
use App\Models\Ebook;
use App\Models\Blog;
use Illuminate\Http\Request;

class ZenithaLmsSearchController
{
    /**
     * Perform global search across courses, ebooks, and blogs
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([
                'message' => 'Search query must be at least 2 characters',
                'results' => []
            ], 400);
        }
        
        $results = [];
        
        // Search courses
        if (config('zenithalms.features.courses', true)) {
            $courses = Course::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit(5)
                ->get(['id', 'title', 'slug', 'description', 'type']);
                
            $results['courses'] = $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'type' => 'course',
                    'url' => route('zenithalms.courses.show', $course->slug),
                    'slug' => $course->slug
                ];
            });
        }
        
        // Search ebooks
        if (config('zenithalms.features.ebooks', false)) {
            $ebooks = Ebook::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit(5)
                ->get(['id', 'title', 'slug', 'description']);
                
            $results['ebooks'] = $ebooks->map(function ($ebook) {
                return [
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'description' => $ebook->description,
                    'type' => 'ebook',
                    'url' => route('zenithalms.ebooks.show', $ebook->slug),
                    'slug' => $ebook->slug
                ];
            });
        }
        
        // Search blogs
        if (config('zenithalms.features.blog', true)) {
            $blogs = Blog::where('title', 'LIKE', "%{$query}%")
                ->orWhere('content', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit(5)
                ->get(['id', 'title', 'slug', 'excerpt']);
                
            $results['blogs'] = $blogs->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'description' => $blog->excerpt,
                    'type' => 'blog',
                    'url' => route('zenithalms.blog.show', $blog->slug),
                    'slug' => $blog->slug
                ];
            });
        }
        
        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => array_sum(array_map('count', $results))
        ]);
    }
}
