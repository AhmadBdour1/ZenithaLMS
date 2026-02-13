<?php

use App\Models\Course;
use Illuminate\Support\Str;

echo "Adding slugs to courses...\n";
$courses = Course::whereNull('slug')->get();
foreach($courses as $course) {
    $course->slug = Str::slug($course->title);
    $course->save();
    echo "Updated slug for: " . $course->title . "\n";
}
echo "Done!\n";
