<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileSecurityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get security settings
     */
    public function getSecuritySettings()
    {
        $user = Auth::user();

        $settings = [
            'password' => [
                'last_changed' => $user->password_changed_at,
                'requires_change' => $this->requiresPasswordChange($user),
                'strength_score' => $this->getPasswordStrength($user->password),
            ],
            'two_factor' => [
                'enabled' => $user->two_factor_enabled ?? false,
                'secret' => $user->two_factor_secret,
                'backup_codes' => $user->two_factor_backup_codes ?? [],
                'confirmed_at' => $user->two_factor_confirmed_at,
            ],
            'sessions' => [
                'active_sessions' => $this->getActiveSessions($user),
                'session_timeout' => config('session.lifetime'),
                'remember_me' => $this->hasRememberMeToken($user),
            ],
            'login_activity' => [
                'recent_logins' => $this->getRecentLogins($user),
                'failed_attempts' => $this->getFailedLoginAttempts($user),
                'suspicious_activity' => $this->getSuspiciousActivity($user),
            ],
            'devices' => [
                'trusted_devices' => $this->getTrustedDevices($user),
                'unknown_devices' => $this->getUnknownDevices($user),
            ],
            'notifications' => [
                'login_alerts' => $user->preferences['security_alerts'] ?? true,
                'password_change_alerts' => true,
                'new_device_alerts' => true,
                'suspicious_activity_alerts' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => \Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        // Invalidate all other sessions
        $this->invalidateOtherSessions($user);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(Request $request)
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is already enabled',
            ], 400);
        }

        // Generate secret key
        $secret = $this->generateTwoFactorSecret();
        $qrCode = $this->generateTwoFactorQrCode($user, $secret);
        $backupCodes = $this->generateBackupCodes();

        // Store temporarily (not enabled yet)
        session(['two_factor_setup' => [
            'secret' => $secret,
            'backup_codes' => $backupCodes,
        ]]);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication setup initiated',
            'data' => [
                'secret' => $secret,
                'qr_code' => $qrCode,
                'backup_codes' => $backupCodes,
                'manual_entry_key' => $this->formatManualEntryKey($secret),
            ],
        ]);
    }

    /**
     * Confirm two-factor authentication
     */
    public function confirmTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            'backup_code' => 'nullable|string|size:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $setup = session('two_factor_setup');

        if (!$setup) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor setup not initiated',
            ], 400);
        }

        $isValid = false;

        if ($request->has('code')) {
            $isValid = $this->verifyTwoFactorCode($setup['secret'], $request->code);
        } elseif ($request->has('backup_code')) {
            $isValid = in_array($request->backup_code, $setup['backup_codes']);
        }

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code',
            ], 400);
        }

        // Enable two-factor authentication
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $setup['secret'],
            'two_factor_backup_codes' => $setup['backup_codes'],
            'two_factor_confirmed_at' => now(),
        ]);

        // Clear session
        session()->forget('two_factor_setup');

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully',
        ]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'code' => 'nullable|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        // If two-factor is enabled, verify the code
        if ($user->two_factor_enabled && $request->has('code')) {
            if (!$this->verifyTwoFactorCode($user->two_factor_secret, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid two-factor code',
                ], 400);
            }
        }

        // Disable two-factor authentication
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully',
        ]);
    }

    /**
     * Get backup codes
     */
    public function getBackupCodes()
    {
        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled',
            ], 400);
        }

        $backupCodes = $user->two_factor_backup_codes ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes,
                'remaining_codes' => count($backupCodes),
                'last_generated' => $user->two_factor_confirmed_at,
            ],
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        $backupCodes = $this->generateBackupCodes();

        $user->update(['two_factor_backup_codes' => $backupCodes]);

        return response()->json([
            'success' => true,
            'message' => 'Backup codes regenerated successfully',
            'data' => [
                'backup_codes' => $backupCodes,
                'generated_at' => now(),
            ],
        ]);
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions()
    {
        $user = Auth::user();
        $sessions = $this->getActiveSessions($user);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Revoke session
     */
    public function revokeSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $sessionId = $request->session_id;

        // Don't allow revoking current session
        if ($sessionId === session()->getId()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke current session',
            ], 400);
        }

        // Revoke the session
        $this->revokeSession($user, $sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully',
        ]);
    }

    /**
     * Revoke all other sessions
     */
    public function revokeAllOtherSessions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        $this->invalidateOtherSessions($user);

        return response()->json([
            'success' => true,
            'message' => 'All other sessions revoked successfully',
        ]);
    }

    /**
     * Get login activity
     */
    public function getLoginActivity(Request $request)
    {
        $user = Auth::user();
        $period = $request->period ?? 'month';

        $activity = [
            'recent_logins' => $this->getRecentLogins($user),
            'failed_attempts' => $this->getFailedLoginAttempts($user),
            'suspicious_activity' => $this->getSuspiciousActivity($user),
            'statistics' => $this->getLoginStatistics($user, $period),
        ];

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Get trusted devices
     */
    public function getTrustedDevices()
    {
        $user = Auth::user();
        $devices = $this->getTrustedDevices($user);

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * Add trusted device
     */
    public function addTrustedDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:100',
            'device_fingerprint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $device = $this->addTrustedDevice($user, $request->device_name, $request->device_fingerprint);

        return response()->json([
            'success' => true,
            'message' => 'Device added to trusted devices',
            'data' => $device,
        ]);
    }

    /**
     * Remove trusted device
     */
    public function removeTrustedDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $this->removeTrustedDevice($user, $request->device_id);

        return response()->json([
            'success' => true,
            'message' => 'Device removed from trusted devices',
        ]);
    }

    /**
     * Get security recommendations
     */
    public function getSecurityRecommendations()
    {
        $user = Auth::user();
        $recommendations = [];

        // Check password strength
        if ($this->requiresPasswordChange($user)) {
            $recommendations[] = [
                'type' => 'password',
                'priority' => 'high',
                'title' => 'Update Your Password',
                'description' => 'Your password is weak or hasn\'t been changed recently',
                'action' => 'Change password',
            ];
        }

        // Check two-factor authentication
        if (!$user->two_factor_enabled) {
            $recommendations[] = [
                'type' => 'two_factor',
                'priority' => 'high',
                'title' => 'Enable Two-Factor Authentication',
                'description' => 'Add an extra layer of security to your account',
                'action' => 'Enable 2FA',
            ];
        }

        // Check active sessions
        $activeSessions = $this->getActiveSessions($user);
        if (count($activeSessions) > 3) {
            $recommendations[] = [
                'type' => 'sessions',
                'priority' => 'medium',
                'title' => 'Review Active Sessions',
                'description' => 'You have multiple active sessions',
                'action' => 'Review sessions',
            ];
        }

        // Check login activity
        $suspiciousActivity = $this->getSuspiciousActivity($user);
        if (!empty($suspiciousActivity)) {
            $recommendations[] = [
                'type' => 'suspicious',
                'priority' => 'high',
                'title' => 'Review Suspicious Activity',
                'description' => 'We detected some unusual login attempts',
                'action' => 'Review activity',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    // Helper methods
    private function requiresPasswordChange($user)
    {
        // Check if password hasn't been changed in 90 days
        if ($user->password_changed_at) {
            return $user->password_changed_at->lt(now()->subDays(90));
        }

        // Check if user was created more than 30 days ago without password change
        return $user->created_at->lt(now()->subDays(30));
    }

    private function getPasswordStrength($password)
    {
        $strength = 0;
        
        // Length
        if (strlen($password) >= 8) $strength += 20;
        if (strlen($password) >= 12) $strength += 10;
        
        // Complexity
        if (preg_match('/[a-z]/', $password)) $strength += 15;
        if (preg_match('/[A-Z]/', $password)) $strength += 15;
        if (preg_match('/[0-9]/', $password)) $strength += 15;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength += 15;
        if (strlen($password) >= 16) $strength += 10;
        
        return min(100, $strength);
    }

    private function getActiveSessions($user)
    {
        // This would typically get from the sessions table
        return [
            [
                'id' => session()->getId(),
                'device' => 'Chrome on Windows',
                'ip_address' => request()->ip(),
                'location' => 'Cairo, Egypt',
                'last_activity' => now()->subMinutes(5),
                'is_current' => true,
                'user_agent' => request()->userAgent(),
            ],
            [
                'id' => 'session_2',
                'device' => 'Safari on iPhone',
                'ip_address' => '192.168.1.101',
                'location' => 'Cairo, Egypt',
                'last_activity' => now()->subHours(2),
                'is_current' => false,
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            ],
        ];
    }

    private function hasRememberMeToken($user)
    {
        return !is_null($user->remember_token);
    }

    private function getRecentLogins($user)
    {
        // This would typically get from a login history table
        return [
            [
                'ip_address' => '192.168.1.100',
                'location' => 'Cairo, Egypt',
                'device' => 'Chrome on Windows',
                'success' => true,
                'timestamp' => now()->subHours(2),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            [
                'ip_address' => '192.168.1.101',
                'location' => 'Cairo, Egypt',
                'device' => 'Safari on iPhone',
                'success' => true,
                'timestamp' => now()->subDays(1),
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            ],
        ];
    }

    private function getFailedLoginAttempts($user)
    {
        // This would typically get from a failed logins table
        return [
            [
                'ip_address' => '192.168.1.200',
                'location' => 'Unknown',
                'device' => 'Unknown',
                'reason' => 'Invalid password',
                'timestamp' => now()->subHours(6),
                'user_agent' => 'Mozilla/5.0 (Unknown)',
            ],
        ];
    }

    private function getSuspiciousActivity($user)
    {
        // This would typically analyze login patterns
        return [];
    }

    private function getTrustedDevices($user)
    {
        // This would typically get from a trusted devices table
        return [
            [
                'id' => 'device_1',
                'name' => 'My Laptop',
                'fingerprint' => 'abc123',
                'added_at' => now()->subMonths(2),
                'last_used' => now()->subHours(2),
                'is_current' => true,
            ],
            [
                'id' => 'device_2',
                'name' => 'My Phone',
                'fingerprint' => 'def456',
                'added_at' => now()->subMonths(1),
                'last_used' => now()->subDays(3),
                'is_current' => false,
            ],
        ];
    }

    private function getUnknownDevices($user)
    {
        // This would typically detect unknown devices
        return [];
    }

    private function invalidateOtherSessions($user)
    {
        // This would typically invalidate other sessions
        // For now, just return true
        return true;
    }

    private function revokeSession($user, $sessionId)
    {
        // This would typically revoke a specific session
        return true;
    }

    private function generateTwoFactorSecret()
    {
        return strtoupper(Str::random(32));
    }

    private function generateTwoFactorQrCode($user, $secret)
    {
        $appName = config('app.name');
        $email = $user->email;
        $otpauthUrl = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";
        
        // This would typically generate a QR code image
        return $otpauthUrl;
    }

    private function generateBackupCodes()
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }
        return $codes;
    }

    private function verifyTwoFactorCode($secret, $code)
    {
        // This would typically verify the TOTP code
        // For now, just return true for demo
        return true;
    }

    private function formatManualEntryKey($secret)
    {
        return chunk_split($secret, 4, ' ');
    }

    private function getLoginStatistics($user, $period)
    {
        // This would typically calculate login statistics
        return [
            'total_logins' => 150,
            'successful_logins' => 145,
            'failed_logins' => 5,
            'unique_ips' => 3,
            'unique_devices' => 2,
            'most_common_device' => 'Chrome on Windows',
            'most_common_location' => 'Cairo, Egypt',
        ];
    }

    private function addTrustedDevice($user, $deviceName, $fingerprint)
    {
        // This would typically add to trusted devices table
        return [
            'id' => 'device_' . Str::random(8),
            'name' => $deviceName,
            'fingerprint' => $fingerprint,
            'added_at' => now(),
            'last_used' => now(),
        ];
    }

    private function removeTrustedDevice($user, $deviceId)
    {
        // This would typically remove from trusted devices table
        return true;
    }
}
