<?php

namespace Database\Factories;

use App\Models\VirtualClass;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class VirtualClassFactory extends Factory
{
    protected $model = VirtualClass::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'instructor_id' => User::factory(),
            'course_id' => Course::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'meeting_link' => $this->faker->url(),
            'meeting_id' => $this->faker->uuid(),
            'meeting_password' => $this->faker->password(6, 10),
            'max_participants' => $this->faker->numberBetween(10, 100),
            'is_active' => true,
            'is_recurring' => false,
            'recurrence_pattern' => null,
            'settings' => [],
        ];
    }
}
