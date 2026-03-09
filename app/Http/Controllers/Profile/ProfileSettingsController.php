<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get user settings
     */
    public function getSettings()
    {
        $user = Auth::user();

        $settings = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'bio' => $user->bio,
                'website' => $user->website,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
            ],
            'social_links' => $user->social_links ?? [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'github' => '',
                'instagram' => '',
                'youtube' => '',
            ],
            'preferences' => $user->preferences ?? [
                'language' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'email_notifications' => true,
                'push_notifications' => true,
                'sms_notifications' => false,
                'marketing_emails' => true,
                'course_updates' => true,
                'subscription_reminders' => true,
                'achievement_notifications' => true,
                'privacy' => 'public',
                'show_email' => false,
                'show_phone' => false,
                'allow_messages' => true,
                'profile_visibility' => 'everyone',
            ],
            'security' => [
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => $user->phone_verified ?? false,
                'last_password_change' => $user->password_changed_at,
                'active_sessions' => $this->getActiveSessions(),
            ],
            'billing' => [
                'default_payment_method' => $user->default_payment_method,
                'billing_address' => $user->billing_address ?? [
                    'line1' => '',
                    'line2' => '',
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'country' => '',
                ],
                'tax_info' => $user->tax_info ?? [
                    'tax_id' => '',
                    'tax_id_type' => '',
                    'business_name' => '',
                ],
                'invoice_preferences' => $user->invoice_preferences ?? [
                    'send_invoices' => true,
                    'invoice_email' => $user->email,
                    'include_tax' => true,
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update profile settings
     */
    public function updateProfileSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        
        $user->update($request->only([
            'name', 'email', 'phone', 'country', 'bio', 'website'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile settings updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Update social links
     */
    public function updateSocialLinks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $user->update(['social_links' => $request->all()]);

        return response()->json([
            'success' => true,
            'message' => 'Social links updated successfully',
        ]);
    }

    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|in:12h,24h',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'course_updates' => 'boolean',
            'subscription_reminders' => 'boolean',
            'achievement_notifications' => 'boolean',
            'privacy' => 'nullable|string|in:public,friends,private',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'allow_messages' => 'boolean',
            'profile_visibility' => 'nullable|string|in:everyone,friends,private',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $user->update(['preferences' => $request->all()]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Update security settings
     */
    public function updateSecuritySettings(Request $request)
    {
        $user = Auth::user();

        // Update password
        if ($request->has('new_password')) {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!\Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            $user->update([
                'password' => \Hash::make($request->new_password),
                'password_changed_at' => now(),
            ]);
        }

        // Update 2FA settings
        if ($request->has('two_factor_enabled')) {
            $user->update(['two_factor_enabled' => $request->boolean('two_factor_enabled')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Security settings updated successfully',
        ]);
    }

    /**
     * Update billing settings
     */
    public function updateBillingSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_payment_method' => 'nullable|string|max:50',
            'billing_address' => 'nullable|array',
            'billing_address.line1' => 'required_with:billing_address|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:100',
            'billing_address.country' => 'required_with:billing_address|string|size:2',
            'tax_info' => 'nullable|array',
            'tax_info.tax_id' => 'required_with:tax_info|string|max:50',
            'tax_info.business_name' => 'required_with:tax_info|string|max:255',
            'invoice_preferences' => 'nullable|array',
            'invoice_preferences.invoice_email' => 'required_with:invoice_preferences|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        $updateData = [];
        
        if ($request->has('default_payment_method')) {
            $updateData['default_payment_method'] = $request->default_payment_method;
        }
        
        if ($request->has('billing_address')) {
            $updateData['billing_address'] = $request->billing_address;
        }
        
        if ($request->has('tax_info')) {
            $updateData['tax_info'] = $request->tax_info;
        }
        
        if ($request->has('invoice_preferences')) {
            $updateData['invoice_preferences'] = $request->invoice_preferences;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Billing settings updated successfully',
        ]);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Delete old avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Upload new avatar
        $avatar = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $avatar]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar' => $avatar,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Delete avatar
     */
    public function deleteAvatar()
    {
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar deleted successfully',
        ]);
    }

    /**
     * Get notification settings
     */
    public function getNotificationSettings()
    {
        $user = Auth::user();
        
        $settings = [
            'email_notifications' => [
                'course_updates' => $user->preferences['course_updates'] ?? true,
                'subscription_reminders' => $user->preferences['subscription_reminders'] ?? true,
                'achievement_notifications' => $user->preferences['achievement_notifications'] ?? true,
                'marketing_emails' => $user->preferences['marketing_emails'] ?? true,
                'security_alerts' => true,
                'billing_notifications' => true,
                'system_updates' => false,
            ],
            'push_notifications' => [
                'course_updates' => $user->preferences['course_updates'] ?? true,
                'subscription_reminders' => $user->preferences['subscription_reminders'] ?? true,
                'achievement_notifications' => $user->preferences['achievement_notifications'] ?? true,
                'messages' => true,
                'comments' => true,
                'mentions' => true,
            ],
            'sms_notifications' => [
                'security_alerts' => false,
                'billing_notifications' => false,
                'subscription_reminders' => false,
            ],
            'in_app_notifications' => [
                'course_updates' => true,
                'messages' => true,
                'comments' => true,
                'mentions' => true,
                'follows' => true,
                'likes' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'nullable|array',
            'push_notifications' => 'nullable|array',
            'sms_notifications' => 'nullable|array',
            'in_app_notifications' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $preferences = $user->preferences ?? [];

        // Update preferences with notification settings
        if ($request->has('email_notifications')) {
            $preferences = array_merge($preferences, $request->email_notifications);
        }

        $user->update(['preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
        ]);
    }

    /**
     * Get privacy settings
     */
    public function getPrivacySettings()
    {
        $user = Auth::user();
        
        $settings = [
            'profile_visibility' => $user->preferences['profile_visibility'] ?? 'everyone',
            'show_email' => $user->preferences['show_email'] ?? false,
            'show_phone' => $user->preferences['show_phone'] ?? false,
            'allow_messages' => $user->preferences['allow_messages'] ?? true,
            'show_online_status' => true,
            'show_last_seen' => false,
            'allow_friend_requests' => true,
            'show_courses' => true,
            'show_certificates' => true,
            'show_achievements' => true,
            'search_engine_indexing' => true,
            'data_collection' => true,
            'analytics_tracking' => true,
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_visibility' => 'nullable|string|in:everyone,friends,private',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'allow_messages' => 'boolean',
            'show_online_status' => 'boolean',
            'show_last_seen' => 'boolean',
            'allow_friend_requests' => 'boolean',
            'show_courses' => 'boolean',
            'show_certificates' => 'boolean',
            'show_achievements' => 'boolean',
            'search_engine_indexing' => 'boolean',
            'data_collection' => 'boolean',
            'analytics_tracking' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $preferences = $user->preferences ?? [];

        // Update preferences with privacy settings
        $preferences = array_merge($preferences, $request->all());

        $user->update(['preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Privacy settings updated successfully',
        ]);
    }

    /**
     * Export user data
     */
    public function exportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv,pdf',
            'include' => 'nullable|array',
            'include.*' => 'in:profile,courses,certificates,purchases,reviews,activity',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $format = $request->format;
        $include = $request->include ?? ['profile', 'courses', 'certificates'];

        $data = $this->prepareExportData($user, $include);
        
        $filename = 'user_data_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        $filepath = storage_path('exports/' . $filename);

        // Create exports directory if not exists
        if (!is_dir(storage_path('exports'))) {
            mkdir(storage_path('exports'), 0755, true);
        }

        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            case 'csv':
                $this->exportToCsv($data, $filepath);
                break;
            case 'pdf':
                $this->exportToPdf($data, $filepath);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Data exported successfully',
            'filename' => $filename,
            'download_url' => route('profile.download-export', $filename),
            'file_size' => $this->formatFileSize(filesize($filepath)),
        ]);
    }

    /**
     * Download exported data
     */
    public function downloadExport($filename)
    {
        $filepath = storage_path('exports/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($filepath);
    }

    // Helper methods
    private function getActiveSessions()
    {
        // This would typically get active sessions from the database
        // For now, return sample data
        return [
            [
                'id' => 'session_1',
                'device' => 'Chrome on Windows',
                'ip' => '192.168.1.100',
                'location' => 'Cairo, Egypt',
                'last_activity' => now()->subMinutes(5),
                'is_current' => true,
            ],
            [
                'id' => 'session_2',
                'device' => 'Safari on iPhone',
                'ip' => '192.168.1.101',
                'location' => 'Cairo, Egypt',
                'last_activity' => now()->subHours(2),
                'is_current' => false,
            ],
        ];
    }

    private function prepareExportData($user, $include)
    {
        $data = [];

        if (in_array('profile', $include)) {
            $data['profile'] = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'bio' => $user->bio,
                'website' => $user->website,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
            ];
        }

        if (in_array('courses', $include)) {
            $data['courses'] = $user->enrollments()
                ->with('course')
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'course_title' => $enrollment->course->title,
                        'enrolled_at' => $enrollment->enrolled_at,
                        'progress' => $enrollment->pivot->progress,
                        'completed_at' => $enrollment->pivot->completed_at,
                    ];
                });
        }

        if (in_array('certificates', $include)) {
            $data['certificates'] = $user->certificates()
                ->with('template')
                ->get()
                ->map(function ($certificate) {
                    return [
                        'template_name' => $certificate->template->name,
                        'certificate_number' => $certificate->certificate_number,
                        'issued_at' => $certificate->issued_at,
                        'expires_at' => $certificate->expires_at,
                    ];
                });
        }

        if (in_array('purchases', $include)) {
            $data['purchases'] = [
                'marketplace_orders' => $user->auraOrders()->get()->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                    ];
                }),
                'stuff_purchases' => $user->stuffPurchases()->get()->map(function ($purchase) {
                    return [
                        'stuff_name' => $purchase->stuff->name,
                        'total_amount' => $purchase->total_amount,
                        'status' => $purchase->status,
                        'created_at' => $purchase->created_at,
                    ];
                }),
            ];
        }

        if (in_array('reviews', $include)) {
            $data['reviews'] = $user->stuffReviews()
                ->with('stuff')
                ->get()
                ->map(function ($review) {
                    return [
                        'stuff_name' => $review->stuff->name,
                        'rating' => $review->rating,
                        'title' => $review->title,
                        'content' => $review->content,
                        'created_at' => $review->created_at,
                    ];
                });
        }

        if (in_array('activity', $include)) {
            $data['activity'] = [
                'login_history' => [], // Would come from login history table
                'course_activity' => [], // Would come from course activity table
                'purchase_history' => [], // Would come from purchase history table
            ];
        }

        return $data;
    }

    private function exportToCsv($data, $filepath)
    {
        $file = fopen($filepath, 'w');
        
        // Write UTF-8 BOM
        fwrite($file, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($file, ['Section', 'Key', 'Value', 'Date']);
        
        // Write data
        foreach ($data as $section => $sectionData) {
            if (is_array($sectionData)) {
                foreach ($sectionData as $item) {
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $date = '';
                            if (in_array($key, ['created_at', 'enrolled_at', 'issued_at', 'completed_at'])) {
                                $date = $value;
                                $value = '';
                            }
                            fputcsv($file, [$section, $key, $value, $date]);
                        }
                    }
                }
            }
        }
        
        fclose($file);
    }

    private function exportToPdf($data, $filepath)
    {
        // Simple text-based PDF export
        $content = "User Data Export\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        foreach ($data as $section => $sectionData) {
            $content .= strtoupper($section) . "\n";
            $content .= str_repeat('-', 50) . "\n";
            
            if (is_array($sectionData)) {
                foreach ($sectionData as $item) {
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $content .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
                        }
                        $content .= "\n";
                    }
                }
            }
        }
        
        file_put_contents($filepath, $content);
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
