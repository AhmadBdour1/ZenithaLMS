<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Forum extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'status',
        'view_count',
        'reply_count',
        'like_count',
        'is_pinned',
        'is_locked',
    ];
    
    protected $casts = [
        'view_count' => 'integer',
        'reply_count' => 'integer',
        'like_count' => 'integer',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function replies()
    {
        return $this->hasMany(ForumReply::class);
    }
}
