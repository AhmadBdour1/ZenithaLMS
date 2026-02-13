<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Blog;
use App\Models\Notification;
use App\Models\NewsletterSubscriber;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZenithaLmsWebhookController extends Controller
{
    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');
        
        // Verify webhook signature
        $event = null;
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 400);
        }
        
        // Handle different webhook events
        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSuccess($event->data->object);
                
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailure($event->data->object);
                
            case 'customer.subscription.created':
                return $this->handleSubscriptionCreated($event->data->object);
                
            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($event->data->object);
                
            case 'invoice.payment_succeeded':
                return $this->handleInvoicePaymentSuccess($event->data->object);
                
            case 'invoice.payment_failed':
                return $this->handleInvoicePaymentFailure($event->data->object);
                
            default:
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook received',
                    'event' => $event->type,
                ]);
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function paypal(Request $request)
    {
        $payload = $request->getContent();
        
        // Verify webhook signature (simplified)
        $webhookId = $request->header('paypal-webhook-id');
        
        if (!$webhookId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing webhook ID',
            ], 400);
        }
        
        // Parse PayPal webhook
        $data = json_decode($payload, true);
        
        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload',
            ], 400);
        }
        
        $eventType = $data['event_type'] ?? null;
        
        // Handle different webhook events
        switch ($eventType) {
            case 'PAYMENT_SALE_COMPLETED':
                return $this->handlePayPalPaymentSuccess($data);
                
            case 'BILLING_SUBSCRIPTION_CREATED':
                return $this->handlePayPalSubscriptionCreated($data);
                
            case 'BILLING_SUBSCRIPTION_CANCELLED':
                return $this->handlePayPalSubscriptionCancelled($data);
                
            case 'BILLING_PLAN_ACTIVATED':
                return $this->handlePayPalPlanActivated($data);
                
            case 'BILLING_PLAN_DEACTIVATED':
                return $this->handlePayPalPlanDeactivated($data);
                
            default:
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook received',
                    'event_type' => $eventType,
                ]);
        }
    }

    /**
     * Handle custom webhook
     */
    public function custom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string',
            'data' => 'required|array',
            'signature' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Verify signature (simplified)
        $expectedSignature = hash_hmac('sha256', json_encode($request->data), config('app.webhook_secret'));
        
        if (!hash_equals($request->signature, $expectedSignature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }
        
        // Handle custom webhook events
        switch ($request->event_type) {
            case 'user.created':
                return $this->handleUserCreated($request->data);
                
            case 'course.enrolled':
                return $this->handleCourseEnrolled($request->data);
                
            'course.completed':
                return $this->handleCourseCompleted($request->data);
                
            'quiz.submitted':
                return $this->handleQuizSubmitted($request->data);
                
            'certificate.issued':
                return $this->handleCertificateIssued($request->data);
                
            default:
                return response()->json([
                    'success' => true,
                    'message' => 'Custom webhook received',
                    'event_type' => $request->event_type,
                ]);
        }
    }

    /**
     * Handle Stripe payment success
     */
    private function handlePaymentSuccess($paymentIntent)
    {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        
        if (!$paymentId) {
            return response()->json([
                'success' => false,
                'message' => 'Payment ID not found in metadata',
            ], 400);
        }
        
        $payment = \App\Models\Payment::findOrFail($paymentId);
        
        // Update payment status
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'gateway_transaction_id' => $paymentIntent->id,
            'gateway_response' => $paymentIntent->toArray(),
        ]);
        
        // Grant access to course if applicable
        if ($payment->course_id) {
            $this->grantCourseAccess($payment->user_id, $payment->course_id);
        }
        
        // Send notification
        $this->sendPaymentNotification($payment, 'success');
        
        // Trigger webhook event
        event(new \App\Events\PaymentCompleted($payment));
        
        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Handle Stripe payment failure
     */
    private function handlePaymentFailure($paymentIntent)
    {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        
        if (!$paymentId) {
            return response()->json([
                'success' => false,
                'message' => 'Payment ID not found in metadata',
            ], 400);
        }
        
        $payment = \App\Models\Payment::findOrFail($paymentId);
        
        // Update payment status
        $payment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_transaction_id' => $paymentIntent->id,
            'gateway_response' => $paymentIntent->toArray(),
        ]);
        
        // Send notification
        $this->sendPaymentNotification($payment, 'failure');
        
        // Trigger webhook event
        event(new \App\Events\PaymentFailed($payment));
        
        return response()->json([
            'success' => true,
            'message' => 'Payment failed',
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Handle Stripe subscription created
     */
    private function handleSubscriptionCreated($subscription)
    {
        $userId = $subscription->metadata->user_id ?? null;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID not found in metadata',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        
        // Create or update subscription record
        $userSubscription = \App\Models\Subscription::updateOrCreate(
            ['user_id' => $userId, 'gateway' => 'stripe'],
            [
                'gateway_subscription_id' => $subscription->id,
                'status' => 'active',
                'plan_id' => $subscription->plan->id,
                'plan_name' => $subscription->plan->name,
                'amount' => $subscription->plan->amount,
                'currency' => $subscription->plan->currency,
                'interval' => $subscription->plan->interval,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_end' => $subscription->trial_end,
                'metadata' => $subscription->metadata->toArray(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Subscription Created',
            'message' => 'Your subscription has been created successfully.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'subscription_id' => $userSubscription->id,
                'action_url' => route('zenithalms.dashboard.subscriptions'),
                'action_button' => 'View Subscriptions',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\SubscriptionCreated($userSubscription));
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'subscription_id' => $userSubscription->id,
        ]);
    }

    /**
     * Handle Stripe subscription deleted
     */
    private function handleSubscriptionDeleted($subscription)
    {
        $userId = $subscription->metadata->user_id ?? null;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID not found in metadata',
            ], 400);
        }
        
        $userSubscription = \App\Models\Subscription::where('gateway', 'stripe')
            ->where('gateway_subscription_id', $subscription->id)
            ->where('user_id', $userId)
            ->first();
        
        if ($userSubscription) {
            $userSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'metadata' => $subscription->metadata->toArray(),
                'updated_at' => now(),
            ]);
            
            // Send notification
            Notification::create([
                'user_id' => $userId,
                'title' => 'Subscription Cancelled',
                'message' => 'Your subscription has been cancelled.',
                'type' => 'warning',
                'channel' => 'in_app',
                'notification_data' => [
                    'subscription_id' => $userSubscription->id,
                    'action_url' => route('zenithalms.dashboard.subscriptions'),
                    'action_button' => 'View Subscriptions',
                ],
            ]);
            
            // Trigger webhook event
            event(new \App\Events\SubscriptionCancelled($userSubscription));
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled',
        ]);
    }

    /**
     * Handle PayPal payment success
     */
    private function handlePayPalPaymentSuccess($data)
    {
        $paymentId = $data['resource']['id'] ?? null;
        
        if (!$paymentId) {
            return response()->json([
                'success' => false,
                'message' => 'Payment ID not found',
            ], 400);
        }
        
        $payment = \App\Models\Payment::where('gateway_transaction_id', $paymentId)->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }
        
        // Update payment status
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'gateway_response' => $data,
        ]);
        
        // Grant access to course if applicable
        if ($payment->course_id) {
            $this->grantCourseAccess($payment->user_id, $payment->course_id);
        }
        
        // Send notification
        $this->sendPaymentNotification($payment, 'success');
        
        // Trigger webhook event
        event(new \App\Events\PaymentCompleted($payment));
        
        return response()->json([
            'success' => true,
            'message' => 'PayPal payment processed successfully',
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Handle PayPal subscription created
     */
    private function handlePayPalSubscriptionCreated($data)
    {
        $userId = $data['resource']['custom_id'] ?? null;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        
        // Create or update subscription record
        $userSubscription = \App\Models\Subscription::updateOrCreate(
            ['user_id' => $userId, 'gateway' => 'paypal'],
            [
                'gateway_subscription_id' => $data['resource']['id'],
                'status' => 'active',
                'plan_id' => $data['resource']['plan_id'],
                'plan_name' => $data['resource']['name'],
                'amount' => $data['resource']['amount']['total'],
                'currency' => $data['resource']['amount']['currency'],
                'interval' => $data['resource']['billing_cycle'],
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(30),
                'metadata' => $data,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'PayPal Subscription Created',
            'message' => 'Your PayPal subscription has been created successfully.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'subscription_id' => $userSubscription->id,
                'action_url' => route('zenithalms.dashboard.subscriptions'),
                'action_button' => 'View Subscriptions',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\SubscriptionCreated($userSubscription));
        
        return response()->json([
            'success' => true,
            'message' => 'PayPal subscription created successfully',
            'subscription_id' => $userSubscription->id,
        ]);
    }

    /**
     * Handle PayPal subscription cancelled
     */
    private function handlePayPalSubscriptionCancelled($data)
    {
        $userId = $data['resource']['custom_id'] ?? null;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID not found',
            ], 400);
        }
        
        $userSubscription = \App\Models\Subscription::where('gateway', 'paypal')
            ->where('gateway_subscription_id', $data['resource']['id'])
            ->where('user_id', $userId)
            ->first();
        
        if ($userSubscription) {
            $userSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'metadata' => $data,
                'updated_at' => now(),
            ]);
            
            // Send notification
            Notification::create([
                'user_id' => $userId,
                'title' => 'PayPal Subscription Cancelled',
                'message' => 'Your PayPal subscription has been cancelled.',
                'type' => 'warning',
                'channel' => 'in_app',
                'notification_data' => [
                    'subscription_id' => $userSubscription->id,
                    'action_url' => route('zenithalms.dashboard.subscriptions'),
                    'action_button' => 'View Subscriptions',
                ],
            ]);
            
            // Trigger webhook event
            event(new \App\Events\SubscriptionCancelled($userSubscription));
        }
        
        return response()->json([
            'success' => true,
            'message' => 'PayPal subscription cancelled',
        ]);
    }

    /**
     * Handle user created webhook
     */
    private function handleUserCreated($data)
    {
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        
        // Send welcome notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Welcome to ZenithaLMS!',
            'message' => 'Thank you for joining ZenithaLMS. Start exploring our courses and features.',
            'type' => 'info',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.dashboard'),
                'action_button' => 'Get Started',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\UserRegistered($user));
        
        return response()->json([
            'success' => true,
            'message' => 'User created webhook processed',
            'user_id' => $userId,
        ]);
    }

    /**
     * Handle course enrolled webhook
     */
    private function handleCourseEnrolled($data)
    {
        $userId = $data['user_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        
        if (!$userId || !$courseId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID or Course ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Course Enrolled!',
            'message' => 'You have successfully enrolled in ' . $course->title . '. Start learning today!',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.courses.show', $course->slug),
                'action_button' => 'View Course',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\CourseEnrolled($user, $course));
        
        return response()->json([
            'success' => true,
            'message' => 'Course enrolled webhook processed',
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);
    }

    /**
     * Handle course completed webhook
     */
    private function handleCourseCompleted($data)
    {
        $userId = $data['user_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        
        if (!$userId || !$courseId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID or Course ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);
        
        // Update enrollment status
        $enrollment = $user->enrollments()->where('course_id', $courseId)->first();
        
        if ($enrollment) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'progress_percentage' => 100,
            ]);
        }
        
        // Create certificate if applicable
        $certificate = \App\Models\Certificate::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'title' => 'Certificate of Completion',
            'description' => 'This certifies that ' . $user->name . ' has successfully completed the course ' . $course->title,
            'certificate_number' => 'ZEN-' . date('Y') . '-' . str_pad($course->id, 4, '0', STR_PAD_LEFT) . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'verification_code' => uniqid(),
            'issued_at' => now(),
            'status' => 'active',
        ]);
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Course Completed!',
            'message' => 'Congratulations! You have completed ' . $course->title . '. Your certificate is ready.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.certificates.show', $certificate->certificate_number),
                'action_button' => 'View Certificate',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\CourseCompleted($user, $course, $certificate));
        
        return response()->json([
            'success' => true,
            'message' => 'Course completed webhook processed',
            'user_id' => $userId,
            'course_id' => $courseId,
            'certificate_id' => $certificate->id,
        ]);
    }

    /**
     * Handle quiz submitted webhook
     */
    private function handleQuizSubmitted($data)
    {
        $userId = $data['user_id'] ?? null;
        $quizId = $data['quiz_id'] ?? null;
        $attemptId = $data['attempt_id'] ?? null;
        
        if (!$userId || !$quizId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID or Quiz ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        $quiz = Quiz::findOrFail($quizId);
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Quiz Submitted!',
            'message' => 'Your quiz attempt for ' . $quiz->title . ' has been submitted.',
            'type' => 'info',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.quiz.result', $attemptId),
                'action_button' => 'View Results',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\QuizSubmitted($user, $quiz, $attemptId));
        
        return response()->json([
            'success' => true,
            'message' => 'Quiz submitted webhook processed',
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'attempt_id' => $attemptId,
        ]);
    }

    /**
     * Handle certificate issued webhook
     */
    private function handleCertificateIssued($data)
    {
        $userId = $data['user_id'] ?? null;
        $certificateId = $data['certificate_id'] ?? null;
        
        if (!$userId || !$certificateId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID or Certificate ID not found',
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        $certificate = \App\Models\Certificate::findOrFail($certificateId);
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Certificate Issued!',
            'message' => 'Your certificate for ' . $certificate->course->title . ' has been issued.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.certificates.show', $certificate->certificate_number),
                'action_button' => 'View Certificate',
            ],
        ]);
        
        // Trigger webhook event
        event(new \App\Events\CertificateIssued($user, $certificate));
        
        return response()->json([
            'success' => true,
            'message' => 'Certificate issued webhook processed',
            'user_id' => $userId,
            'certificate_id' => $certificateId,
        ]);
    }

    /**
     * Grant course access
     */
    private function grantCourseAccess($userId, $courseId)
    {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);
        
        // Check if already enrolled
        if (!$user->enrollments()->where('course_id', $courseId)->exists()) {
            $user->enrollments()->create([
                'course_id' => $courseId,
                'status' => 'active',
                'progress_percentage' => 0,
            ]);
        }
    }

    /**
     * Send payment notification
     */
    private function sendPaymentNotification($payment, $status)
    {
        $user = $payment->user;
        
        if ($status === 'success') {
            $title = 'Payment Successful';
            $message = 'Your payment has been processed successfully.';
        } else {
            $title = 'Payment Failed';
            $message = 'Your payment could not be processed. Please try again.';
        }
        
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $status === 'success' ? 'success' : 'error',
            'channel' => 'in_app',
            'notification_data' => [
                'payment_id' => $payment->id,
                'action_url' => route('zenithalms.payments.index'),
                'action_button' => 'View Payments',
            ],
        ]);
    }
}
