<?php

use App\Models\Course;

echo "Checking courses...\n";
$courses = Course::count();
echo "Total courses: " . $courses . "\n";

$first = Course::first();
if($first) {
    echo "First course: " . $first->title . "\n";
    echo "Has featured column: " . (isset($first->is_featured) ? 'Yes' : 'No') . "\n";
    echo "Featured value: " . ($first->is_featured ?? 'NULL') . "\n";
} else {
    echo "No courses found\n";
}
