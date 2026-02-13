<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Certificate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_template_id',
        'certificate_number',
        'issued_at',
        'expires_at',
        'certificate_url',
        'verification_code',
        'certificate_data',
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'certificate_data' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }
    
    /**
     * Check if certificate is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Check if certificate is valid
     */
    public function isValid()
    {
        return !$this->isExpired();
    }
    
    /**
     * Get certificate status
     */
    public function getStatus()
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        
        return 'valid';
    }
    
    /**
     * Generate verification URL
     */
    public function getVerificationUrl()
    {
        return route('certificates.verify', $this->verification_code);
    }
    
    /**
     * Get certificate file name
     */
    public function getFileName()
    {
        return "certificate_{$this->certificate_number}.pdf";
    }
}
