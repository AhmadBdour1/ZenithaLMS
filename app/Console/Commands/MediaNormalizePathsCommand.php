<?php

namespace App\Console\Commands;

use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MediaNormalizePathsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:normalize-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize existing media paths in database to disk-relative format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting media path normalization...');

        $mediaService = new MediaService();
        
        // Track updates
        $updatedCourses = 0;
        $updatedEbooks = 0;
        $updatedUsers = 0;

        // Normalize courses.thumbnail
        $updatedCourses += $this->normalizeCourseThumbnails($mediaService);

        // Normalize courses.preview_video (only local paths)
        $updatedCourses += $this->normalizeCoursePreviewVideos($mediaService);

        // Normalize ebooks.thumbnail
        $updatedEbooks += $this->normalizeEbookThumbnails($mediaService);

        // Normalize ebooks.file_path
        $updatedEbooks += $this->normalizeEbookFilePaths($mediaService);

        // Normalize users.avatar
        $updatedUsers += $this->normalizeUserAvatars($mediaService);

        // Output final report
        $this->info('Media path normalization completed!');
        $this->info("updated_courses: {$updatedCourses}");
        $this->info("updated_ebooks: {$updatedEbooks}");
        $this->info("updated_users: {$updatedUsers}");
    }

    private function normalizeCourseThumbnails(MediaService $mediaService): int
    {
        $this->info('Normalizing courses.thumbnail...');
        
        $courses = DB::table('courses')
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->get();

        $count = 0;
        foreach ($courses as $course) {
            $normalizedPath = $mediaService->normalizePath($course->thumbnail);
            if ($normalizedPath !== $course->thumbnail) {
                DB::table('courses')
                    ->where('id', $course->id)
                    ->update(['thumbnail' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} course thumbnail paths");
        return $count;
    }

    private function normalizeCoursePreviewVideos(MediaService $mediaService): int
    {
        $this->info('Normalizing courses.preview_video (local paths only)...');
        
        $courses = DB::table('courses')
            ->whereNotNull('preview_video')
            ->where('preview_video', '!=', '')
            ->get();

        $count = 0;
        foreach ($courses as $course) {
            // Only normalize local paths, keep external URLs
            if ($this->isLocalPath($course->preview_video)) {
                $normalizedPath = $mediaService->normalizePath($course->preview_video);
                if ($normalizedPath !== $course->preview_video) {
                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update(['preview_video' => $normalizedPath]);
                    $count++;
                }
            }
        }

        $this->info("Updated {$count} course preview video paths");
        return $count;
    }

    private function normalizeEbookThumbnails(MediaService $mediaService): int
    {
        $this->info('Normalizing ebooks.thumbnail...');
        
        $ebooks = DB::table('ebooks')
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->get();

        $count = 0;
        foreach ($ebooks as $ebook) {
            $normalizedPath = $mediaService->normalizePath($ebook->thumbnail);
            if ($normalizedPath !== $ebook->thumbnail) {
                DB::table('ebooks')
                    ->where('id', $ebook->id)
                    ->update(['thumbnail' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} ebook thumbnail paths");
        return $count;
    }

    private function normalizeEbookFilePaths(MediaService $mediaService): int
    {
        $this->info('Normalizing ebooks.file_path...');
        
        $ebooks = DB::table('ebooks')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get();

        $count = 0;
        foreach ($ebooks as $ebook) {
            $normalizedPath = $mediaService->normalizePath($ebook->file_path);
            if ($normalizedPath !== $ebook->file_path) {
                DB::table('ebooks')
                    ->where('id', $ebook->id)
                    ->update(['file_path' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} ebook file paths");
        return $count;
    }

    private function normalizeUserAvatars(MediaService $mediaService): int
    {
        $this->info('Normalizing users.avatar...');
        
        $users = DB::table('users')
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $normalizedPath = $mediaService->normalizePath($user->avatar);
            if ($normalizedPath !== $user->avatar) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['avatar' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} user avatar paths");
        return $count;
    }

    private function isLocalPath(string $path): bool
    {
        // Check if it's a local storage path (not external URL)
        // Keep external URLs like youtube.com, vimeo.com, etc.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            // It's a URL, check if it's external (not local storage)
            $host = parse_url($path, PHP_URL_HOST);
            return $host === null || str_contains($path, '/storage/');
        }
        
        // Not a URL, treat as local path
        return true;
    }
}
