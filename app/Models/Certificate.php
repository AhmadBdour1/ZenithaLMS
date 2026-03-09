<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_id',
        'enrollment_id',
        'template_id',
        'certificate_number',
        'title',
        'student_name',
        'course_name',
        'instructor_name',
        'instructor_signature',
        'institution_name',
        'institution_logo',
        'issue_date',
        'completion_date',
        'expiry_date',
        'grade',
        'score',
        'total_score',
        'percentage',
        'duration_hours',
        'duration_weeks',
        'skills_acquired',
        'description',
        'custom_fields',
        'verification_code',
        'verification_url',
        'is_verified',
        'is_public',
        'status', // 'draft', 'issued', 'revoked', 'expired'
        'template_data',
        'background_image',
        'layout',
        'font_family',
        'color_scheme',
        'qr_code_data',
        'blockchain_hash',
        'issued_by',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'completion_date' => 'datetime',
        'expiry_date' => 'datetime',
        'grade' => 'decimal:2',
        'score' => 'integer',
        'total_score' => 'integer',
        'percentage' => 'decimal:2',
        'duration_hours' => 'integer',
        'duration_weeks' => 'integer',
        'skills_acquired' => 'array',
        'custom_fields' => 'array',
        'template_data' => 'array',
        'certificate_data' => 'array',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
        'qr_code_data' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function verifications()
    {
        return $this->hasMany(CertificateVerification::class);
    }

    public function shares()
    {
        return $this->hasMany(CertificateShare::class);
    }

    // Scopes
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    // Methods
    public function generateCertificateNumber()
    {
        do {
            $number = 'CERT-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (static::where('certificate_number', $number)->exists());

        $this->certificate_number = $number;
        $this->save();

        return $number;
    }

    public function generateVerificationCode()
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (static::where('verification_code', $code)->exists());

        $this->verification_code = $code;
        $this->verification_url = route('certificates.verify', $code);
        $this->save();

        return $code;
    }

    public function issue()
    {
        $this->update([
            'status' => 'issued',
            'issue_date' => now(),
            'is_verified' => true,
        ]);

        // Generate certificate number and verification code if not exists
        if (!$this->certificate_number) {
            $this->generateCertificateNumber();
        }

        if (!$this->verification_code) {
            $this->generateVerificationCode();
        }

        // Generate QR code data
        $this->generateQRCode();

        // Generate blockchain hash for verification
        $this->generateBlockchainHash();

        return true;
    }

    public function revoke($reason = null)
    {
        $this->update([
            'status' => 'revoked',
            'is_verified' => false,
            'notes' => $reason,
        ]);

        return true;
    }

    public function verify()
    {
        if ($this->status !== 'issued') {
            return [
                'valid' => false,
                'reason' => 'Certificate is not issued',
            ];
        }

        if ($this->status === 'revoked') {
            return [
                'valid' => false,
                'reason' => 'Certificate has been revoked',
            ];
        }

        if ($this->expiry_date && $this->expiry_date < now()) {
            return [
                'valid' => false,
                'reason' => 'Certificate has expired',
            ];
        }

        // Record verification
        $this->verifications()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'verified_at' => now(),
        ]);

        return [
            'valid' => true,
            'certificate' => $this->load(['user', 'course', 'template']),
        ];
    }

    public function generateQRCode()
    {
        $qrData = [
            'certificate_number' => $this->certificate_number,
            'verification_code' => $this->verification_code,
            'verification_url' => $this->verification_url,
            'student_name' => $this->student_name,
            'course_name' => $this->course_name,
            'issue_date' => $this->issue_date->format('Y-m-d'),
            'grade' => $this->grade,
            'percentage' => $this->percentage,
        ];

        $this->qr_code_data = $qrData;
        $this->save();

        return $qrData;
    }

    public function generateBlockchainHash()
    {
        $data = [
            'certificate_number' => $this->certificate_number,
            'student_name' => $this->student_name,
            'course_name' => $this->course_name,
            'issue_date' => $this->issue_date->toISOString(),
            'grade' => $this->grade,
            'verification_code' => $this->verification_code,
        ];

        $this->blockchain_hash = hash('sha256', json_encode($data));
        $this->save();

        return $this->blockchain_hash;
    }

    public function getPublicUrl()
    {
        if (!$this->is_public) {
            return null;
        }

        return route('certificates.public', $this->verification_code);
    }

    public function getDownloadUrl($format = 'pdf')
    {
        return route('certificates.download', [
            'certificate' => $this->id,
            'format' => $format,
        ]);
    }

    public function share($platform = null, $customMessage = null)
    {
        $shareData = [
            'platform' => $platform,
            'message' => $customMessage ?: "I just completed {$this->course_name} and earned a certificate!",
            'shared_at' => now(),
            'ip_address' => request()->ip(),
        ];

        return $this->shares()->create($shareData);
    }

    public function getShareStats()
    {
        return $this->shares()
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();
    }

    public function canBeVerified()
    {
        return $this->status === 'issued' && 
               $this->is_verified && 
               (!$this->expiry_date || $this->expiry_date > now());
    }

    public function isValid()
    {
        return $this->status === 'issued' && 
               $this->is_verified && 
               $this->status !== 'revoked' &&
               (!$this->expiry_date || $this->expiry_date > now());
    }

    public function getGradeLetter()
    {
        if ($this->percentage >= 97) return 'A+';
        if ($this->percentage >= 93) return 'A';
        if ($this->percentage >= 90) return 'A-';
        if ($this->percentage >= 87) return 'B+';
        if ($this->percentage >= 83) return 'B';
        if ($this->percentage >= 80) return 'B-';
        if ($this->percentage >= 77) return 'C+';
        if ($this->percentage >= 73) return 'C';
        if ($this->percentage >= 70) return 'C-';
        if ($this->percentage >= 67) return 'D+';
        if ($this->percentage >= 63) return 'D';
        if ($this->percentage >= 60) return 'D-';
        
        return 'F';
    }

    public function getGradeColor()
    {
        $letter = $this->getGradeLetter();
        
        $colors = [
            'A+' => '#10b981', // green
            'A' => '#10b981',
            'A-' => '#10b981',
            'B+' => '#3b82f6', // blue
            'B' => '#3b82f6',
            'B-' => '#3b82f6',
            'C+' => '#f59e0b', // amber
            'C' => '#f59e0b',
            'C-' => '#f59e0b',
            'D+' => '#ef4444', // red
            'D' => '#ef4444',
            'D-' => '#ef4444',
            'F' => '#ef4444',
        ];

        return $colors[$letter] ?? '#6b7280'; // gray
    }

    public function getDurationText()
    {
        if ($this->duration_weeks) {
            return $this->duration_weeks . ' weeks';
        }
        
        if ($this->duration_hours) {
            return $this->duration_hours . ' hours';
        }
        
        return null;
    }

    public function addCustomField($key, $value)
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        
        $this->custom_fields = $fields;
        $this->save();
        
        return true;
    }

    public function getCustomField($key, $default = null)
    {
        $fields = $this->custom_fields ?? [];
        
        return $fields[$key] ?? $default;
    }

    public function duplicate()
    {
        $newCertificate = $this->replicate();
        $newCertificate->certificate_number = null;
        $newCertificate->verification_code = null;
        $newCertificate->verification_url = null;
        $newCertificate->blockchain_hash = null;
        $newCertificate->qr_code_data = null;
        $newCertificate->status = 'draft';
        $newCertificate->issue_date = null;
        $newCertificate->is_verified = false;
        $newCertificate->save();

        return $newCertificate;
    }
}
