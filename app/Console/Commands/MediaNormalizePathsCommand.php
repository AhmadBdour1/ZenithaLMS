<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        // Normalize courses.thumbnail
        $this->normalizeCourseThumbnails();

        // Normalize courses.preview_video (only local paths)
        $this->normalizeCoursePreviewVideos();

        // Normalize ebooks.thumbnail
        $this->normalizeEbookThumbnails();

        // Normalize ebooks.file_path
        $this->normalizeEbookFilePaths();

        // Normalize users.avatar
        $this->normalizeUserAvatars();

        $this->info('Media path normalization completed!');
    }

    private function normalizeCourseThumbnails()
    {
        $this->info('Normalizing courses.thumbnail...');
        
        $courses = DB::table('courses')
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->get();

        $count = 0;
        foreach ($courses as $course) {
            $normalizedPath = $this->normalizePath($course->thumbnail);
            if ($normalizedPath !== $course->thumbnail) {
                DB::table('courses')
                    ->where('id', $course->id)
                    ->update(['thumbnail' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} course thumbnail paths");
    }

    private function normalizeCoursePreviewVideos()
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
                $normalizedPath = $this->normalizePath($course->preview_video);
                if ($normalizedPath !== $course->preview_video) {
                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update(['preview_video' => $normalizedPath]);
                    $count++;
                }
            }
        }

        $this->info("Updated {$count} course preview video paths");
    }

    private function normalizeEbookThumbnails()
    {
        $this->info('Normalizing ebooks.thumbnail...');
        
        $ebooks = DB::table('ebooks')
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->get();

        $count = 0;
        foreach ($ebooks as $ebook) {
            $normalizedPath = $this->normalizePath($ebook->thumbnail);
            if ($normalizedPath !== $ebook->thumbnail) {
                DB::table('ebooks')
                    ->where('id', $ebook->id)
                    ->update(['thumbnail' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} ebook thumbnail paths");
    }

    private function normalizeEbookFilePaths()
    {
        $this->info('Normalizing ebooks.file_path...');
        
        $ebooks = DB::table('ebooks')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get();

        $count = 0;
        foreach ($ebooks as $ebook) {
            $normalizedPath = $this->normalizePath($ebook->file_path);
            if ($normalizedPath !== $ebook->file_path) {
                DB::table('ebooks')
                    ->where('id', $ebook->id)
                    ->update(['file_path' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} ebook file paths");
    }

    private function normalizeUserAvatars()
    {
        $this->info('Normalizing users.avatar...');
        
        $users = DB::table('users')
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $normalizedPath = $this->normalizePath($user->avatar);
            if ($normalizedPath !== $user->avatar) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['avatar' => $normalizedPath]);
                $count++;
            }
        }

        $this->info("Updated {$count} user avatar paths");
    }

    private function normalizePath(string $path): string
    {
        // Remove leading "storage/"
        if (str_starts_with($path, 'storage/')) {
            return substr($path, 9);
        }

        // Handle URLs with "/storage/" - extract path after /storage/
        if (str_contains($path, '/storage/')) {
            $parts = explode('/storage/', $path);
            if (isset($parts[1])) {
                // Remove query parameters if any
                $pathPart = explode('?', $parts[1])[0];
                return $pathPart;
            }
        }

        return $path;
    }

    private function isLocalPath(string $path): bool
    {
        // Check if it's a local storage path (not external URL)
        return !filter_var($path, FILTER_VALIDATE_URL) || 
               str_starts_with($path, 'http') && str_contains($path, '/storage/');
    }
}
