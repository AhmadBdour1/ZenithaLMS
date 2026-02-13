<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use App\Models\CertificateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'certificate_template_id' => CertificateTemplate::factory(),
            'certificate_number' => 'CERT-' . $this->faker->unique()->numerify('##########'),
            'issued_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+5 years'),
            'certificate_url' => $this->faker->url(),
            'verification_code' => $this->faker->unique()->bothify('??????-######'),
            'certificate_data' => [
                'grade' => $this->faker->randomElement(['A', 'B', 'C', 'Pass']),
                'score' => $this->faker->randomFloat(2, 60, 100),
                'completion_date' => $this->faker->date(),
                'instructor_name' => $this->faker->name(),
                'institution' => $this->faker->company(),
                'signature_date' => $this->faker->date(),
            ],
        ];
    }
    
    /**
     * Create a valid certificate
     */
    public function valid()
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('now', '+5 years'),
        ]);
    }
    
    /**
     * Create an expired certificate
     */
    public function expired()
    {
        return $this->state([
            'expires_at' => $this->faker->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }
    
    /**
     * Create a certificate without expiration
     */
    public function lifetime()
    {
        return $this->state([
            'expires_at' => null,
        ]);
    }
}
