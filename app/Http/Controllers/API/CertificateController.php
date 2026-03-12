<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's certificates
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $certificates = $user->certificates()
            ->with(['course', 'template'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $certificates->map(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'title' => $certificate->title,
                    'student_name' => $certificate->student_name,
                    'course_name' => $certificate->course_name,
                    'grade' => $certificate->grade,
                    'percentage' => $certificate->percentage,
                    'grade_letter' => $certificate->getGradeLetter(),
                    'grade_color' => $certificate->getGradeColor(),
                    'issue_date' => $certificate->issue_date?->format('Y-m-d'),
                    'expiry_date' => $certificate->expiry_date?->format('Y-m-d'),
                    'status' => $certificate->status,
                    'is_verified' => $certificate->is_verified,
                    'is_public' => $certificate->is_public,
                    'verification_url' => $certificate->verification_url,
                    'public_url' => $certificate->getPublicUrl(),
                    'download_url' => $certificate->getDownloadUrl(),
                    'course' => $certificate->course ? [
                        'id' => $certificate->course->id,
                        'title' => $certificate->course->title,
                        'thumbnail' => $certificate->course->getThumbnailUrl(),
                    ] : null,
                    'template' => $certificate->template ? [
                        'name' => $certificate->template->name,
                        'preview' => $certificate->template->getPreviewUrl(),
                    ] : null,
                    'created_at' => $certificate->created_at->format('Y-m-d'),
                ];
            }),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
            ],
        ]);
    }

    /**
     * Get certificate details
     */
    public function show($id)
    {
        $user = request()->user();
        
        $certificate = $user->certificates()
            ->with(['course', 'template', 'verifications', 'shares'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'title' => $certificate->title,
                'student_name' => $certificate->student_name,
                'course_name' => $certificate->course_name,
                'instructor_name' => $certificate->instructor_name,
                'institution_name' => $certificate->institution_name,
                'grade' => $certificate->grade,
                'score' => $certificate->score,
                'total_score' => $certificate->total_score,
                'percentage' => $certificate->percentage,
                'grade_letter' => $certificate->getGradeLetter(),
                'grade_color' => $certificate->getGradeColor(),
                'duration_hours' => $certificate->duration_hours,
                'duration_weeks' => $certificate->duration_weeks,
                'duration_text' => $certificate->getDurationText(),
                'skills_acquired' => $certificate->skills_acquired,
                'description' => $certificate->description,
                'custom_fields' => $certificate->custom_fields,
                'issue_date' => $certificate->issue_date?->format('Y-m-d'),
                'completion_date' => $certificate->completion_date?->format('Y-m-d'),
                'expiry_date' => $certificate->expiry_date?->format('Y-m-d'),
                'status' => $certificate->status,
                'is_verified' => $certificate->is_verified,
                'is_public' => $certificate->is_public,
                'verification_code' => $certificate->verification_code,
                'verification_url' => $certificate->verification_url,
                'public_url' => $certificate->getPublicUrl(),
                'download_url' => $certificate->getDownloadUrl(),
                'qr_code_data' => $certificate->qr_code_data,
                'blockchain_hash' => $certificate->blockchain_hash,
                'course' => $certificate->course,
                'template' => $certificate->template,
                'verifications_count' => $certificate->verifications()->count(),
                'shares_count' => $certificate->shares()->count(),
                'share_stats' => $certificate->getShareStats(),
                'can_be_verified' => $certificate->canBeVerified(),
                'is_valid' => $certificate->isValid(),
                'created_at' => $certificate->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Create new certificate
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'template_id' => 'required|exists:certificate_templates,id',
            'title' => 'required|string|max:255',
            'student_name' => 'required|string|max:255',
            'grade' => 'required|numeric|min:0|max:100',
            'score' => 'required|integer|min:0',
            'total_score' => 'required|integer|min:0',
            'duration_hours' => 'nullable|integer|min:1',
            'duration_weeks' => 'nullable|integer|min:1',
            'skills_acquired' => 'array',
            'description' => 'nullable|string',
            'custom_fields' => 'array',
            'is_public' => 'boolean',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $template = CertificateTemplate::findOrFail($request->template_id);

        // Check if user can use this template
        if (!$template->canBeUsedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot use this template. Upgrade to premium to access this template.',
            ], 403);
        }

        // Calculate percentage
        $percentage = $request->total_score > 0 ? ($request->score / $request->total_score) * 100 : 0;

        // Create certificate
        $certificate = $user->certificates()->create([
            'course_id' => $request->course_id,
            'template_id' => $request->template_id,
            'title' => $request->title,
            'student_name' => $request->student_name,
            'course_name' => $template->course->title,
            'instructor_name' => $template->course->instructor->name,
            'institution_name' => config('app.name'),
            'grade' => $request->grade,
            'score' => $request->score,
            'total_score' => $request->total_score,
            'percentage' => $percentage,
            'duration_hours' => $request->duration_hours,
            'duration_weeks' => $request->duration_weeks,
            'skills_acquired' => $request->skills_acquired,
            'description' => $request->description,
            'custom_fields' => $request->custom_fields,
            'is_public' => $request->get('is_public', false),
            'expiry_date' => $request->expiry_date,
            'completion_date' => now(),
            'status' => 'draft',
        ]);

        // Increment template usage
        $template->incrementUsage();

        return response()->json([
            'success' => true,
            'message' => 'Certificate created successfully',
            'data' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'status' => $certificate->status,
            ],
        ], 201);
    }

    /**
     * Issue certificate
     */
    public function issue($id)
    {
        $user = request()->user();
        $certificate = $user->certificates()->findOrFail($id);

        if ($certificate->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Certificate cannot be issued',
            ], 400);
        }

        if ($certificate->issue()) {
            return response()->json([
                'success' => true,
                'message' => 'Certificate issued successfully',
                'data' => [
                    'certificate_number' => $certificate->certificate_number,
                    'verification_code' => $certificate->verification_code,
                    'verification_url' => $certificate->verification_url,
                    'download_url' => $certificate->getDownloadUrl(),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to issue certificate',
        ], 500);
    }

    /**
     * Revoke certificate
     */
    public function revoke(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $certificate = $user->certificates()->findOrFail($id);

        if ($certificate->revoke($request->reason)) {
            return response()->json([
                'success' => true,
                'message' => 'Certificate revoked successfully',
                'data' => [
                    'status' => $certificate->fresh()->status,
                    'revoked_at' => now()->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to revoke certificate',
        ], 500);
    }

    /**
     * Update certificate
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'student_name' => 'sometimes|string|max:255',
            'skills_acquired' => 'sometimes|array',
            'description' => 'sometimes|nullable|string',
            'custom_fields' => 'sometimes|array',
            'is_public' => 'sometimes|boolean',
            'expiry_date' => 'sometimes|nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $certificate = $user->certificates()->findOrFail($id);

        // Only allow updating draft certificates
        if ($certificate->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update issued certificate',
            ], 400);
        }

        $certificate->update($request->only([
            'title',
            'student_name',
            'skills_acquired',
            'description',
            'custom_fields',
            'is_public',
            'expiry_date',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Certificate updated successfully',
            'data' => $certificate->fresh(),
        ]);
    }

    /**
     * Delete certificate
     */
    public function destroy($id)
    {
        $user = request()->user();
        $certificate = $user->certificates()->findOrFail($id);

        // Only allow deleting draft certificates
        if ($certificate->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete issued certificate',
            ], 400);
        }

        $certificate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Certificate deleted successfully',
        ]);
    }

    /**
     * Verify certificate (public endpoint)
     */
    public function verify($code)
    {
        $certificate = Certificate::where('verification_code', $code)
            ->with(['user', 'course', 'template'])
            ->firstOrFail();

        $verification = $certificate->verify();

        return response()->json([
            'success' => true,
            'data' => $verification,
        ]);
    }

    /**
     * Share certificate
     */
    public function share(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:facebook,twitter,linkedin,email,whatsapp',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $certificate = $user->certificates()->findOrFail($id);

        if (!$certificate->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate must be public to share',
            ], 400);
        }

        $share = $certificate->share($request->platform, $request->message);

        return response()->json([
            'success' => true,
            'message' => 'Certificate shared successfully',
            'data' => [
                'share_id' => $share->id,
                'public_url' => $certificate->getPublicUrl(),
                'share_message' => $share->message,
            ],
        ]);
    }

    /**
     * Get certificate templates
     */
    public function templates(Request $request)
    {
        $user = $request->user();
        
        $query = CertificateTemplate::active();

        // Filter by category
        if ($request->category) {
            $query->byCategory($request->category);
        }

        // Filter by layout
        if ($request->layout) {
            $query->byLayout($request->layout);
        }

        // Filter by premium/free
        if ($request->has('premium')) {
            $query->where('is_premium', $request->boolean('premium'));
        }

        // Sort
        $sort = $request->get('sort', 'popular');
        switch ($sort) {
            case 'popular':
                $query->popular();
                break;
            case 'rating':
                $query->highestRated();
                break;
            case 'newest':
                $query->latest();
                break;
            default:
                $query->orderBy('sort_order');
        }

        $templates = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $templates->map(function ($template) use ($user) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'category' => $template->category,
                    'layout' => $template->layout,
                    'orientation' => $template->orientation,
                    'preview_image' => $template->preview_image,
                    'preview_url' => $template->getPreviewUrl(),
                    'is_premium' => $template->is_premium,
                    'price' => $template->price,
                    'rating' => $template->rating,
                    'reviews_count' => $template->reviews_count,
                    'usage_count' => $template->usage_count,
                    'can_use' => $template->canBeUsedBy($user),
                ];
            }),
            'pagination' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    /**
     * Download certificate
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();
        $certificate = $user->certificates()->findOrFail($id);

        if ($certificate->status !== 'issued') {
            return response()->json([
                'success' => false,
                'message' => 'Certificate must be issued before downloading',
            ], 400);
        }

        $format = $request->get('format', 'pdf');

        // Generate certificate file
        $filename = "certificate-{$certificate->certificate_number}.{$format}";
        
        // This would generate the actual certificate file
        // For now, return a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Certificate download started',
            'data' => [
                'download_url' => $certificate->getDownloadUrl($format),
                'filename' => $filename,
            ],
        ]);
    }
}
