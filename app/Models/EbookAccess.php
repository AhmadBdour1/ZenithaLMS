<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EbookAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ebook_id',
        'download_count',
        'access_granted_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'access_granted_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the access.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ebook that owns the access.
     */
    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_accessed_at' => now()]);
    }
}
