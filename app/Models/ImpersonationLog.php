<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImpersonationLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_email',
        'target_user_id',
        'target_user_name',
        'target_user_email',
        'entity_type',
        'entity_id',
        'entity_name',
        'reason',
        'ip_address',
        'user_agent',
        'started_at',
        'ended_at',
        'duration_minutes',
        'status', // 'active', 'completed', 'expired', 'forced'
        'notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_minutes' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeForced($query)
    {
        return $query->where('status', 'forced');
    }

    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByTargetUser($query, $targetUserId)
    {
        return $query->where('target_user_id', $targetUserId);
    }

    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('started_at', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('started_at', now()->month)
                    ->whereYear('started_at', now()->year);
    }

    // Methods
    public static function startImpersonation($adminId, $targetUserId, $entityType, $entityId, $entityName, $reason, $duration)
    {
        $admin = User::find($adminId);
        $targetUser = User::find($targetUserId);

        return static::create([
            'admin_id' => $adminId,
            'admin_name' => $admin?->name,
            'admin_email' => $admin?->email,
            'target_user_id' => $targetUserId,
            'target_user_name' => $targetUser?->name,
            'target_user_email' => $targetUser?->email,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'started_at' => now(),
            'duration_minutes' => $duration,
            'status' => 'active',
        ]);
    }

    public function completeImpersonation()
    {
        $this->update([
            'ended_at' => now(),
            'duration_minutes' => $this->started_at->diffInMinutes(now()),
            'status' => 'completed',
        ]);

        return $this;
    }

    public function expireImpersonation()
    {
        $this->update([
            'ended_at' => now(),
            'duration_minutes' => $this->started_at->diffInMinutes(now()),
            'status' => 'expired',
        ]);

        return $this;
    }

    public function forceStopImpersonation()
    {
        $this->update([
            'ended_at' => now(),
            'duration_minutes' => $this->started_at->diffInMinutes(now()),
            'status' => 'forced',
        ]);

        return $this;
    }

    public function getDurationText()
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' minutes';
        } else {
            $hours = floor($this->duration_minutes / 60);
            $minutes = $this->duration_minutes % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    public function getStatusColor()
    {
        return match ($this->status) {
            'active' => 'green',
            'completed' => 'blue',
            'expired' => 'orange',
            'forced' => 'red',
            default => 'gray',
        };
    }

    public function getEntityTypeDisplayName()
    {
        return match ($this->entity_type) {
            'users' => 'User',
            'courses' => 'Course',
            'subscriptions' => 'Subscription',
            'marketplace' => 'Marketplace Product',
            'orders' => 'Order',
            'pagebuilder' => 'Page',
            'certificates' => 'Certificate',
            'stuff' => 'Stuff',
            default => ucfirst($this->entity_type),
        };
    }

    public function getStartedAtFormatted()
    {
        return $this->started_at->format('Y-m-d H:i:s');
    }

    public function getEndedAtFormatted()
    {
        return $this->ended_at ? $this->ended_at->format('Y-m-d H:i:s') : null;
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isForced()
    {
        return $this->status === 'forced';
    }

    // Static methods for statistics
    public static function getStats($period = 'today')
    {
        $query = static::query();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'this_week':
                $query->thisWeek();
                break;
            case 'this_month':
                $query->thisMonth();
                break;
        }

        return [
            'total' => $query->count(),
            'active' => $query->active()->count(),
            'completed' => $query->completed()->count(),
            'expired' => $query->expired()->count(),
            'forced' => $query->forced()->count(),
            'average_duration' => $query->avg('duration_minutes'),
            'by_entity_type' => $query->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->get()
                ->pluck('count', 'entity_type')
                ->toArray(),
            'by_admin' => $query->selectRaw('admin_name, count(*) as count')
                ->groupBy('admin_name')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public static function getRecentActivity($limit = 10)
    {
        return static::with(['admin', 'targetUser'])
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getActiveImpersonations()
    {
        return static::active()
            ->with(['admin', 'targetUser'])
            ->orderBy('started_at', 'desc')
            ->get();
    }
}
