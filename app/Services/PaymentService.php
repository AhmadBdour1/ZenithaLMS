<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    private WalletService $walletService;
    private CouponService $couponService;

    public function __construct(WalletService $walletService, CouponService $couponService)
    {
        $this->walletService = $walletService;
        $this->couponService = $couponService;
    }

    /**
     * Process course enrollment payment
     */
    public function processCoursePayment(User $user, int $courseId, array $paymentData): array
    {
        $course = Course::findOrFail($courseId);

        // Check if course is free
        if ($course->is_free) {
            return $this->processFreeEnrollment($user, $course);
        }

        // Check if already enrolled
        if ($user->enrollments()->where('course_id', $courseId)->exists()) {
            return [
                'success' => false,
                'message' => 'Already enrolled in this course',
            ];
        }

        $amount = $course->price;
        $finalAmount = $amount;

        // Apply coupon if provided
        if (!empty($paymentData['coupon_code'])) {
            $couponResult = $this->couponService->applyCoupon($paymentData['coupon_code'], $amount);
            if ($couponResult['valid']) {
                $finalAmount = $couponResult['final_amount'];
            }
        }

        // Process payment based on method
        $paymentMethod = $paymentData['payment_method'] ?? 'wallet';

        if ($paymentMethod === 'wallet') {
            return $this->processWalletPayment($user, $finalAmount, $course, 'course_enrollment');
        } else {
            return $this->processExternalPayment($user, $finalAmount, $course, $paymentData, 'course_enrollment');
        }
    }

    /**
     * Process ebook purchase payment
     */
    public function processEbookPayment(User $user, int $ebookId, array $paymentData): array
    {
        $ebook = Ebook::findOrFail($ebookId);

        // Check if ebook is free
        if ($ebook->is_free) {
            return $this->processFreeEbookAccess($user, $ebook);
        }

        // Check if already has access
        if ($ebook->accesses()->where('user_id', $user->id)->exists()) {
            return [
                'success' => false,
                'message' => 'Already have access to this ebook',
            ];
        }

        $amount = $ebook->price;
        $finalAmount = $amount;

        // Apply coupon if provided
        if (!empty($paymentData['coupon_code'])) {
            $couponResult = $this->couponService->applyCoupon($paymentData['coupon_code'], $amount);
            if ($couponResult['valid']) {
                $finalAmount = $couponResult['final_amount'];
            }
        }

        // Process payment based on method
        $paymentMethod = $paymentData['payment_method'] ?? 'wallet';

        if ($paymentMethod === 'wallet') {
            return $this->processWalletPayment($user, $finalAmount, $ebook, 'ebook_purchase');
        } else {
            return $this->processExternalPayment($user, $finalAmount, $ebook, $paymentData, 'ebook_purchase');
        }
    }

    /**
     * Process free course enrollment
     */
    private function processFreeEnrollment(User $user, Course $course): array
    {
        // Check if already enrolled
        if ($user->enrollments()->where('course_id', $course->id)->exists()) {
            return [
                'success' => false,
                'message' => 'Already enrolled in this course',
            ];
        }

        // Create enrollment
        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Enrolled successfully in free course',
            'payment_id' => null,
        ];
    }

    /**
     * Process free ebook access
     */
    private function processFreeEbookAccess(User $user, Ebook $ebook): array
    {
        // Check if already has access
        if ($ebook->accesses()->where('user_id', $user->id)->exists()) {
            return [
                'success' => false,
                'message' => 'Already have access to this ebook',
            ];
        }

        // Create access record
        $ebook->accesses()->create([
            'user_id' => $user->id,
            'download_count' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Access granted to free ebook',
            'payment_id' => null,
        ];
    }

    /**
     * Process wallet payment
     */
    private function processWalletPayment(User $user, float $amount, $item, string $type): array
    {
        // Check wallet balance
        if (!$this->walletService->hasSufficientFunds($user, $amount)) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ];
        }

        // Process wallet payment
        $paymentResult = $this->walletService->processPayment($user, $amount, "Payment for {$type}: " . $item->title);

        if (!$paymentResult['success']) {
            return $paymentResult;
        }

        // Create payment record
        $payment = $this->createPaymentRecord($user, $amount, $item, $type, 'wallet', 'completed');

        // Grant access
        $this->grantAccess($user, $item, $type);

        return [
            'success' => true,
            'message' => 'Payment processed successfully using wallet',
            'payment' => $payment,
            'wallet_balance' => $paymentResult['new_balance'],
        ];
    }

    /**
     * Process external payment
     */
    private function processExternalPayment(User $user, float $amount, $item, array $paymentData, string $type): array
    {
        // Create pending payment record
        $payment = $this->createPaymentRecord($user, $amount, $item, $type, $paymentData['payment_method'], 'pending', $paymentData);

        // Simulate payment processing
        $this->simulatePaymentProcessing($payment);

        return [
            'success' => true,
            'message' => 'Payment initiated successfully',
            'payment' => $payment,
        ];
    }

    /**
     * Create payment record
     */
    private function createPaymentRecord(User $user, float $amount, $item, string $type, string $method, string $status, array $paymentData = []): Payment
    {
        $paymentData = [
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'USD',
            'type' => $type,
            'status' => $status,
            'payment_method' => $method,
            'payment_gateway' => 'wallet', // Default to wallet for wallet payments
            'transaction_id' => 'txn_' . uniqid(),
            'payment_data' => $paymentData,
        ];

        // Set item-specific fields
        if ($type === 'course_enrollment' && $item instanceof Course) {
            $paymentData['course_id'] = $item->id;
        } elseif ($type === 'ebook_purchase' && $item instanceof Ebook) {
            $paymentData['ebook_id'] = $item->id;
        }

        return Payment::create($paymentData);
    }

    /**
     * Grant access to item
     */
    private function grantAccess(User $user, $item, string $type): void
    {
        if ($type === 'course_enrollment' && $item instanceof Course) {
            // Create enrollment if not exists
            if (!$user->enrollments()->where('course_id', $item->id)->exists()) {
                $user->enrollments()->create([
                    'course_id' => $item->id,
                    'organization_id' => $item->organization_id ?? $user->organization_id ?? 1,
                    'status' => 'active',
                    'progress_percentage' => 0,
                    'enrolled_at' => now(),
                ]);
            }
        } elseif ($type === 'ebook_purchase' && $item instanceof Ebook) {
            // Create access if not exists
            if (!$item->accesses()->where('user_id', $user->id)->exists()) {
                $item->accesses()->create([
                    'user_id' => $user->id,
                    'download_count' => 0,
                ]);
            }
        }
    }

    /**
     * Simulate payment processing
     */
    private function simulatePaymentProcessing(Payment $payment): void
    {
        // Simulate payment processing (90% success rate)
        $success = rand(1, 100) > 10;

        if ($success) {
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'gateway_transaction_id' => 'txn_' . uniqid(),
            ]);

            // Grant access
            $this->grantAccessAfterPayment($payment);
        } else {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);
        }
    }

    /**
     * Grant access after successful payment
     */
    private function grantAccessAfterPayment(Payment $payment): void
    {
        $user = $payment->user;

        if ($payment->course_id) {
            $course = Course::find($payment->course_id);
            if ($course) {
                $this->grantAccess($user, $course, 'course_enrollment');
            }
        } elseif ($payment->ebook_id) {
            $ebook = Ebook::find($payment->ebook_id);
            if ($ebook) {
                $this->grantAccess($user, $ebook, 'ebook_purchase');
            }
        }
    }

    /**
     * Get user's payment history
     */
    public function getPaymentHistory(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->payments()->with(['course', 'ebook', 'paymentGateway'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(User $user): array
    {
        $payments = $user->payments();

        return [
            'total_payments' => $payments->count(),
            'successful_payments' => $payments->where('status', 'completed')->count(),
            'failed_payments' => $payments->where('status', 'failed')->count(),
            'total_spent' => $payments->where('status', 'completed')->sum('amount'),
            'last_payment' => $payments->latest()->first(),
        ];
    }
}
