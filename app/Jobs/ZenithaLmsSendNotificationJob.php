<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Notification;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchableJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ZenithaLmsSendNotificationJob implements ShouldQueue
{
    use DispatchableJobs, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [1, 5, 10];
    
    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;
    
    /**
     * Create a new job instance.
     *
     * @param  int  $userId
     * @param  string  $title
     * @param  string  $message
     * @param  string  $type
     * @param  string  $channel
     * @param  array  $data
     * @return void
     */
    public function __construct(
        private int $userId,
        private string $title,
        private string $message,
        private string $type,
        private string $channel,
        private array $data = []
    ) {
        $this->onQueue('notifications');
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $user = User::findOrFail($this->userId);
            
            // Create in-app notification
            if ($this->shouldCreateInAppNotification()) {
                $this->createInAppNotification($user);
            }
            
            // Send email notification
            if ($this->shouldSendEmailNotification()) {
                $this->sendEmailNotification($user);
            }
            
            // Send push notification
            if ($this->shouldSendPushNotification()) {
                $this->sendPushNotification($user);
            }
            
            // Send SMS notification
            if ($this->shouldSendSmsNotification()) {
                $this->sendSmsNotification($user);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage(), [
                'user_id' => $this->userId,
                'title' => $this->title,
                'type' => $this->type,
                'channel' => $this->channel,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error('Notification job failed: ' . $exception->getMessage(), [
            'user_id' => $this->userId,
            'title' => $this->title,
            'type' => $this->type,
            'channel' => $this->channel,
            'attempts' => $this->attempts(),
        ]);
        
        // Optionally notify admin about failed notification
        $this->notifyAdminOfFailure($exception);
    }
    
    /**
     * Create in-app notification
     */
    private function createInAppNotification($user)
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'channel' => 'in_app',
            'notification_data' => $this->data,
            'priority' => $this->calculatePriority(),
        ]);
        
        // Trigger real-time notification if user is online
        if ($this->isUserOnline($user)) {
            $this->sendRealTimeNotification($user, $notification);
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($user)
    {
        // Get email template
        $template = $this->getEmailTemplate();
        
        if (!$template) {
            // Use default email template
            $this->sendDefaultEmail($user);
            return;
        }
        
        // Render email content
        $subject = $this->renderTemplate($template->subject_template, $user);
        $content = $this->renderTemplate($template->content_template, $user);
        
        try {
            Mail::send($user->email, new \App\Mail\ZenithaLmsNotification($subject, $content, $this->data));
            
            Log::info('Email notification sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'title' => $this->title,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'title' => $this->title,
            ]);
        }
    }
    
    /**
     * Send push notification
     */
    private function sendPushNotification($user)
    {
        if (!$user->device_tokens || $user->device_tokens->isEmpty()) {
            return;
        }
        
        $deviceTokens = $user->device_tokens->pluck('token')->toArray();
        
        $payload = [
            'title' => $this->title,
            'body' => $this->message,
            'type' => $this->type,
            'data' => array_merge($this->data, [
                'notification_id' => $this->getNotificationId(),
                'user_id' => $user->id,
            ]),
        ];
        
        foreach ($deviceTokens as $token) {
            try {
                $this->sendPushToToken($token, $payload);
            } catch (\Exception $e) {
                Log::error('Failed to send push notification to token: ' . $e->getMessage(), [
                    'token' => $token,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
    
    /**
     * Send SMS notification
     */
    private function sendSmsNotification($user)
    {
        if (!$user->phone) {
            return;
        }
        
        $message = $this->formatSmsMessage();
        
        try {
            // Use SMS service (e.g., Twilio, Nexmo)
            $this->sendSms($user->phone, $message);
            
            Log::info('SMS notification sent successfully', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'title' => $this->title,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'title' => $this->title,
            ]);
        }
    }
    
    /**
     * Send default email
     */
    private function sendDefaultEmail($user)
    {
        $subject = $this->title;
        $content = $this->message;
        
        // Add user-specific data
        $content .= "\n\n" . "Sent to: " . $user->name;
        $content .= "\n" . "Email: " . $user->email;
        
        try {
            Mail::send($user->email, new \App\Mail\ZenithaLmsNotification($subject, $content, $this->data));
        } catch (\Exception $e) {
            Log::error('Failed to send default email: ' . $e->getMessage());
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate()
    {
        return EmailTemplate::where('type', $this->type)
            ->where('channel', 'email')
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Render template with user data
     */
    private function renderTemplate($template, $user)
    {
        $variables = array_merge($this->data, [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
        ]);
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Send push notification to specific token
     */
    private function sendPushToToken($token, $payload)
    {
        // Use FCM (Firebase Cloud Messaging)
        $fcmKey = config('services.fcm.key');
        
        if (!$fcmKey) {
            return;
        }
        
        $notification = [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'sound' => 'default',
                'badge' => '1',
            ],
            'data' => $payload['data'],
        ];
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'key=' . $fcmKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $notification);
        
        if (!$response->successful()) {
            throw new \Exception('FCM request failed: ' . $response->body());
        }
        
        return $response->json();
    }
    
    /**
     * Send SMS
     */
    private function sendSms($phone, $message)
    {
        // Use SMS service (e.g., Twilio)
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');
        
        if (!$sid || !$token || !$from) {
            return;
        }
        
        $client = new \Twilio\Rest\Client($sid, $token);
        
        $client->messages->create(
            $phone,
            $from,
            $message
        );
    }
    
    /**
     * Format SMS message
     */
    private function formatSmsMessage()
    {
        $message = $this->title . "\n\n" . $this->message;
        
        // Add signature
        $message .= "\n\n" . config('app.name');
        
        // Limit SMS length
        return substr($message, 0, 160);
    }
    
    /**
     * Send real-time notification
     */
    private function sendRealTimeNotification($user, $notification)
    {
        // Use WebSocket or real-time service
        // This would integrate with your real-time notification system
        broadcast(new \App\Events\NotificationSent($notification));
    }
    
    /**
     * Check if user is online
     */
    private function isUserOnline($user)
    {
        // Check if user has active session or WebSocket connection
        return $user->last_seen_at && $user->last_seen_at->diffInMinutes(now()) < 5;
    }
    
    /**
     * Calculate notification priority
     */
    private function calculatePriority()
    {
        switch ($this->type) {
            case 'alert':
            case 'error':
                return 'high';
            case 'warning':
                return 'medium';
            case 'success':
            case 'info':
            default:
                return 'low';
        }
    }
    
    /**
     * Get notification ID
     */
    private function getNotificationId()
    {
        return uniqid('notif_');
    }
    
    /**
     * Check if in-app notification should be created
     */
    private function shouldCreateInAppNotification()
    {
        return in_array($this->channel, ['in_app', 'all']);
    }
    
    /**
     * Check if email notification should be sent
     */
    private function shouldSendEmailNotification()
    {
        return in_array($this->channel, ['email', 'all']) && 
               config('zenithalms.notifications.email.enabled', true);
    }
    
    /**
     * Check if push notification should be sent
     */
    private function shouldSendPushNotification()
    {
        return in_array($this->channel, ['push', 'all']) && 
               config('zenithalms.notifications.push.enabled', true);
    }
    
    /**
     * Check if SMS notification should be sent
     */
    private function shouldSendSmsNotification()
    {
        return in_array($this->channel, ['sms', 'all']) && 
               config('zenithalms.notifications.sms.enabled', true);
    }
    
    /**
     * Notify admin of job failure
     */
    private function notifyAdminOfFailure($exception)
    {
        $adminEmail = config('zenithalms.notifications.admin_email');
        
        if (!$adminEmail) {
            return;
        }
        
        $subject = 'Notification Job Failed - ZenithaLMS';
        $content = "A notification job failed to process.\n\n";
        $content .= "Details:\n";
        $content .= "User ID: {$this->userId}\n";
        $content .= "Title: {$this->title}\n";
        $content .= "Type: {$this->type}\n";
        $content .= "Channel: {$this->channel}\n";
        $content .= "Attempts: {$this->attempts()}\n";
        $content .= "Error: " . $exception->getMessage() . "\n";
        $content .= "Trace: " . $exception->getTraceAsString();
        
        try {
            Mail::send($adminEmail, new \App\Mail\ZenithaLmsNotification($subject, $content, []));
        } catch (\Exception $e) {
            Log::error('Failed to notify admin of job failure: ' . $e->getMessage());
        }
    }
}
