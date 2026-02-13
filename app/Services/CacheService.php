<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    /**
     * Cache durations in seconds
     */
    const CACHE_DURATIONS = [
        'short' => 300,      // 5 minutes
        'medium' => 1800,    // 30 minutes
        'long' => 3600,      // 1 hour
        'very_long' => 86400, // 24 hours
    ];

    /**
     * Get cached data or store it
     */
    public static function remember(string $key, $duration, $callback)
    {
        return Cache::remember($key, self::CACHE_DURATIONS[$duration] ?? $duration, $callback);
    }

    /**
     * Get cached data or store it forever
     */
    public static function rememberForever(string $key, $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Clear cache by key
     */
    public static function forget(string $key)
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache by pattern
     */
    public static function forgetPattern(string $pattern)
    {
        if (method_exists(Cache::getStore(), 'getPrefix')) {
            $prefix = Cache::getStore()->getPrefix();
            $pattern = $prefix . $pattern;
        }
        
        return Cache::flush(); // For simplicity, flush all cache
    }

    /**
     * Cache course data
     */
    public static function cacheCourse($slug, $duration = 'medium')
    {
        return self::remember("course_slug_{$slug}", $duration, function () use ($slug) {
            return \App\Models\Course::with([
                'instructor', 
                'category', 
                'lessons' => function ($query) {
                    $query->where('is_published', true)->orderBy('sort_order');
                }, 
                'assessments'
            ])->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        });
    }

    /**
     * Cache courses list
     */
    public static function cacheCoursesList($filters = [], $duration = 'short')
    {
        $key = 'courses_list_' . md5(serialize($filters));
        
        return self::remember($key, $duration, function () use ($filters) {
            $query = \App\Models\Course::with(['instructor', 'category'])
                ->where('is_published', true)
                ->orderBy('created_at', 'desc');

            // Apply filters
            if (isset($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }
            if (isset($filters['level'])) {
                $query->where('level', $filters['level']);
            }
            if (isset($filters['price_type'])) {
                if ($filters['price_type'] === 'free') {
                    $query->where('is_free', true);
                } elseif ($filters['price_type'] === 'paid') {
                    $query->where('is_free', false);
                }
            }
            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('title', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('what_you_will_learn', 'like', '%' . $filters['search'] . '%');
                });
            }

            return $query->paginate(12);
        });
    }

    /**
     * Cache categories
     */
    public static function cacheCategories($duration = 'very_long')
    {
        return self::remember('categories_active', $duration, function () {
            return \App\Models\Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Cache user courses
     */
    public static function cacheUserCourses($userId, $duration = 'medium')
    {
        return self::remember("user_courses_{$userId}", $duration, function () use ($userId) {
            return \App\Models\Enrollment::with([
                'course.instructor', 
                'course.category',
                'course.lessons' => function ($query) {
                    $query->where('is_published', true)->orderBy('sort_order');
                }
            ])
            ->where('user_id', $userId)
            ->orderBy('enrolled_at', 'desc')
            ->paginate(12);
        });
    }

    /**
     * Cache course statistics
     */
    public static function cacheCourseStats($courseId, $duration = 'short')
    {
        return self::remember("course_stats_{$courseId}", $duration, function () use ($courseId) {
            $course = \App\Models\Course::findOrFail($courseId);
            
            return [
                'total_students' => $course->activeEnrollments()->count(),
                'average_rating' => 0, // No rating system yet
                'total_reviews' => 0, // No review system yet
                'completion_rate' => $course->enrollments()->where('status', 'completed')->count() / max($course->enrollments()->count(), 1) * 100,
                'lessons_count' => $course->publishedLessons()->count(),
                'total_duration' => $course->publishedLessons()->sum('duration_minutes'),
            ];
        });
    }

    /**
     * Clear course-related cache
     */
    public static function clearCourseCache($slug = null)
    {
        if ($slug) {
            self::forget("course_slug_{$slug}");
            self::forget("course_stats_{$slug}");
        }
        
        // Clear courses list cache
        self::forgetPattern('courses_list_*');
    }

    /**
     * Clear user-related cache
     */
    public static function clearUserCache($userId = null)
    {
        if ($userId) {
            self::forget("user_courses_{$userId}");
        }
    }

    /**
     * Clear all application cache
     */
    public static function clearAllCache()
    {
        Cache::flush();
    }
}
