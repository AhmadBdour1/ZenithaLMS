<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\VirtualClass;
use App\Models\VirtualClassParticipant;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZenithaLmsVirtualClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display virtual classes listing
     */
    public function index(Request $request)
    {
        $query = VirtualClass::with(['instructor', 'course'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at', 'asc');

        // ZenithaLMS: Enhanced search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // ZenithaLMS: Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ZenithaLMS: Filter by date
        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        $virtualClasses = $query->paginate(12);
        $courses = Course::where('is_published', true)->get();

        return view('zenithalms.virtual-class.index', compact('virtualClasses', 'courses'));
    }

    /**
     * Display virtual class details
     */
    public function show($id)
    {
        $virtualClass = VirtualClass::with(['instructor', 'course', 'participants'])
            ->findOrFail($id);

        // ZenithaLMS: Check if user is enrolled
        $isEnrolled = Auth::check() ? 
            $virtualClass->participants()->where('user_id', Auth::id())->exists() : 
            false;

        // ZenithaLMS: Check if user can join
        $canJoin = Auth::check() && 
            $virtualClass->status === 'live' && 
            $virtualClass->current_participants < $virtualClass->max_participants;

        // ZenithaLMS: Generate AI insights if not exists
        if (!$virtualClass->ai_analysis) {
            $this->generateAiInsights($virtualClass);
        }

        return view('zenithalms.virtual-class.show', compact(
            'virtualClass', 
            'isEnrolled', 
            'canJoin'
        ));
    }

    /**
     * Join virtual class
     */
    public function join($id)
    {
        $user = Auth::user();
        $virtualClass = VirtualClass::findOrFail($id);

        // ZenithaLMS: Check if user can join
        if ($virtualClass->status !== 'live') {
            return back()->with('error', 'This class is not currently live');
        }

        if ($virtualClass->current_participants >= $virtualClass->max_participants) {
            return back()->with('error', 'This class is full');
        }

        // ZenithaLMS: Check if user is already enrolled
        $existingParticipant = $virtualClass->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($existingParticipant) {
            if ($existingParticipant->status === 'joined') {
                return redirect()->route('zenithalms.virtual-class.room', $id);
            } else {
                // Reactivate participant
                $existingParticipant->update([
                    'status' => 'joined',
                    'joined_at' => now(),
                ]);
            }
        } else {
            // Create new participant
            VirtualClassParticipant::create([
                'virtual_class_id' => $id,
                'user_id' => $user->id,
                'status' => 'joined',
                'joined_at' => now(),
            ]);

            // Update participant count
            $virtualClass->increment('current_participants');
        }

        return redirect()->route('zenithalms.virtual-class.room', $id);
    }

    /**
     * Leave virtual class
     */
    public function leave($id)
    {
        $user = Auth::user();
        $virtualClass = VirtualClass::findOrFail($id);

        $participant = $virtualClass->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => 'left',
                'left_at' => now(),
                'attendance_duration_minutes' => now()->diffInMinutes($participant->joined_at),
            ]);

            // Update participant count
            $virtualClass->decrement('current_participants');
        }

        return redirect()->route('zenithalms.virtual-class.show', $id)
            ->with('success', 'You have left the virtual class');
    }

    /**
     * Display virtual class room
     */
    public function room($id)
    {
        $user = Auth::user();
        $virtualClass = VirtualClass::with(['instructor', 'course'])
            ->findOrFail($id);

        $participant = $virtualClass->participants()
            ->where('user_id', $user->id)
            ->where('status', 'joined')
            ->firstOrFail();

        // ZenithaLMS: Generate join URL
        $joinUrl = $this->generateJoinUrl($virtualClass, $participant);

        return view('zenithalms.virtual-class.room', compact(
            'virtualClass', 
            'participant', 
            'joinUrl'
        ));
    }

    /**
     * Display user's virtual classes
     */
    public function myClasses(Request $request)
    {
        $user = Auth::user();
        
        $query = VirtualClass::with(['instructor', 'course'])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('scheduled_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $virtualClasses = $query->paginate(12);

        return view('zenithalms.virtual-class.my-classes', compact('virtualClasses'));
    }

    /**
     * Display virtual class recording
     */
    public function recording($id)
    {
        $user = Auth::user();
        $virtualClass = VirtualClass::with(['instructor', 'course'])
            ->findOrFail($id);

        // ZenithaLMS: Check if user has access
        $hasAccess = $virtualClass->participants()
            ->where('user_id', $user->id)
            ->exists() || 
            $virtualClass->instructor_id === $user->id ||
            $user->hasRole('admin');

        if (!$hasAccess) {
            abort(403, 'Unauthorized access');
        }

        // ZenithaLMS: Get recording data
        $recordingData = $virtualClass->recording_data ?? [];

        return view('zenithalms.virtual-class.recording', compact(
            'virtualClass', 
            'recordingData'
        ));
    }

    /**
     * ZenithaLMS: Admin methods
     */
    public function create()
    {
        $this->authorize('manage_virtual_classes');
        
        $courses = Course::where('is_published', true)->get();
        return view('zenithalms.virtual-class.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage_virtual_classes');
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_participants' => 'required|integer|min:1|max:1000',
            'platform' => 'required|in:zoom,teams,google_meet,custom',
            'meeting_password' => 'nullable|string|max:20',
        ]);

        $virtualClassData = $request->except(['meeting_id', 'join_url']);
        $virtualClassData['instructor_id'] = Auth::id();
        $virtualClassData['status'] = 'scheduled';
        $virtualClassData['current_participants'] = 0;

        // ZenithaLMS: Generate meeting details
        $meetingDetails = $this->generateMeetingDetails($request->platform, $request->all());
        $virtualClassData = array_merge($virtualClassData, $meetingDetails);

        $virtualClass = VirtualClass::create($virtualClassData);

        // ZenithaLMS: Generate AI insights
        $this->generateAiInsights($virtualClass);

        return redirect()->route('zenithalms.virtual-class.show', $virtualClass->id)
            ->with('success', 'Virtual class created successfully!');
    }

    public function edit($id)
    {
        $this->authorize('manage_virtual_classes');
        
        $virtualClass = VirtualClass::findOrFail($id);
        $courses = Course::where('is_published', true)->get();
        
        return view('zenithalms.virtual-class.edit', compact('virtualClass', 'courses'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage_virtual_classes');
        
        $virtualClass = VirtualClass::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_participants' => 'required|integer|min:1|max:1000',
            'platform' => 'required|in:zoom,teams,google_meet,custom',
            'meeting_password' => 'nullable|string|max:20',
        ]);

        $virtualClass->update($request->except(['meeting_id', 'join_url']));

        // ZenithaLMS: Regenerate AI insights
        $this->generateAiInsights($virtualClass);

        return redirect()->route('zenithalms.virtual-class.show', $virtualClass->id)
            ->with('success', 'Virtual class updated successfully!');
    }

    public function start($id)
    {
        $this->authorize('manage_virtual_classes');
        
        $virtualClass = VirtualClass::findOrFail($id);
        
        $virtualClass->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        // ZenithaLMS: Send notifications to enrolled participants
        $this->sendClassStartNotifications($virtualClass);

        return back()->with('success', 'Virtual class started successfully!');
    }

    public function end($id)
    {
        $this->authorize('manage_virtual_classes');
        
        $virtualClass = VirtualClass::findOrFail($id);
        
        $virtualClass->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // ZenithaLMS: Update all participants status
        $virtualClass->participants()
            ->where('status', 'joined')
            ->update([
                'status' => 'completed',
                'left_at' => now(),
            ]);

        return back()->with('success', 'Virtual class ended successfully!');
    }

    public function destroy($id)
    {
        $this->authorize('manage_virtual_classes');
        
        $virtualClass = VirtualClass::findOrFail($id);
        $virtualClass->delete();

        return redirect()->route('zenithalms.virtual-class.index')
            ->with('success', 'Virtual class deleted successfully!');
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function generateAiInsights($virtualClass)
    {
        $content = $virtualClass->title . ' ' . $virtualClass->description;
        
        // ZenithaLMS: AI-powered analysis
        $insights = [
            'difficulty_level' => $this->analyzeDifficultyLevel($content),
            'estimated_engagement' => $this->estimateEngagement($virtualClass),
            'recommended_duration' => $this->recommendOptimalDuration($content),
            'target_audience' => $this->identifyTargetAudience($content),
            'interaction_suggestions' => $this->generateInteractionSuggestions($virtualClass),
            'vr_compatibility' => $this->checkVRCompatibility($virtualClass),
            'accessibility_features' => $this->recommendAccessibilityFeatures($virtualClass),
            'engagement_prediction' => $this->predictEngagement($virtualClass),
        ];

        $virtualClass->update([
            'ai_analysis' => $insights,
        ]);
    }

    private function generateMeetingDetails($platform, $data)
    {
        // ZenithaLMS: Generate meeting details based on platform
        $meetingDetails = [];

        switch ($platform) {
            case 'zoom':
                $meetingDetails = [
                    'meeting_id' => 'ZOOM_' . Str::random(10),
                    'join_url' => 'https://zoom.us/j/' . Str::random(10),
                    'meeting_password' => $data['meeting_password'] ?? Str::random(6),
                ];
                break;
            
            case 'teams':
                $meetingDetails = [
                    'meeting_id' => 'TEAMS_' . Str::random(10),
                    'join_url' => 'https://teams.microsoft.com/l/meetup-join/' . Str::random(10),
                    'meeting_password' => $data['meeting_password'] ?? null,
                ];
                break;
            
            case 'google_meet':
                $meetingDetails = [
                    'meeting_id' => 'MEET_' . Str::random(10),
                    'join_url' => 'https://meet.google.com/' . Str::random(10),
                    'meeting_password' => null,
                ];
                break;
            
            case 'custom':
                $meetingDetails = [
                    'meeting_id' => 'CUSTOM_' . Str::random(10),
                    'join_url' => $data['custom_join_url'] ?? null,
                    'meeting_password' => $data['meeting_password'] ?? null,
                ];
                break;
        }

        return $meetingDetails;
    }

    private function generateJoinUrl($virtualClass, $participant)
    {
        // ZenithaLMS: Generate personalized join URL
        $baseUrl = $virtualClass->join_url;
        $participantToken = Str::random(32);
        
        // Store participant token
        $participant->update([
            'join_url' => $baseUrl . '?token=' . $participantToken,
        ]);

        return $baseUrl . '?token=' . $participantToken;
    }

    private function analyzeDifficultyLevel($content)
    {
        // ZenithaLMS: Analyze content difficulty
        $advancedWords = ['advanced', 'complex', 'sophisticated', 'expert', 'professional', 'master'];
        $beginnerWords = ['basic', 'beginner', 'introductory', 'fundamental', 'simple', 'easy'];
        
        $contentLower = strtolower($content);
        $advancedCount = 0;
        $beginnerCount = 0;

        foreach ($advancedWords as $word) {
            $advancedCount += substr_count($contentLower, $word);
        }

        foreach ($beginnerWords as $word) {
            $beginnerCount += substr_count($contentLower, $word);
        }

        if ($advancedCount > $beginnerCount) {
            return 'advanced';
        } elseif ($beginnerCount > $advancedCount) {
            return 'beginner';
        }

        return 'intermediate';
    }

    private function estimateEngagement($virtualClass)
    {
        // ZenithaLMS: Estimate engagement level
        $factors = [
            'title_length' => strlen($virtualClass->title),
            'description_length' => strlen($virtualClass->description),
            'duration' => $virtualClass->duration_minutes,
            'max_participants' => $virtualClass->max_participants,
        ];

        $score = 0;
        
        // Title length factor
        if ($factors['title_length'] > 20 && $factors['title_length'] < 100) {
            $score += 20;
        }
        
        // Description length factor
        if ($factors['description_length'] > 50 && $factors['description_length'] < 500) {
            $score += 20;
        }
        
        // Duration factor
        if ($factors['duration'] >= 30 && $factors['duration'] <= 90) {
            $score += 30;
        }
        
        // Participant limit factor
        if ($factors['max_participants'] >= 10 && $factors['max_participants'] <= 50) {
            $score += 30;
        }

        if ($score >= 80) {
            return 'high';
        } elseif ($score >= 50) {
            return 'medium';
        }

        return 'low';
    }

    private function recommendOptimalDuration($content)
    {
        // ZenithaLMS: Recommend optimal duration
        $contentLength = strlen($content);
        
        if ($contentLength < 100) {
            return 30; // 30 minutes for short content
        } elseif ($contentLength < 300) {
            return 60; // 1 hour for medium content
        } elseif ($contentLength < 500) {
            return 90; // 1.5 hours for long content
        } else {
            return 120; // 2 hours for very long content
        }
    }

    private function identifyTargetAudience($content)
    {
        // ZenithaLMS: Identify target audience
        $audienceKeywords = [
            'beginners' => ['beginner', 'new', 'introductory', 'basic', 'fundamental'],
            'intermediate' => ['intermediate', 'improving', 'developing', 'enhancing'],
            'advanced' => ['advanced', 'expert', 'professional', 'master', 'specialized'],
            'students' => ['student', 'academic', 'university', 'college', 'school'],
            'professionals' => ['professional', 'corporate', 'business', 'industry', 'workplace'],
        ];

        $contentLower = strtolower($content);
        $audienceScores = [];

        foreach ($audienceKeywords as $audience => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($contentLower, $keyword);
            }
            $audienceScores[$audience] = $score;
        }

        arsort($audienceScores);
        
        return array_keys($audienceScores)[0] ?? 'general';
    }

    private function generateInteractionSuggestions($virtualClass)
    {
        // ZenithaLMS: Generate interaction suggestions
        $suggestions = [];
        
        if ($virtualClass->duration_minutes > 60) {
            $suggestions[] = 'Include Q&A sessions every 20 minutes';
            $suggestions[] = 'Use breakout rooms for group discussions';
        }
        
        if ($virtualClass->max_participants > 20) {
            $suggestions[] = 'Use polling for quick feedback';
            $suggestions[] = 'Enable chat moderation';
        }
        
        if ($virtualClass->platform === 'zoom') {
            $suggestions[] = 'Enable virtual backgrounds';
            $suggestions[] = 'Use reaction emojis';
        }
        
        $suggestions[] = 'Start with icebreaker activity';
        $suggestions[] = 'Use visual aids and presentations';
        $suggestions[] = 'Record session for later reference';
        
        return $suggestions;
    }

    private function checkVRCompatibility($virtualClass)
    {
        // ZenithaLMS: Check VR compatibility
        $vrFeatures = [
            'platform_support' => $virtualClass->platform === 'custom',
            'duration_suitable' => $virtualClass->duration_minutes >= 30,
            'participant_limit' => $virtualClass->max_participants <= 20,
            'content_type' => $this->analyzeContentType($virtualClass->description),
        ];

        $compatibilityScore = 0;
        
        foreach ($vrFeatures as $feature => $compatible) {
            if ($compatible) {
                $compatibilityScore += 25;
            }
        }

        return [
            'compatible' => $compatibilityScore >= 75,
            'score' => $compatibilityScore,
            'features' => $vrFeatures,
            'recommendations' => $this->getVRRecommendations($vrFeatures),
        ];
    }

    private function analyzeContentType($description)
    {
        // ZenithaLMS: Analyze content type for VR
        $vrKeywords = ['3d', 'virtual', 'reality', 'immersive', 'simulation', 'interactive'];
        $descriptionLower = strtolower($description);
        
        foreach ($vrKeywords as $keyword) {
            if (strpos($descriptionLower, $keyword) !== false) {
                return 'vr_suitable';
            }
        }
        
        return 'standard';
    }

    private function getVRRecommendations($vrFeatures)
    {
        $recommendations = [];
        
        if (!$vrFeatures['platform_support']) {
            $recommendations[] = 'Use VR-compatible platform like custom integration';
        }
        
        if (!$vrFeatures['duration_suitable']) {
            $recommendations[] = 'Increase duration to at least 30 minutes for VR experience';
        }
        
        if (!$vrFeatures['participant_limit']) {
            $recommendations[] = 'Limit participants to 20 for optimal VR experience';
        }
        
        if ($vrFeatures['content_type'] !== 'vr_suitable') {
            $recommendations[] = 'Include VR-specific content and activities';
        }
        
        return $recommendations;
    }

    private function recommendAccessibilityFeatures($virtualClass)
    {
        // ZenithaLMS: Recommend accessibility features
        $features = [
            'closed_captioning' => true,
            'sign_language' => $virtualClass->max_participants > 10,
            'screen_reader_support' => true,
            'keyboard_navigation' => true,
            'high_contrast_mode' => true,
            'adjustable_playback_speed' => true,
            'transcripts' => $virtualClass->duration_minutes > 30,
        ];

        return $features;
    }

    private function predictEngagement($virtualClass)
    {
        // ZenithaLMS: Predict engagement using AI
        $factors = [
            'time_of_day' => now()->format('H'),
            'day_of_week' => now()->format('N'),
            'duration' => $virtualClass->duration_minutes,
            'max_participants' => $virtualClass->max_participants,
            'title_quality' => $this->analyzeTitleQuality($virtualClass->title),
            'description_quality' => $this->analyzeDescriptionQuality($virtualClass->description),
        ];

        $score = 0;
        
        // Time of day factor
        if ($factors['time_of_day'] >= 10 && $factors['time_of_day'] <= 14) {
            $score += 15; // Morning/afternoon
        } elseif ($factors['time_of_day'] >= 18 && $factors['time_of_day'] <= 21) {
            $score += 20; // Evening
        }
        
        // Day of week factor
        if ($factors['day_of_week'] >= 2 && $factors['day_of_week'] <= 5) {
            $score += 10; // Weekdays
        }
        
        // Duration factor
        if ($factors['duration'] >= 30 && $factors['duration'] <= 90) {
            $score += 15;
        }
        
        // Participant limit factor
        if ($factors['max_participants'] >= 5 && $factors['max_participants'] <= 25) {
            $score += 10;
        }
        
        // Title quality factor
        $score += $factors['title_quality'] * 20;
        
        // Description quality factor
        $score += $factors['description_quality'] * 30;

        if ($score >= 80) {
            return ['predicted_engagement' => 'high', 'confidence' => 0.85];
        } elseif ($score >= 60) {
            return ['predicted_engagement' => 'medium', 'confidence' => 0.75];
        }
        
        return ['predicted_engagement' => 'low', 'confidence' => 0.65];
    }

    private function analyzeTitleQuality($title)
    {
        // ZenithaLMS: Analyze title quality
        $length = strlen($title);
        
        if ($length >= 20 && $length <= 60) {
            return 0.8;
        } elseif ($length >= 10 && $length <= 80) {
            return 0.6;
        }
        
        return 0.4;
    }

    private function analyzeDescriptionQuality($description)
    {
        // ZenithaLMS: Analyze description quality
        $length = strlen($description);
        
        if ($length >= 50 && $length <= 300) {
            return 0.8;
        } elseif ($length >= 30 && $length <= 500) {
            return 0.6;
        }
        
        return 0.4;
    }

    private function sendClassStartNotifications($virtualClass)
    {
        // ZenithaLMS: Send notifications to enrolled participants
        $participants = $virtualClass->participants()
            ->where('status', 'enrolled')
            ->with('user')
            ->get();

        foreach ($participants as $participant) {
            // Send notification
            $participant->user->notifications()->create([
                'title' => 'Virtual Class Started',
                'message' => "The virtual class '{$virtualClass->title}' has started. Join now!",
                'type' => 'virtual_class',
                'channel' => 'in_app',
                'notification_data' => [
                    'virtual_class_id' => $virtualClass->id,
                    'action_url' => route('zenithalms.virtual-class.room', $virtualClass->id),
                    'action_button' => 'Join Class',
                ],
            ]);
        }
    }
}
