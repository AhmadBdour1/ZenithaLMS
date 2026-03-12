<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use App\Models\Ebook;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $itemType = $this->faker->randomElement(['course', 'ebook']);
        
        return [
            'user_id' => User::factory(),
            'course_id' => $itemType === 'course' ? Course::factory() : null,
            'ebook_id' => $itemType === 'ebook' ? Ebook::factory() : null,
            'payment_gateway' => $this->faker->randomElement(['stripe', 'paypal', 'wallet']),
            'transaction_id' => $this->faker->unique()->uuid(),
            'amount' => $this->faker->randomFloat(2, 9.99, 999.99),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'payment_data' => $this->faker->optional(0.7)->randomElements([
                'gateway_response' => $this->faker->paragraph(),
                'failure_reason' => $this->faker->optional(0.3)->sentence(),
                'refund_reason' => $this->faker->optional(0.2)->sentence(),
                'processing_time' => $this->faker->numberBetween(1, 300),
            ]),
            'paid_at' => $this->faker->optional(0.6)->dateTimeBetween('-6 months', 'now'),
        ];
    }
    
    /**
     * Create a completed payment
     */
    public function completed()
    {
        return $this->state([
            'status' => 'completed',
            'paid_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }
    
    /**
     * Create a pending payment
     */
    public function pending()
    {
        return $this->state([
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }
    
    /**
     * Create a failed payment
     */
    public function failed()
    {
        return $this->state([
            'status' => 'failed',
            'paid_at' => null,
            'payment_data' => array_merge(
                $this->faker->optional(0.7)->randomElements([
                    'gateway_response' => $this->faker->paragraph(),
                    'processing_time' => $this->faker->numberBetween(1, 300),
                ]),
                ['failure_reason' => $this->faker->sentence()]
            ),
        ]);
    }
    
    /**
     * Create a refunded payment
     */
    public function refunded()
    {
        return $this->state([
            'status' => 'refunded',
            'paid_at' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
            'payment_data' => array_merge(
                $this->faker->optional(0.7)->randomElements([
                    'gateway_response' => $this->faker->paragraph(),
                    'processing_time' => $this->faker->numberBetween(1, 300),
                ]),
                ['refund_reason' => $this->faker->sentence()]
            ),
        ]);
    }
    
    /**
     * Create a course payment
     */
    public function forCourse()
    {
        return $this->state([
            'course_id' => Course::factory(),
            'ebook_id' => null,
        ]);
    }
    
    /**
     * Create an ebook payment
     */
    public function forEbook()
    {
        return $this->state([
            'course_id' => null,
            'ebook_id' => Ebook::factory(),
        ]);
    }
    
    /**
     * Create a Stripe payment
     */
    public function stripe()
    {
        return $this->state([
            'payment_gateway' => 'stripe',
        ]);
    }
    
    /**
     * Create a PayPal payment
     */
    public function paypal()
    {
        return $this->state([
            'payment_gateway' => 'paypal',
        ]);
    }
    
    /**
     * Create a wallet payment
     */
    public function wallet()
    {
        return $this->state([
            'payment_gateway' => 'wallet',
        ]);
    }
}
