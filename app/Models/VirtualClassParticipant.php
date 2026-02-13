<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClassParticipant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'virtual_class_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
        'attendance_duration_minutes',
        'participation_data',
        'ai_engagement_score',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'attendance_duration_minutes' => 'integer',
        'participation_data' => 'array',
        'ai_engagement_score' => 'decimal:2',
    ];

    /**
     * ZenithaLMS: Participant Status Constants
     */
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_JOINED = 'joined';
    const STATUS_LEFT = 'left';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABSENT = 'absent';

    /**
     * ZenithaLMS: Relationships
     */
    public function virtualClass()
    {
        return $this->belongsTo(VirtualClass::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByVirtualClass($query, $classId)
    {
        return $query->where('virtual_class_id', $classId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeJoined($query)
    {
        return $query->where('status', self::STATUS_JOINED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isEnrolled()
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    public function isJoined()
    {
        return $this->status === self::STATUS_JOINED;
    }

    public function isLeft()
    {
        return $this->status === self::STATUS_LEFT;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isAbsent()
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function getAttendanceDurationFormatted()
    {
        if ($this->attendance_duration_minutes < 60) {
            return $this->attendance_duration_minutes . ' min';
        } else {
            $hours = floor($this->attendance_duration_minutes / 60);
            $minutes = $this->attendance_duration_minutes % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    public function getAttendancePercentage()
    {
        if (!$this->virtualClass) {
            return 0;
        }
        
        $classDuration = $this->virtualClass->duration_minutes;
        if ($classDuration === 0) {
            return 0;
        }
        
        return ($this->attendance_duration_minutes / $classDuration) * 100;
    }

    public function getParticipationData()
    {
        return $this->participation_data ?? [];
    }

    public function updateParticipationData($key, $value)
    {
        $data = $this->getParticipationData();
        $data[$key] = $value;
        
        $this->update([
            'participation_data' => $data,
        ]);
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiEngagementScore()
    {
        // ZenithaLMS: Generate AI-powered engagement score
        $engagementFactors = [
            'attendance_factor' => $this->calculateAttendanceFactor(),
            'participation_factor' => $this->calculateParticipationFactor(),
            'interaction_factor' => $this->calculateInteractionFactor(),
            'time_factor' => $this->calculateTimeFactor(),
            'quality_factor' => $this->calculateQualityFactor(),
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($engagementFactors as $factor => $data) {
            $totalScore += $data['score'] * $data['weight'];
            $totalWeight += $data['weight'];
        }

        $finalScore = $totalWeight > 0 ? ($totalScore / $totalWeight) : 0;

        $engagementData = [
            'final_score' => $finalScore,
            'factors' => $engagementFactors,
            'engagement_level' => $this->determineEngagementLevel($finalScore),
            'recommendations' => $this->generateEngagementRecommendations($engagementFactors),
            'calculated_at' => now()->toISOString(),
        ];

        $this->update([
            'ai_engagement_score' => $finalScore,
            'participation_data' => array_merge($this->getParticipationData(), [
                'ai_engagement' => $engagementData,
            ]),
        ]);

        return $engagementData;
    }

    private function calculateAttendanceFactor()
    {
        // ZenithaLMS: Calculate attendance factor
        $attendancePercentage = $this->getAttendancePercentage();
        
        if ($attendancePercentage >= 95) {
            return ['score' => 100, 'weight' => 0.3, 'analysis' => 'Excellent attendance'];
        } elseif ($attendancePercentage >= 80) {
            return ['score' => 85, 'weight' => 0.3, 'analysis' => 'Good attendance'];
        } elseif ($attendancePercentage >= 60) {
            return ['score' => 70, 'weight' => 0.3, 'analysis' => 'Fair attendance'];
        } elseif ($attendancePercentage >= 40) {
            return ['score' => 50, 'weight' => 0.3, 'analysis' => 'Poor attendance'];
        } else {
            return ['score' => 25, 'weight' => 0.3, 'analysis' => 'Very poor attendance'];
        }
    }

    private function calculateParticipationFactor()
    {
        // ZenithaLMS: Calculate participation factor
        $participationData = $this->getParticipationData();
        
        $score = 50; // Base score
        $analysis = 'Moderate participation';
        
        // Check chat messages
        $chatMessages = $participationData['chat_messages'] ?? 0;
        if ($chatMessages > 10) {
            $score += 20;
            $analysis = 'High participation';
        } elseif ($chatMessages > 5) {
            $score += 10;
            $analysis = 'Good participation';
        } elseif ($chatMessages < 2) {
            $score -= 10;
            $analysis = 'Low participation';
        }
        
        // Check questions asked
        $questionsAsked = $participationData['questions_asked'] ?? 0;
        if ($questionsAsked > 3) {
            $score += 15;
        } elseif ($questionsAsked > 1) {
            $score += 8;
        }
        
        // Check hand raises
        $handRaises = $participationData['hand_raises'] ?? 0;
        if ($handRaises > 5) {
            $score += 10;
        } elseif ($handRaises > 2) {
            $score += 5;
        }
        
        // Check polls answered
        $pollsAnswered = $participationData['polls_answered'] ?? 0;
        if ($pollsAnswered > 3) {
            $score += 10;
        } elseif ($pollsAnswered > 1) {
            $score += 5;
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'weight' => 0.25,
            'analysis' => $analysis,
            'details' => [
                'chat_messages' => $chatMessages,
                'questions_asked' => $questionsAsked,
                'hand_raises' => $handRaises,
                'polls_answered' => $pollsAnswered,
            ],
        ];
    }

    private function calculateInteractionFactor()
    {
        // ZenithaLMS: Calculate interaction factor
        $participationData = $this->getParticipationData();
        
        $score = 50; // Base score
        $analysis = 'Moderate interaction';
        
        // Check peer interactions
        $peerInteractions = $participationData['peer_interactions'] ?? 0;
        if ($peerInteractions > 5) {
            $score += 20;
            $analysis = 'High interaction';
        } elseif ($peerInteractions > 2) {
            $score += 10;
            $analysis = 'Good interaction';
        } elseif ($peerInteractions < 1) {
            $score -= 10;
            $analysis = 'Low interaction';
        }
        
        // Check group work participation
        $groupWorkParticipation = $participationData['group_work_participation'] ?? 0;
        if ($groupWorkParticipation > 3) {
            $score += 15;
        } elseif ($groupWorkParticipation > 1) {
            $score += 8;
        }
        
        // Check breakout room participation
        $breakoutRoomParticipation = $participationData['breakout_room_participation'] ?? 0;
        if ($breakoutRoomParticipation > 2) {
            $score += 10;
        } elseif ($breakoutRoomParticipation > 1) {
            $score += 5;
        }
        
        // Check screen sharing
        $screenSharing = $participationData['screen_sharing'] ?? 0;
        if ($screenSharing > 0) {
            $score += 15;
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'weight' => 0.2,
            'analysis' => $analysis,
            'details' => [
                'peer_interactions' => $peerInteractions,
                'group_work_participation' => $groupWorkParticipation,
                'breakout_room_participation' => $breakoutRoomParticipation,
                'screen_sharing' => $screenSharing,
            ],
        ];
    }

    private function calculateTimeFactor()
    {
        // ZenithaLMS: Calculate time factor
        $participationData = $this->getParticipationData();
        
        $score = 50; // Base score
        $analysis = 'Moderate timing';
        
        // Check join time
        $joinTime = $this->joined_at;
        $classStartTime = $this->virtualClass->started_at;
        
        if ($joinTime && $classStartTime) {
            $joinDelay = $joinTime->diffInMinutes($classStartTime);
            
            if ($joinDelay <= 5) {
                $score += 20;
                $analysis = 'Excellent timing';
            } elseif ($joinDelay <= 15) {
                $score += 10;
                $analysis = 'Good timing';
            } elseif ($joinDelay <= 30) {
                $score += 5;
                $analysis = 'Fair timing';
            } else {
                $score -= 10;
                $analysis = 'Late arrival';
            }
        }
        
        // Check active time vs total time
        $activeTime = $participationData['active_time_minutes'] ?? $this->attendance_duration_minutes;
        $totalTime = $this->virtualClass->duration_minutes;
        
        if ($totalTime > 0) {
            $activePercentage = ($activeTime / $totalTime) * 100;
            
            if ($activePercentage >= 90) {
                $score += 15;
            } elseif ($activePercentage >= 70) {
                $score += 10;
            } elseif ($activePercentage >= 50) {
                $score += 5;
            } else {
                $score -= 5;
            }
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'weight' => 0.15,
            'analysis' => $analysis,
            'details' => [
                'join_delay' => $joinDelay ?? null,
                'active_time' => $activeTime,
                'total_time' => $totalTime,
                'active_percentage' => $totalTime > 0 ? ($activeTime / $totalTime) * 100 : 0,
            ],
        ];
    }

    private function calculateQualityFactor()
    {
        // ZenithaLMS: Calculate quality factor
        $participationData = $this->getParticipationData();
        
        $score = 50; // Base score
        $analysis = 'Moderate quality';
        
        // Check question quality
        $questionQuality = $participationData['question_quality_score'] ?? 0;
        if ($questionQuality >= 80) {
            $score += 20;
            $analysis = 'High quality participation';
        } elseif ($questionQuality >= 60) {
            $score += 10;
            $analysis = 'Good quality participation';
        } elseif ($questionQuality < 40) {
            $score -= 10;
            $analysis = 'Low quality participation';
        }
        
        // Check contribution relevance
        $contributionRelevance = $participationData['contribution_relevance_score'] ?? 0;
        if ($contributionRelevance >= 80) {
            $score += 15;
        } elseif ($contributionRelevance >= 60) {
            $score += 8;
        } elseif ($contributionRelevance < 40) {
            $score -= 5;
        }
        
        // Check collaboration score
        $collaborationScore = $participationData['collaboration_score'] ?? 0;
        if ($collaborationScore >= 80) {
            $score += 15;
        } elseif ($collaborationScore >= 60) {
            $score += 7;
        } elseif ($collaborationScore < 40) {
            $score -= 5;
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'weight' => 0.1,
            'analysis' => $analysis,
            'details' => [
                'question_quality' => $questionQuality,
                'contribution_relevance' => $contributionRelevance,
                'collaboration_score' => $collaborationScore,
            ],
        ];
    }

    private function determineEngagementLevel($score)
    {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 80) {
            return 'high';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 60) {
            return 'moderate';
        } elseif ($score >= 50) {
            return 'low';
        }
        
        return 'very_low';
    }

    private function generateEngagementRecommendations($factors)
    {
        $recommendations = [];
        
        // Attendance recommendations
        if ($factors['attendance_factor']['score'] < 70) {
            $recommendations[] = 'Try to join classes on time and attend the full duration';
            $recommendations[] = 'Set reminders for class start times';
        }
        
        // Participation recommendations
        if ($factors['participation_factor']['score'] < 70) {
            $recommendations[] = 'Participate more actively in class discussions';
            $recommendations[] = 'Ask questions when you need clarification';
            $recommendations[] = 'Respond to polls and engage with interactive elements';
        }
        
        // Interaction recommendations
        if ($factors['interaction_factor']['score'] < 70) {
            $recommendations[] = 'Interact more with your peers during group activities';
            $recommendations[] = 'Participate actively in breakout rooms';
            $recommendations[] = 'Share your screen when appropriate to contribute';
        }
        
        // Time recommendations
        if ($factors['time_factor']['score'] < 70) {
            $recommendations[] = 'Join classes on time to maximize learning';
            $recommendations[] = 'Stay engaged throughout the entire session';
        }
        
        // Quality recommendations
        if ($factors['quality_factor']['score'] < 70) {
            $recommendations[] = 'Focus on asking relevant and thoughtful questions';
            $recommendations[] = 'Ensure your contributions are on-topic and helpful';
            $recommendations[] = 'Collaborate effectively with group members';
        }
        
        return $recommendations;
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ENROLLED => 'Enrolled',
            self::STATUS_JOINED => 'Joined',
            self::STATUS_LEFT => 'Left',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ABSENT => 'Absent',
        ];
    }

    public static function getEngagementLevels()
    {
        return [
            'excellent' => 'Excellent',
            'high' => 'High',
            'good' => 'Good',
            'moderate' => 'Moderate',
            'low' => 'Low',
            'very_low' => 'Very Low',
        ];
    }
}
