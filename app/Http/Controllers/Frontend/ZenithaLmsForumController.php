<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumReply;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZenithaLmsForumController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy', 'reply']);
    }

    /**
     * Display forum listing
     */
    public function index(Request $request)
    {
        $query = Forum::with(['user', 'course', 'replies'])
            ->where('status', 'active')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Enhanced search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('content', 'like', '%' . $searchTerm . '%');
            });
        }

        // ZenithaLMS: Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // ZenithaLMS: Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $forums = $query->paginate(20);
        $courses = Course::where('is_published', true)->get();

        return view('zenithalms.forum.index', compact('forums', 'courses'));
    }

    /**
     * Display forum post details
     */
    public function show($id)
    {
        $forum = Forum::with(['user', 'course', 'replies.user'])
            ->where('status', 'active')
            ->findOrFail($id);

        // ZenithaLMS: Increment view count
        $forum->incrementViewCount();

        // ZenithaLMS: Generate AI sentiment analysis if not exists
        if (!$forum->ai_sentiment) {
            $this->generateAiSentiment($forum);
        }

        return view('zenithalms.forum.show', compact('forum'));
    }

    /**
     * Show form to create forum post
     */
    public function create()
    {
        $courses = Course::where('is_published', true)->get();
        return view('zenithalms.forum.create', compact('courses'));
    }

    /**
     * Store new forum post
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'category' => 'required|in:general,discussion,question,announcement,feedback',
        ]);

        $forum = Forum::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'course_id' => $request->course_id,
            'category' => $request->category,
            'status' => 'active',
        ]);

        // ZenithaLMS: Generate AI sentiment analysis
        $this->generateAiSentiment($forum);

        return redirect()->route('zenithalms.forum.show', $forum->id)
            ->with('success', 'Forum post created successfully!');
    }

    /**
     * Show form to edit forum post
     */
    public function edit($id)
    {
        $forum = Forum::where('user_id', Auth::id())->findOrFail($id);
        $courses = Course::where('is_published', true)->get();
        
        return view('zenithalms.forum.edit', compact('forum', 'courses'));
    }

    /**
     * Update forum post
     */
    public function update(Request $request, $id)
    {
        $forum = Forum::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'category' => 'required|in:general,discussion,question,announcement,feedback',
        ]);

        $forum->update($request->all());

        // ZenithaLMS: Regenerate AI sentiment analysis
        $this->generateAiSentiment($forum);

        return redirect()->route('zenithalms.forum.show', $forum->id)
            ->with('success', 'Forum post updated successfully!');
    }

    /**
     * Delete forum post
     */
    public function destroy($id)
    {
        $forum = Forum::where('user_id', Auth::id())->findOrFail($id);
        $forum->delete();

        return redirect()->route('zenithalms.forum.index')
            ->with('success', 'Forum post deleted successfully!');
    }

    /**
     * Reply to forum post
     */
    public function reply(Request $request, $forumId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $forum = Forum::findOrFail($forumId);

        $reply = ForumReply::create([
            'forum_id' => $forumId,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'status' => 'active',
        ]);

        // ZenithaLMS: Update forum reply count
        $forum->increment('reply_count');

        // ZenithaLMS: Generate AI sentiment for reply
        $this->generateAiSentimentForReply($reply);

        return redirect()->route('zenithalms.forum.show', $forumId)
            ->with('success', 'Reply posted successfully!');
    }

    /**
     * Like forum post
     */
    public function like($forumId)
    {
        $forum = Forum::findOrFail($forumId);
        $user = Auth::user();

        // ZenithaLMS: Toggle like
        $existingLike = DB::table('forum_likes')
            ->where('forum_id', $forumId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            DB::table('forum_likes')
                ->where('forum_id', $forumId)
                ->where('user_id', $user->id)
                ->delete();
            
            $forum->decrement('like_count');
            $liked = false;
        } else {
            DB::table('forum_likes')->insert([
                'forum_id' => $forumId,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            
            $forum->increment('like_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'like_count' => $forum->like_count,
        ]);
    }

    /**
     * Like forum reply
     */
    public function likeReply($replyId)
    {
        $reply = ForumReply::findOrFail($replyId);
        $user = Auth::user();

        // ZenithaLMS: Toggle like
        $existingLike = DB::table('forum_reply_likes')
            ->where('reply_id', $replyId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            DB::table('forum_reply_likes')
                ->where('reply_id', $replyId)
                ->where('user_id', $user->id)
                ->delete();
            
            $reply->decrement('like_count');
            $liked = false;
        } else {
            DB::table('forum_reply_likes')->insert([
                'reply_id' => $replyId,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            
            $reply->increment('like_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'like_count' => $reply->like_count,
        ]);
    }

    /**
     * Search forum posts
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json(['posts' => []]);
        }

        // ZenithaLMS: AI-powered search
        $posts = Forum::with(['user', 'course'])
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();

        return response()->json(['posts' => $posts]);
    }

    /**
     * Get forum statistics
     */
    public function statistics()
    {
        $this->authorize('view_forum_statistics');

        $stats = [
            'total_posts' => Forum::where('status', 'active')->count(),
            'total_replies' => ForumReply::where('status', 'active')->count(),
            'total_users' => DB::table('forum_users')->distinct('user_id')->count(),
            'posts_by_category' => Forum::where('status', 'active')
                ->groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->get(),
            'most_active_users' => DB::table('forum_users')
                ->join('users', 'forum_users.user_id', '=', 'users.id')
                ->select('users.name', 'users.email', DB::raw('COUNT(*) as post_count'))
                ->groupBy('users.id', 'users.name', 'users.email')
                ->orderBy('post_count', 'desc')
                ->limit(10)
                ->get(),
            'recent_activity' => Forum::with(['user', 'course'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function generateAiSentiment($forum)
    {
        $content = $forum->title . ' ' . $forum->content;
        
        // ZenithaLMS: AI sentiment analysis
        $sentiment = $this->analyzeSentiment($content);
        $keywords = $this->extractKeywords($content);
        $topics = $this->extractTopics($content);
        $urgency = $this->calculateUrgency($content);

        $forum->update([
            'ai_sentiment' => [
                'sentiment' => $sentiment,
                'confidence' => $this->calculateSentimentConfidence($content, $sentiment),
                'keywords' => $keywords,
                'topics' => $topics,
                'urgency' => $urgency,
                'language' => $this->detectLanguage($content),
                'emotional_tone' => $this->analyzeEmotionalTone($content),
                'complexity' => $this->analyzeComplexity($content),
            ],
        ]);
    }

    private function generateAiSentimentForReply($reply)
    {
        $content = $reply->content;
        
        // ZenithaLMS: AI sentiment analysis for reply
        $sentiment = $this->analyzeSentiment($content);
        $keywords = $this->extractKeywords($content);
        $emotionalTone = $this->analyzeEmotionalTone($content);

        $reply->update([
            'ai_sentiment' => [
                'sentiment' => $sentiment,
                'confidence' => $this->calculateSentimentConfidence($content, $sentiment),
                'keywords' => $keywords,
                'emotional_tone' => $emotionalTone,
                'language' => $this->detectLanguage($content),
            ],
        ]);
    }

    private function analyzeSentiment($content)
    {
        // ZenithaLMS: Enhanced sentiment analysis
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'helpful', 'useful', 'perfect', 'love', 'awesome', 'brilliant', 'outstanding'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'disappointing', 'useless', 'hate', 'worst', 'poor', 'problem', 'issue', 'bug', 'error'];
        $neutralWords = ['question', 'information', 'help', 'need', 'want', 'looking', 'searching', 'find', 'check', 'verify'];

        $contentLower = strtolower($content);
        $positiveCount = 0;
        $negativeCount = 0;
        $neutralCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($contentLower, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($contentLower, $word);
        }

        foreach ($neutralWords as $word) {
            $neutralCount += substr_count($contentLower, $word);
        }

        $totalWords = str_word_count($contentLower);
        
        if ($positiveCount > $negativeCount && $positiveCount > $neutralCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount && $negativeCount > $neutralCount) {
            return 'negative';
        } elseif ($neutralCount > $positiveCount && $neutralCount > $negativeCount) {
            return 'neutral';
        } elseif ($positiveCount === $negativeCount) {
            return 'neutral';
        }

        return 'neutral';
    }

    private function calculateSentimentConfidence($content, $sentiment)
    {
        // ZenithaLMS: Calculate confidence score for sentiment analysis
        $totalWords = str_word_count($content);
        
        if ($totalWords === 0) {
            return 0;
        }

        $sentimentWords = [
            'positive' => ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'helpful', 'useful'],
            'negative' => ['bad', 'terrible', 'awful', 'horrible', 'disappointing', 'useless', 'problem', 'issue'],
            'neutral' => ['question', 'information', 'help', 'need', 'want', 'looking', 'searching'],
        ];

        $relevantWords = $sentimentWords[$sentiment] ?? [];
        $relevantCount = 0;

        foreach ($relevantWords as $word) {
            $relevantCount += substr_count(strtolower($content), $word);
        }

        $confidence = ($relevantCount / $totalWords) * 100;
        return min(100, max(0, $confidence));
    }

    private function extractKeywords($content)
    {
        // ZenithaLMS: Extract important keywords
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        $words = str_word_count(strtolower($content), 1);
        $keywords = [];

        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && strlen($word) > 2) {
                $keywords[] = $word;
            }
        }

        return array_unique(array_slice($keywords, 0, 10));
    }

    private function extractTopics($content)
    {
        // ZenithaLMS: Extract topics from content
        $topicKeywords = [
            'programming' => ['code', 'programming', 'development', 'software', 'coding', 'algorithm', 'database', 'api'],
            'design' => ['design', 'ui', 'ux', 'interface', 'user', 'experience', 'layout', 'visual', 'color'],
            'business' => ['business', 'marketing', 'sales', 'revenue', 'profit', 'customer', 'strategy', 'management'],
            'education' => ['learning', 'education', 'course', 'student', 'teacher', 'study', 'knowledge', 'skill'],
            'technology' => ['technology', 'tech', 'digital', 'innovation', 'artificial', 'intelligence', 'machine', 'data'],
        ];

        $topics = [];
        $contentLower = strtolower($content);

        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }

        return array_unique($topics);
    }

    private function calculateUrgency($content)
    {
        // ZenithaLMS: Calculate urgency level
        $urgentWords = ['urgent', 'emergency', 'asap', 'immediately', 'quickly', 'fast', 'soon', 'now', 'help', 'problem', 'issue', 'critical'];
        $contentLower = strtolower($content);
        
        $urgentCount = 0;
        foreach ($urgentWords as $word) {
            $urgentCount += substr_count($contentLower, $word);
        }

        if ($urgentCount >= 3) {
            return 'high';
        } elseif ($urgentCount >= 1) {
            return 'medium';
        }

        return 'low';
    }

    private function detectLanguage($content)
    {
        // ZenithaLMS: Simple language detection
        $arabicChars = 'ابجدهوزحطيكلمنسععفصقرشتثخذضظغعفقكلمنوهي';
        $contentSample = substr($content, 0, 100);
        
        $arabicCount = 0;
        for ($i = 0; $i < strlen($contentSample); $i++) {
            if (strpos($arabicChars, $contentSample[$i]) !== false) {
                $arabicCount++;
            }
        }

        if ($arabicCount > 20) {
            return 'ar';
        }

        return 'en';
    }

    private function analyzeEmotionalTone($content)
    {
        // ZenithaLMS: Analyze emotional tone
        $emotionalWords = [
            'excited' => ['excited', 'thrilled', 'enthusiastic', 'passionate', 'eager'],
            'frustrated' => ['frustrated', 'annoyed', 'irritated', 'upset', 'angry'],
            'confused' => ['confused', 'unclear', 'puzzled', 'lost', 'unsure'],
            'happy' => ['happy', 'pleased', 'satisfied', 'delighted', 'glad'],
            'worried' => ['worried', 'concerned', 'anxious', 'nervous', 'stressed'],
        ];

        $contentLower = strtolower($content);
        $toneScores = [];

        foreach ($emotionalWords as $tone => $words) {
            $score = 0;
            foreach ($words as $word) {
                $score += substr_count($contentLower, $word);
            }
            $toneScores[$tone] = $score;
        }

        arsort($toneScores);
        
        return key($toneScores) ?: 'neutral';
    }

    private function analyzeComplexity($content)
    {
        // ZenithaLMS: Analyze content complexity
        $wordCount = str_word_count($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content);
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : $wordCount;

        if ($avgWordsPerSentence > 20) {
            return 'high';
        } elseif ($avgWordsPerSentence > 10) {
            return 'medium';
        }

        return 'low';
    }
}
