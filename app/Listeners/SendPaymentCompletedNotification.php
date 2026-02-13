<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\User;
use App\Models\Course;
use App\Models\Notification;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    
    /**
     * Handle the event.
     *
     * @param  \App\Events\PaymentCompleted  $event
     * @return void
     */
    public function handle(PaymentCompleted $event)
    {
        $payment = $event->payment;
        $user = $payment->user;
        
        try {
            // Send in-app notification
            $this->sendInAppNotification($user, $payment);
            
            // Send email notification
            $this->sendEmailNotification($user, $payment);
            
            // Update user statistics
            $this->updateUserStatistics($user, $payment);
            
            // Update course statistics
            $this->updateCourseStatistics($payment);
            
            // Trigger webhook if configured
            $this->triggerWebhook($payment);
            
            Log::info('Payment completed notification sent successfully', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send payment completed notification: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ]);
        }
    }
    
    /**
     * Send in-app notification
     */
    private function sendInAppNotification($user, $payment)
    {
        $title = 'Payment Successful';
        $message = 'Your payment has been processed successfully.';
        
        if ($payment->type === 'course_enrollment') {
            $course = $payment->course;
            $title = 'Course Enrolled!';
            $message = 'You have successfully enrolled in ' . $course->title . '. Start learning today!';
        }
        
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'action_url' => $this->getActionUrl($payment),
                'action_button' => $this->getActionButton($payment),
            ],
        ]);
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($user, $payment)
    {
        $template = $this->getEmailTemplate($payment);
        
        if (!$template) {
            $this->sendDefaultEmail($user, $payment);
            return;
        }
        
        $subject = $this->renderTemplate($template->subject_template, $user, $payment);
        $content = $this->renderTemplate($template->content_template, $user, $payment);
        
        try {
            Mail::send($user->email, new \App\Mail\ZenithaLmsNotification($subject, $content, [
                'payment' => $payment,
                'user' => $user,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to send payment completed email: ' . $e->getMessage());
        }
    }
    
    /**
     * Send default email
     */
    private function sendDefaultEmail($user, $payment)
    {
        $subject = 'Payment Successful - ZenithaLMS';
        $content = "Dear {$user->name},\n\n";
        $content .= "Your payment has been processed successfully.\n\n";
        $content .= "Payment Details:\n";
        $content .= "Amount: {$payment->currency} {$payment->amount}\n";
        $content .= "Type: {$payment->type}\n";
        $content .= "Status: {$payment->status}\n";
        $content .= "Date: {$payment->completed_at->format('M d, Y g:i A')}\n\n";
        
        if ($payment->course) {
            $content .= "Course: {$payment->course->title}\n";
            $content .= "You can now access your course from your dashboard.\n\n";
        }
        
        $content .= "Thank you for choosing ZenithaLMS!\n";
        $content .= config('app.name') . " Team";
        
        try {
            Mail::send($user->email, new \App\Mail\ZenithaLmsNotification($subject, $content, [
                'payment' => $payment,
                'user' => $user,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to send default payment email: ' . $e->getMessage());
        }
    }
    
    /**
     * Update user statistics
     */
    private function updateUserStatistics($user, $payment)
    {
        // Update user's total spending
        $user->update([
            'total_spent' => $user->total_spent + $payment->amount,
            'last_payment_at' => now(),
        ]);
        
        // Update user's payment count
        $user->increment('payment_count');
        
        // Update user's course count if course enrollment
        if ($payment->type === 'course_enrollment') {
            $user->increment('course_count');
        }
    }
    
    /**
     * Update course statistics
     */
    private function updateCourseStatistics($payment)
    {
        if (!$payment->course) {
            return;
        }
        
        $course = $payment->course;
        
        // Update course revenue
        $course->update([
            'total_revenue' => $course->total_revenue + $payment->amount,
            'enrollment_count' => $course->enrollment_count + 1,
        ]);
        
        // Update course popularity score
        $course->increment('popularity_score');
    }
    
    /**
     * Trigger webhook
     */
    private function triggerWebhook($payment)
    {
        $webhookUrl = config('zenithalms.webhooks.payment_completed');
        
        if (!$webhookUrl) {
            return;
        }
        
        $payload = [
            'event' => 'payment.completed',
            'data' => [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'type' => $payment->type,
                'status' => $payment->status,
                'course_id' => $payment->course_id,
                'completed_at' => $payment->completed_at->toISOString(),
                'gateway' => $payment->paymentGateway->name ?? 'Unknown',
                'user' => [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'email' => $payment->user->email,
                ],
            ],
            'timestamp' => now()->toISOString(),
        ];
        
        try {
            \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ZenithaLMS-Webhook/1.0',
                ],
                'timeout' => 10,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to trigger payment completed webhook: ' . $e->getMessage());
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($payment)
    {
        $templateType = $payment->type === 'course_enrollment' ? 'course_enrollment' : 'payment_completed';
        
        return EmailTemplate::where('type', $templateType)
            ->where('channel', 'email')
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Render template with data
     */
    private function renderTemplate($template, $user, $payment)
    {
        $variables = [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'payment_amount' => $payment->amount,
            'payment_currency' => $payment->currency,
            'payment_type' => $payment->type,
            'payment_status' => $payment->status,
            'payment_date' => $payment->completed_at->format('M d, Y g:i A'),
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
        ];
        
        if ($payment->course) {
            $variables['course_title'] = $payment->course->title;
            $variables['course_description'] = $payment->course->description;
            $variables['course_url'] = route('zenithalms.courses.show', $payment->course->slug);
            $variables['instructor_name'] = $payment->course->instructor->name;
        }
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Get action URL based on payment type
     */
    private function getActionUrl($payment)
    {
        if ($payment->type === 'course_enrollment' && $payment->course) {
            return route('zenithalms.courses.show', $payment->course->slug);
        }
        
        return route('zenithalms.payments.index');
    }
    
    /**
     * Get action button text based on payment type
     */
    private function getActionButton($payment)
    {
        if ($payment->type === 'course_enrollment' && $payment->course) {
            return 'View Course';
        }
        
        return 'View Payments';
    }
}
