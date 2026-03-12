<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'user_id',
        'rating',
        'title',
        'content',
        'pros',
        'cons',
        'recommendation', // 'yes', 'no', 'maybe'
        'verified_purchase',
        'helpful_count',
        'status', // 'pending', 'approved', 'rejected'
        'admin_notes',
        'ip_address',
        'user_agent',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
        'reviewed_at' => 'datetime',
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

    public function helpfulVotes()
    {
        return $this->hasMany(StuffReviewHelpful::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified_purchase', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeByRecommendation($query, $recommendation)
    {
        return $query->where('recommendation', $recommendation);
    }

    // Methods
    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // Update stuff rating
        $this->stuff->updateRating();
    }

    public function reject($notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function markHelpful($userId)
    {
        $helpful = $this->helpfulVotes()->where('user_id', $userId)->first();

        if (!$helpful) {
            $this->helpfulVotes()->create(['user_id' => $userId]);
            $this->increment('helpful_count');
        }

        return $this->helpful_count;
    }

    public function unmarkHelpful($userId)
    {
        $helpful = $this->helpfulVotes()->where('user_id', $userId)->first();

        if ($helpful) {
            $helpful->delete();
            $this->decrement('helpful_count');
        }

        return $this->helpful_count;
    }

    public function getRecommendationText()
    {
        switch ($this->recommendation) {
            case 'yes':
                return 'Yes, I recommend this';
            case 'no':
                return 'No, I do not recommend this';
            case 'maybe':
                return 'Maybe, depends on your needs';
            default:
                return 'No recommendation';
        }
    }

    public function getRatingStars()
    {
        $stars = [];
        $fullStars = floor($this->rating);
        $halfStar = $this->rating - $fullStars >= 0.5;

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
                $stars[] = 'full';
            } elseif ($i == $fullStars + 1 && $halfStar) {
                $stars[] = 'half';
            } else {
                $stars[] = 'empty';
            }
        }

        return $stars;
    }

    public function canEdit($user)
    {
        return $this->user_id === $user->id && $this->status === 'pending';
    }

    public function canDelete($user)
    {
        return $this->user_id === $user->id || $user->hasRole('admin');
    }

    public function isVerified()
    {
        return $this->verified_purchase;
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function getFormattedRating()
    {
        return number_format($this->rating, 1);
    }

    public function getProsArray()
    {
        return $this->pros ? explode("\n", $this->pros) : [];
    }

    public function getConsArray()
    {
        return $this->cons ? explode("\n", $this->cons) : [];
    }

    public function hasPros()
    {
        return !empty($this->pros);
    }

    public function hasCons()
    {
        return !empty($this->cons);
    }

    public function hasRecommendation()
    {
        return !empty($this->recommendation);
    }

    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getReviewedAtFormatted()
    {
        return $this->reviewed_at ? $this->reviewed_at->format('Y-m-d H:i:s') : null;
    }
}

class StuffReviewHelpful extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review()
    {
        return $this->belongsTo(StuffReview::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
