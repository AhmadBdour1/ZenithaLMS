<?php

use Illuminate\Support\Facades\Route;

// Profile Routes
Route::prefix('profile')->middleware(['auth'])->group(function () {
    
    // Profile Main Controller
    Route::get('/', [App\Http\Controllers\Profile\ProfileController::class, 'getProfile']);
    Route::put('/', [App\Http\Controllers\Profile\ProfileController::class, 'updateProfile']);
    Route::post('/avatar', [App\Http\Controllers\Profile\ProfileController::class, 'updateAvatar']);
    Route::delete('/avatar', [App\Http\Controllers\Profile\ProfileController::class, 'deleteAvatar']);
    Route::put('/password', [App\Http\Controllers\Profile\ProfileController::class, 'updatePassword']);
    Route::delete('/account', [App\Http\Controllers\Profile\ProfileController::class, 'deleteAccount']);
    
    // Profile Data
    Route::get('/courses', [App\Http\Controllers\Profile\ProfileController::class, 'getCourses']);
    Route::get('/certificates', [App\Http\Controllers\Profile\ProfileController::class, 'getCertificates']);
    Route::get('/subscriptions', [App\Http\Controllers\Profile\ProfileController::class, 'getSubscriptions']);
    Route::get('/purchases', [App\Http\Controllers\Profile\ProfileController::class, 'getPurchases']);
    Route::get('/reviews', [App\Http\Controllers\Profile\ProfileController::class, 'getReviews']);
    Route::get('/pages', [App\Http\Controllers\Profile\ProfileController::class, 'getPages']);
    Route::get('/achievements', [App\Http\Controllers\Profile\ProfileController::class, 'getAchievements']);
    Route::get('/activity-timeline', [App\Http\Controllers\Profile\ProfileController::class, 'getActivityTimeline']);
    Route::get('/statistics', [App\Http\Controllers\Profile\ProfileController::class, 'getStatistics']);
    
    // Profile Settings Controller
    Route::get('/settings', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'getSettings']);
    Route::put('/settings/profile', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updateProfileSettings']);
    Route::put('/settings/social', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updateSocialLinks']);
    Route::put('/settings/preferences', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updatePreferences']);
    Route::put('/settings/security', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updateSecuritySettings']);
    Route::put('/settings/billing', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updateBillingSettings']);
    Route::post('/settings/avatar', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'uploadAvatar']);
    Route::delete('/settings/avatar', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'deleteAvatar']);
    
    // Notifications
    Route::get('/settings/notifications', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'getNotificationSettings']);
    Route::put('/settings/notifications', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updateNotificationSettings']);
    
    // Privacy
    Route::get('/settings/privacy', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'getPrivacySettings']);
    Route::put('/settings/privacy', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'updatePrivacySettings']);
    
    // Data Export
    Route::post('/settings/export', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'exportData']);
    Route::get('/settings/download/{filename}', [App\Http\Controllers\Profile\ProfileSettingsController::class, 'downloadExport']);
    
    // Profile Activity Controller
    Route::get('/activity/dashboard', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getActivityDashboard']);
    Route::get('/activity', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getActivities']);
    Route::get('/activity/{id}', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getActivityDetail']);
    Route::get('/activity/statistics', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getActivityStatistics']);
    
    // Learning Progress
    Route::get('/learning/progress', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getLearningProgress']);
    
    // Purchase History
    Route::get('/purchases/history', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getPurchaseHistory']);
    
    // Certificates and Achievements
    Route::get('/certificates/achievements', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getCertificatesAndAchievements']);
    
    // Reviews and Ratings
    Route::get('/reviews/ratings', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getReviewsAndRatings']);
    
    // Pages and Content
    Route::get('/pages/content', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getPagesAndContent']);
    
    // Social Activity
    Route::get('/social/activity', [App\Http\Controllers\Profile\ProfileActivityController::class, 'getSocialActivity']);
    
    // Profile Security Controller
    Route::get('/security', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getSecuritySettings']);
    Route::put('/security/password', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'changePassword']);
    Route::post('/security/two-factor/enable', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'enableTwoFactor']);
    Route::post('/security/two-factor/confirm', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'confirmTwoFactor']);
    Route::delete('/security/two-factor/disable', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'disableTwoFactor']);
    Route::get('/security/two-factor/backup-codes', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getBackupCodes']);
    Route::post('/security/two-factor/backup-codes/regenerate', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'regenerateBackupCodes']);
    
    // Sessions
    Route::get('/security/sessions', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getActiveSessions']);
    Route::delete('/security/sessions/{session_id}', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'revokeSession']);
    Route::delete('/security/sessions/all', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'revokeAllOtherSessions']);
    
    // Login Activity
    Route::get('/security/login-activity', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getLoginActivity']);
    
    // Trusted Devices
    Route::get('/security/trusted-devices', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getTrustedDevices']);
    Route::post('/security/trusted-devices', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'addTrustedDevice']);
    Route::delete('/security/trusted-devices/{device_id}', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'removeTrustedDevice']);
    
    // Security Recommendations
    Route::get('/security/recommendations', [App\Http\Controllers\Profile\ProfileSecurityController::class, 'getSecurityRecommendations']);
});

// Public Profile Routes
Route::prefix('profile')->group(function () {
    
    // Public Profile Controller
    Route::get('/{username}', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicProfile']);
    Route::get('/{username}/courses', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicCourses']);
    Route::get('/{username}/certificates', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicCertificates']);
    Route::get('/{username}/achievements', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicAchievements']);
    Route::get('/{username}/reviews', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicReviews']);
    Route::get('/{username}/pages', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicPages']);
    Route::get('/{username}/portfolio', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getPublicPortfolio']);
    
    // Social Interactions (require auth)
    Route::middleware(['auth'])->group(function () {
        Route::post('/{username}/follow', [App\Http\Controllers\Profile\ProfilePublicController::class, 'followUser']);
        Route::delete('/{username}/follow', [App\Http\Controllers\Profile\ProfilePublicController::class, 'unfollowUser']);
        Route::get('/{username}/followers', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getFollowers']);
        Route::get('/{username}/following', [App\Http\Controllers\Profile\ProfilePublicController::class, 'getFollowing']);
        Route::post('/{username}/message', [App\Http\Controllers\Profile\ProfilePublicController::class, 'sendMessage']);
        Route::post('/{username}/report', [App\Http\Controllers\Profile\ProfilePublicController::class, 'reportUser']);
        Route::post('/{username}/block', [App\Http\Controllers\Profile\ProfilePublicController::class, 'blockUser']);
        Route::delete('/{username}/block', [App\Http\Controllers\Profile\ProfilePublicController::class, 'unblockUser']);
    });
});
