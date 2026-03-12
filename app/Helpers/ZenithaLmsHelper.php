<?php

if (!function_exists('zenithalms_format_currency')) {
    /**
     * Format currency amount
     */
    function zenithalms_format_currency($amount, $currency = 'USD') {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('zenithalms_format_date')) {
    /**
     * Format date according to user preference
     */
    function zenithalms_format_date($date, $format = 'M d, Y') {
        if ($date instanceof \Carbon\Carbon) {
            return $date->format($format);
        }
        
        return date($format, strtotime($date));
    }
}

if (!function_exists('zenithalms_format_time')) {
    /**
     * Format time according to user preference
     */
    function zenithalms_format_time($time, $format = 'g:i A') {
        if ($time instanceof \Carbon\Carbon) {
            return $time->format($format);
        }
        
        return date($format, strtotime($time));
    }
}

if (!function_exists('zenithalms_format_datetime')) {
    /**
     * Format datetime according to user preference
     */
    function zenithalms_format_datetime($datetime, $format = 'M d, Y g:i A') {
        if ($datetime instanceof \Carbon\Carbon) {
            return $datetime->format($format);
        }
        
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('zenithalms_time_ago')) {
    /**
     * Get time ago string
     */
    function zenithalms_time_ago($datetime) {
        if ($datetime instanceof \Carbon\Carbon) {
            return $datetime->diffForHumans();
        }
        
        return \Carbon\Carbon::parse($datetime)->diffForHumans();
    }
}

if (!function_exists('zenithalms_slugify')) {
    /**
     * Generate URL-friendly slug
     */
    function zenithalms_slugify($string) {
        $slug = strtolower($string);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

if (!function_exists('zenithalms_generate_uuid')) {
    /**
     * Generate UUID
     */
    function zenithalms_generate_uuid() {
        return \Illuminate\Support\Str::uuid();
    }
}

if (!function_exists('zenithalms_generate_token')) {
    /**
     * Generate random token
     */
    function zenithalms_generate_token($length = 32) {
        return \Illuminate\Support\Str::random($length);
    }
}

if (!function_exists('zenithalms_encrypt_string')) {
    /**
     * Encrypt string
     */
    function zenithalms_encrypt_string($string) {
        return encrypt($string);
    }
}

if (!function_exists('zenithalms_decrypt_string')) {
    /**
     * Decrypt string
     */
    function zenithalms_decrypt_string($encryptedString) {
        try {
            return decrypt($encryptedString);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('zenithalms_validate_email')) {
    /**
     * Validate email address
     */
    function zenithalms_validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('zenithalms_validate_phone')) {
    /**
     * Validate phone number
     */
    function zenithalms_validate_phone($phone) {
        // Basic phone validation (can be enhanced)
        return preg_match('/^[+]?[1-9]\d{1,14}$/', $phone);
    }
}

if (!function_exists('zenithalms_format_file_size')) {
    /**
     * Format file size
     */
    function zenithalms_format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('zenithalms_get_user_avatar')) {
    /**
     * Get user avatar URL
     */
    function zenithalms_get_user_avatar($user, $size = 40) {
        if ($user->avatar) {
            return asset('storage/avatars/' . $user->avatar);
        }
        
        // Generate avatar from user's name
        $name = $user->name ?? 'User';
        $initials = strtoupper(substr($name, 0, 2));
        
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&color=7F9CFB&background=EBF4FF&size=" . $size;
    }
}

if (!function_exists('zenithalms_get_course_thumbnail')) {
    /**
     * Get course thumbnail URL
     */
    function zenithalms_get_course_thumbnail($course, $size = 'medium') {
        if ($course->thumbnail) {
            return asset('storage/courses/' . $course->thumbnail);
        }
        
        // Generate placeholder thumbnail
        $sizes = [
            'small' => 300,
            'medium' => 600,
            'large' => 1200,
        ];
        
        $width = $sizes[$size] ?? 600;
        $height = $width * 0.5625; // 16:9 aspect ratio
        
        return "https://picsum.photos/seed/{$course->id}/{$width}/{$height}.jpg";
    }
}

if (!function_exists('zenithalms_get_ebook_thumbnail')) {
    /**
     * Get ebook thumbnail URL
     */
    function zenithalms_get_ebook_thumbnail($ebook, $size = 'medium') {
        if ($ebook->thumbnail) {
            return asset('storage/ebooks/' . $ebook->thumbnail);
        }
        
        // Generate placeholder thumbnail
        $sizes = [
            'small' => 300,
            'medium' => 600,
            'large' => 1200,
        ];
        
        $width = $sizes[$size] ?? 600;
        $height = $width * 1.5; // 2:3 aspect ratio
        
        return "https://picsum.photos/seed/{$ebook->id}/{$width}/{$height}.jpg";
    }
}

if (!function_exists('zenithalms_calculate_progress')) {
    /**
     * Calculate progress percentage
     */
    function zenithalms_calculate_progress($completed, $total) {
        if ($total === 0) {
            return 0;
        }
        
        return min(100, round(($completed / $total) * 100));
    }
}

if (!function_exists('zenithalms_get_grade_letter')) {
    /**
     * Get grade letter from percentage
     */
    function zenithalms_get_grade_letter($percentage) {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        
        return 'F';
    }
}

if (!function_exists('zenithalms_get_grade_color')) {
    /**
     * Get grade color from percentage
     */
    function zenithalms_get_grade_color($percentage) {
        if ($percentage >= 90) return 'text-green-600';
        if ($percentage >= 80) return 'text-blue-600';
        if ($percentage >= 70) return 'text-yellow-600';
        if ($percentage >= 60) return 'text-orange-600';
        
        return 'text-red-600';
    }
}

if (!function_exists('zenithalms_is_weekend')) {
    /**
     * Check if today is weekend
     */
    function zenithalms_is_weekend() {
        return in_array(now()->dayOfWeek, [\Carbon\Carbon::SATURDAY, \Carbon\Carbon::SUNDAY]);
    }
}

if (!function_exists('zenithalms_get_greeting')) {
    /**
     * Get greeting based on time of day
     */
    function zenithalms_get_greeting() {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 17) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }
}

if (!function_exists('zenithalms_truncate_text')) {
    /**
     * Truncate text to specified length
     */
    function zenithalms_truncate_text($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('zenithalms_excerpt')) {
    /**
     * Get excerpt from text
     */
    function zenithalms_excerpt($text, $length = 150) {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('zenithalms_highlight_keywords')) {
    /**
     * Highlight keywords in text
     */
    function zenithalms_highlight_keywords($text, $keywords, $className = 'highlight') {
        if (empty($keywords)) {
            return $text;
        }
        
        if (!is_array($keywords)) {
            $keywords = [$keywords];
        }
        
        foreach ($keywords as $keyword) {
            $text = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span class="' . $className . '">$1</span>', $text);
        }
        
        return $text;
    }
}

if (!function_exists('zenithalms_generate_breadcrumb')) {
    /**
     * Generate breadcrumb array
     */
    function zenithalms_generate_breadcrumb($items) {
        $breadcrumb = [];
        
        foreach ($items as $item) {
            $breadcrumb[] = [
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'active' => $item['active'] ?? false,
            ];
        }
        
        return $breadcrumb;
    }
}

if (!function_exists('zenithalms_get_pagination_info')) {
    /**
     * Get pagination information
     */
    function zenithalms_get_pagination_info($paginator) {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more' => $paginator->hasMorePages(),
            'has_previous' => $paginator->hasPreviousPages(),
            'has_next' => $paginator->hasMorePages(),
        ];
    }
}

if (!function_exists('zenithalms_get_status_badge')) {
    /**
     * Get status badge HTML
     */
    function zenithalms_get_status_badge($status, $type = 'default') {
        $badges = [
            'active' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'failed' => 'bg-red-100 text-red-800',
            'success' => 'bg-green-100 text-green-800',
            'error' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'info' => 'bg-blue-100 text-blue-800',
        ];
        
        $colors = $badges[$status] ?? $badges[$type];
        
        return '<span class="px-3 py-1 ' . $colors . ' text-xs font-semibold rounded-full">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('zenithalms_get_priority_badge')) {
    /**
     * Get priority badge HTML
     */
    function zenithalms_get_priority_badge($priority) {
        $priorities = [
            'high' => 'bg-red-100 text-red-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-green-100 text-green-800',
        ];
        
        $color = $priorities[$priority] ?? $priorities['medium'];
        
        return '<span class="px-3 py-1 ' . $color . ' text-xs font-semibold rounded-full">' . ucfirst($priority) . '</span>';
    }
}

if (!function_exists('zenithalms_get_difficulty_badge')) {
    /**
     * Get difficulty badge HTML
     */
    function zenithalms_get_difficulty_badge($difficulty) {
        $difficulties = [
            'beginner' => 'bg-green-100 text-green-800',
            'intermediate' => 'bg-blue-100 text-blue-800',
            'advanced' => 'bg-red-100 text-red-800',
        ];
        
        $color = $difficulties[$difficulty] ?? $difficulties['intermediate'];
        
        return '<span class="px-3 py-1 ' . $color . ' text-xs font-semibold rounded-full">' . ucfirst($difficulty) . '</span>';
    }
}

if (!function_exists('zenithalms_format_duration')) {
    /**
     * Format duration in human readable format
     */
    function zenithalms_format_duration($seconds) {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        if ($seconds < 3600) {
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        
        $hours = floor($seconds / 3600);
        if ($seconds < 86400) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        
        $days = floor($seconds / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '');
    }
}

if (!function_exists('zenithalms_get_random_color')) {
    /**
     * Get random color from palette
     */
    function zenithalms_get_random_color() {
        $colors = [
            'bg-primary-500',
            'bg-accent-purple',
            'bg-green-500',
            'bg-blue-500',
            'bg-yellow-500',
            'bg-red-500',
            'bg-indigo-500',
            'bg-pink-500',
            'bg-purple-500',
            'bg-gray-500',
        ];
        
        return $colors[array_rand($colors)];
    }
}

if (!function_exists('zenithalms_get_chart_color')) {
    /**
     * Get chart color based on index
     */
    function zenithalms_get_chart_color($index) {
        $colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // yellow
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#14B8A6', // teal
            '#F97316', // orange
            '#6B7280', // gray
        ];
        
        return $colors[$index % count($colors)];
    }
}

if (!function_exists('zenithalms_is_json')) {
    /**
     * Check if string is valid JSON
     */
    function zenithalms_is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('zenithalms_safe_json_decode')) {
    /**
     * Safely decode JSON string
     */
    function zenithalms_safe_json_decode($string, $default = null) {
        $decoded = json_decode($string, true);
        
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }
}

if (!function_exists('zenithalms_array_to_csv')) {
    /**
     * Convert array to CSV string
     */
    function zenithalms_array_to_csv($array, $headers = []) {
        $csv = '';
        
        // Add headers if provided
        if (!empty($headers)) {
            $csv .= implode(',', $headers) . "\n";
        }
        
        // Add data rows
        foreach ($array as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }
        
        return $csv;
    }
}

if (!function_exists('zenithalms_csv_to_array')) {
    /**
     * Convert CSV string to array
     */
    function zenithalms_csv_to_array($csv, $hasHeaders = true) {
        $lines = explode("\n", $csv);
        $array = [];
        
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $array[] = $row;
        }
        
        if ($hasHeaders && !empty($array)) {
            $headers = array_shift($array);
            $array = array_map(function ($row) use ($headers) {
                return array_combine($headers, $row);
            }, $array);
        }
        
        return $array;
    }
}
