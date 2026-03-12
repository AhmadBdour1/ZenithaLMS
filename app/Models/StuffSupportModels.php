<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffDownload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'user_id',
        'purchase_id',
        'license_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
        'file_size',
        'download_time',
        'status', // 'completed', 'failed', 'cancelled'
        'error_message',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'file_size' => 'integer',
        'download_time' => 'integer',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchase()
    {
        return $this->belongsTo(StuffPurchase::class, 'purchase_id');
    }

    public function license()
    {
        return $this->belongsTo(StuffLicense::class, 'license_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStuff($query, $stuffId)
    {
        return $query->where('stuff_id', $stuffId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('downloaded_at', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('downloaded_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('downloaded_at', now()->month)
                    ->whereYear('downloaded_at', now()->year);
    }

    // Methods
    public function complete($fileSize = null, $downloadTime = null)
    {
        $this->update([
            'status' => 'completed',
            'file_size' => $fileSize,
            'download_time' => $downloadTime,
            'downloaded_at' => now(),
        ]);

        return $this;
    }

    public function fail($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'downloaded_at' => now(),
        ]);

        return $this;
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'downloaded_at' => now(),
        ]);

        return $this;
    }

    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getFormattedDownloadTime()
    {
        if (!$this->download_time) {
            return null;
        }

        if ($this->download_time < 60) {
            return $this->download_time . ' seconds';
        } elseif ($this->download_time < 3600) {
            return round($this->download_time / 60, 1) . ' minutes';
        } else {
            return round($this->download_time / 3600, 1) . ' hours';
        }
    }

    public function getDownloadSpeed()
    {
        if (!$this->file_size || !$this->download_time) {
            return null;
        }

        $speed = $this->file_size / $this->download_time; // bytes per second

        if ($speed < 1024) {
            return round($speed, 2) . ' B/s';
        } elseif ($speed < 1048576) {
            return round($speed / 1024, 2) . ' KB/s';
        } else {
            return round($speed / 1048576, 2) . ' MB/s';
        }
    }

    public function getFormattedDownloadedAt()
    {
        return $this->downloaded_at->format('Y-m-d H:i:s');
    }
}

class StuffAnalytics extends Model
{
    protected $fillable = [
        'stuff_id',
        'user_id',
        'purchase_id',
        'type', // 'view', 'download', 'purchase', 'search', 'share', 'review'
        'source', // 'search', 'category', 'featured', 'popular', 'trending', 'direct', 'referral'
        'ip_address',
        'user_agent',
        'referrer',
        'session_id',
        'amount',
        'currency',
        'quantity',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchase()
    {
        return $this->belongsTo(StuffPurchase::class, 'purchase_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStuff($query, $stuffId)
    {
        return $query->where('stuff_id', $stuffId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    // Methods
    public static function recordView($stuffId, $userId = null, $source = null, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'type' => 'view',
            'source' => $source ?: 'direct',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'session_id' => session()->getId(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public static function recordDownload($stuffId, $userId, $purchaseId = null, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'type' => 'download',
            'source' => 'purchase',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public static function recordPurchase($stuffId, $userId, $purchaseId, $amount, $currency, $quantity = 1, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'type' => 'purchase',
            'source' => 'checkout',
            'amount' => $amount,
            'currency' => $currency,
            'quantity' => $quantity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public static function recordSearch($stuffId, $userId = null, $searchTerm = null, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'type' => 'search',
            'source' => 'search',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => array_merge($metadata, ['search_term' => $searchTerm]),
            'created_at' => now(),
        ]);
    }

    public static function recordShare($stuffId, $userId = null, $platform = null, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'type' => 'share',
            'source' => $platform ?: 'direct',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public static function recordReview($stuffId, $userId, $rating = null, $metadata = [])
    {
        return static::create([
            'stuff_id' => $stuffId,
            'user_id' => $userId,
            'type' => 'review',
            'source' => 'review',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => array_merge($metadata, ['rating' => $rating]),
            'created_at' => now(),
        ]);
    }

    public static function getAnalytics($stuffId, $period = '30days')
    {
        $dateRange = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7days' => [now()->subDays(7), now()],
            '30days' => [now()->subDays(30), now()],
            '90days' => [now()->subDays(90), now()],
            '1year' => [now()->subYear(), now()],
            default => [now()->subDays(30), now()],
        };

        $analytics = static::where('stuff_id', $stuffId)
            ->whereBetween('created_at', $dateRange)
            ->get();

        return [
            'views' => $analytics->where('type', 'view')->count(),
            'downloads' => $analytics->where('type', 'download')->count(),
            'purchases' => $analytics->where('type', 'purchase')->count(),
            'reviews' => $analytics->where('type', 'review')->count(),
            'shares' => $analytics->where('type', 'share')->count(),
            'searches' => $analytics->where('type', 'search')->count(),
            'revenue' => $analytics->where('type', 'purchase')->sum('amount'),
            'unique_users' => $analytics->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
            'conversion_rate' => $this->calculateConversionRate($analytics),
            'top_sources' => $analytics->groupBy('source')->map->count()->sortDesc()->take(5),
            'daily_stats' => $this->getDailyStats($stuffId, $dateRange),
        ];
    }

    private static function calculateConversionRate($analytics)
    {
        $views = $analytics->where('type', 'view')->count();
        $purchases = $analytics->where('type', 'purchase')->count();

        return $views > 0 ? ($purchases / $views) * 100 : 0;
    }

    private static function getDailyStats($stuffId, $dateRange)
    {
        return static::where('stuff_id', $stuffId)
            ->whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($day) {
                return [
                    'views' => $day->where('type', 'view')->sum('count'),
                    'downloads' => $day->where('type', 'download')->sum('count'),
                    'purchases' => $day->where('type', 'purchase')->sum('count'),
                    'revenue' => $day->where('type', 'purchase')->sum('amount'),
                ];
            });
    }
}

class StuffSupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'user_id',
        'vendor_id',
        'subject',
        'description',
        'priority', // 'low', 'medium', 'high', 'urgent'
        'status', // 'open', 'in_progress', 'resolved', 'closed'
        'category', // 'technical', 'billing', 'general', 'feature_request', 'bug_report'
        'type', // 'question', 'issue', 'request', 'complaint'
        'assigned_to',
        'resolved_by',
        'resolution',
        'resolved_at',
        'response_time',
        'first_response_at',
        'last_response_at',
        'escalated',
        'escalated_at',
        'escalated_to',
        'satisfaction_rating',
        'feedback',
        'internal_notes',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'first_response_at' => 'datetime',
        'last_response_at' => 'datetime',
        'escalated_at' => 'datetime',
        'escalated' => 'boolean',
        'response_time' => 'integer',
        'satisfaction_rating' => 'integer',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function escalatedTo()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function replies()
    {
        return $this->hasMany(StuffSupportReply::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByStuff($query, $stuffId)
    {
        return $query->where('stuff_id', $stuffId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeOverdue($query)
    {
        return $query->where('created_at', '<', now()->subHours(24))
                    ->whereIn('status', ['open', 'in_progress']);
    }

    // Methods
    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_progress',
        ]);

        return $this;
    }

    public function escalate($toUserId = null, $reason = null)
    {
        $this->update([
            'escalated' => true,
            'escalated_at' => now(),
            'escalated_to' => $toUserId,
            'internal_notes' => $reason,
        ]);

        return $this;
    }

    public function resolve($resolution = null, $resolvedBy = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => $resolvedBy ?: auth()->id(),
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function close()
    {
        $this->update([
            'status' => 'closed',
        ]);

        return $this;
    }

    public function reopen()
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution' => null,
        ]);

        return $this;
    }

    public function addReply($content, $userId = null, $isInternal = false)
    {
        $reply = $this->replies()->create([
            'user_id' => $userId ?: auth()->id(),
            'content' => $content,
            'is_internal' => $isInternal,
        ]);

        // Update last response time
        $this->update(['last_response_at' => now()]);

        // Set first response time if this is the first reply
        if (!$this->first_response_at) {
            $this->update([
                'first_response_at' => now(),
                'response_time' => $this->created_at->diffInMinutes(now()),
            ]);
        }

        return $reply;
    }

    public function setSatisfactionRating($rating, $feedback = null)
    {
        $this->update([
            'satisfaction_rating' => $rating,
            'feedback' => $feedback,
        ]);

        return $this;
    }

    public function getResponseTimeText()
    {
        if (!$this->response_time) {
            return 'Not responded yet';
        }

        if ($this->response_time < 60) {
            return $this->response_time . ' minutes';
        } elseif ($this->response_time < 1440) {
            return round($this->response_time / 60, 1) . ' hours';
        } else {
            return round($this->response_time / 1440, 1) . ' days';
        }
    }

    public function getPriorityColor()
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getStatusColor()
    {
        return match ($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function isOverdue()
    {
        return in_array($this->status, ['open', 'in_progress']) && 
               $this->created_at->lt(now()->subHours(24));
    }

    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getResolvedAtFormatted()
    {
        return $this->resolved_at ? $this->resolved_at->format('Y-m-d H:i:s') : null;
    }

    public function getFirstResponseAtFormatted()
    {
        return $this->first_response_at ? $this->first_response_at->format('Y-m-d H:i:s') : null;
    }

    public function getLastResponseAtFormatted()
    {
        return $this->last_response_at ? $this->last_response_at->format('Y-m-d H:i:s') : null;
    }
}

class StuffSupportReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
        'is_internal',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(StuffSupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    // Methods
    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }
}
