<?php

use App\Models\Course;
use Illuminate\Support\Str;

echo "Testing course slugs...\n";
$courses = Course::get();
foreach($courses as $course) {
    if(!$course->slug) {
        $course->slug = Str::slug($course->title);
        $course->save();
        echo "Updated slug for: " . $course->title . "\n";
    }
}
echo "Done!\n";
