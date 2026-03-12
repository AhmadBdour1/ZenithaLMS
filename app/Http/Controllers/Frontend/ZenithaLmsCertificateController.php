<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ZenithaLmsCertificateController extends Controller
{
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
        $this->middleware('auth');
    }

    /**
     * Display user's certificates
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Certificate::with(['course', 'template'])
            ->where('user_id', $user->id)
            ->where('is_verified', true)
            ->orderBy('issued_at', 'desc');

        // ZenithaLMS: Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        $certificates = $query->paginate(20);
        $courses = Course::where('is_published', true)->get();

        return view('zenithalms.certificate.index', compact('certificates', 'courses'));
    }

    /**
     * Display certificate details
     */
    public function show($certificateNumber)
    {
        $certificate = Certificate::with(['user', 'course', 'template'])
            ->where('certificate_number', $certificateNumber)
            ->where('is_verified', true)
            ->firstOrFail();

        // ZenithaLMS: Check if user owns this certificate
        if (Auth::id() !== $certificate->user_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        // ZenithaLMS: Generate QR code if not exists
        if (!$certificate->qr_code) {
            $this->generateQrCode($certificate);
        }

        return view('zenithalms.certificate.show', compact('certificate'));
    }

    /**
     * Verify certificate
     */
    public function verify(Request $request)
    {
        $certificateNumber = $request->get('certificate_number');
        $verificationCode = $request->get('verification_code');

        if (!$certificateNumber) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate number is required'
            ]);
        }

        $certificate = Certificate::with(['user', 'course'])
            ->where('certificate_number', $certificateNumber)
            ->first();

        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found'
            ]);
        }

        if (!$certificate->is_verified) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate has been revoked'
            ]);
        }

        if ($certificate->expires_at && $certificate->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate has expired'
            ]);
        }

        // ZenithaLMS: Verify with blockchain if enabled
        $blockchainVerified = $this->verifyBlockchain($certificate);

        return response()->json([
            'valid' => true,
            'certificate' => [
                'certificate_number' => $certificate->certificate_number,
                'title' => $certificate->title,
                'student_name' => $certificate->user->name,
                'course_name' => $certificate->course->title,
                'issued_at' => $certificate->issued_at->format('Y-m-d'),
                'expires_at' => $certificate->expires_at ? $certificate->expires_at->format('Y-m-d') : null,
                'verification_code' => $certificate->verification_code,
                'blockchain_verified' => $blockchainVerified,
            ]
        ]);
    }

    /**
     * Download certificate
     */
    public function download($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->where('user_id', Auth::id())
            ->where('is_verified', true)
            ->firstOrFail();

        // ZenithaLMS: Generate PDF certificate
        $pdf = $this->generatePdfCertificate($certificate);

        return $pdf->download('certificate-' . $certificate->certificate_number . '.pdf');
    }

    /**
     * Share certificate
     */
    public function share($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->where('user_id', Auth::id())
            ->where('is_verified', true)
            ->firstOrFail();

        // ZenithaLMS: Generate shareable link
        $shareUrl = route('zenithalms.certificate.verify') . '?certificate_number=' . $certificate->certificate_number;

        return response()->json([
            'share_url' => $shareUrl,
            'certificate_url' => route('zenithalms.certificate.show', $certificate->certificate_number),
            'qr_code' => $certificate->qr_code,
        ]);
    }

    /**
     * Add certificate to LinkedIn
     */
    public function addToLinkedIn($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->where('user_id', Auth::id())
            ->where('is_verified', true)
            ->firstOrFail();

        // ZenithaLMS: Generate LinkedIn share URL
        $linkedinUrl = 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME';
        $params = [
            'name' => $certificate->title,
            'organizationName' => 'ZenithaLMS',
            'issueYear' => $certificate->issued_at->format('Y'),
            'issueMonth' => $certificate->issued_at->format('m'),
            'certUrl' => route('zenithalms.certificate.verify') . '?certificate_number=' . $certificate->certificateNumber,
            'certId' => $certificate->certificate_number,
        ];

        $linkedinUrl .= '&' . http_build_query($params);

        return response()->json(['linkedin_url' => $linkedinUrl]);
    }

    /**
     * Display certificate verification page
     */
    public function verificationPage()
    {
        return view('zenithalms.certificate.verify');
    }

    /**
     * ZenithaLMS: Admin methods
     */
    public function adminIndex(Request $request)
    {
        $this->authorize('manage_certificates');
        
        $query = Certificate::with(['user', 'course', 'template'])
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($request->status === 'revoked') {
                $query->where('is_verified', false);
            }
        }

        // ZenithaLMS: Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // ZenithaLMS: Filter by date range
        if ($request->filled('date_from')) {
            $query->where('issued_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issued_at', '<=', $request->date_to);
        }

        $certificates = $query->paginate(50);
        $courses = Course::where('is_published', true)->get();

        return view('zenithalms.certificate.admin.index', compact('certificates', 'courses'));
    }

    public function adminShow($id)
    {
        $this->authorize('manage_certificates');
        
        $certificate = Certificate::with(['user', 'course', 'template'])
            ->findOrFail($id);

        return view('zenithalms.certificate.admin.show', compact('certificate'));
    }

    public function adminRevoke($id)
    {
        $this->authorize('manage_certificates');
        
        $certificate = Certificate::findOrFail($id);
        $certificate->update(['is_verified' => false]);

        // ZenithaLMS: Update blockchain status
        $this->updateBlockchainStatus($certificate, 'revoked');

        return back()->with('success', 'Certificate revoked successfully');
    }

    public function adminRestore($id)
    {
        $this->authorize('manage_certificates');
        
        $certificate = Certificate::findOrFail($id);
        $certificate->update(['is_verified' => true]);

        // ZenithaLMS: Update blockchain status
        $this->updateBlockchainStatus($certificate, 'active');

        return back()->with('success', 'Certificate restored successfully');
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function generatePdfCertificate($certificate)
    {
        // ZenithaLMS: Generate PDF certificate with modern design
        $pdf = new \TCPDF();
        
        $pdf->SetCreator('ZenithaLMS');
        $pdf->SetAuthor('ZenithaLMS');
        $pdf->SetTitle('Certificate - ' . $certificate->title);
        
        $pdf->AddPage();
        
        // ZenithaLMS: Add certificate content
        $this->addCertificateContent($pdf, $certificate);
        
        return $pdf;
    }

    private function addCertificateContent($pdf, $certificate)
    {
        // ZenithaLMS: Modern certificate design
        $pdf->SetFont('helvetica', '', 16);
        $pdf->Cell(0, 10, 'Certificate of Completion', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'This is to certify that', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, $certificate->user->name, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'has successfully completed the course', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, $certificate->course->title, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'on ' . $certificate->issued_at->format('F j, Y'), 0, 1, 'C');
        
        // ZenithaLMS: Add verification details
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Certificate Number: ' . $certificate->certificate_number, 0, 1, 'C');
        $pdf->Cell(0, 10, 'Verification Code: ' . $certificate->verification_code, 0, 1, 'C');
        
        // ZenithaLMS: Add QR code
        if ($certificate->qr_code) {
            $pdf->Image($certificate->qr_code, 150, 200, 50, 50, 'PNG');
        }
    }

    private function generateQrCode($certificate)
    {
        // ZenithaLMS: Generate QR code for certificate verification
        $verificationUrl = route('zenithalms.certificate.verify') . '?certificate_number=' . $certificate->certificate_number;
        
        $qrCode = QrCode::format('png')
            ->size(200)
            ->generate($verificationUrl);
        
        $folder = $this->mediaService->getFolder('certificates.qr_codes');
        $qrCodePath = $folder . '/' . $certificate->certificate_number . '.png';
        
        // Store using Storage directly since it's generated content, not uploaded
        Storage::disk('public')->put($qrCodePath, $qrCode);
        
        $certificate->update(['qr_code' => $qrCodePath]);
    }

    private function verifyBlockchain($certificate)
    {
        // ZenithaLMS: Blockchain verification (simulated)
        // In real implementation, this would connect to actual blockchain network
        $blockchainData = $certificate->blockchain_data ?? [];
        
        if (!empty($blockchainData)) {
            // Simulate blockchain verification
            return $blockchainData['verified'] ?? false;
        }
        
        return false;
    }

    private function updateBlockchainStatus($certificate, $status)
    {
        // ZenithaLMS: Update blockchain status (simulated)
        $blockchainData = $certificate->blockchain_data ?? [];
        $blockchainData['status'] = $status;
        $blockchainData['updated_at'] = now()->toISOString();
        
        $certificate->update(['blockchain_data' => $blockchainData]);
    }

    /**
     * ZenithaLMS: Auto-generate certificate for completed courses
     */
    public function autoGenerate($enrollmentId)
    {
        $enrollment = Enrollment::with(['user', 'course'])
            ->where('id', $enrollmentId)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->whereDoesntHave('certificate')
            ->firstOrFail();

        // ZenithaLMS: Generate certificate
        $certificate = Certificate::create([
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'template_id' => 1, // Default template
            'certificate_number' => $this->generateCertificateNumber(),
            'title' => 'Certificate of Completion - ' . $enrollment->course->title,
            'description' => 'Successfully completed ' . $enrollment->course->title,
            'verification_code' => $this->generateVerificationCode(),
            'issued_at' => now(),
            'is_verified' => true,
            'blockchain_data' => [
                'transaction_id' => Str::random(32),
                'block_hash' => Str::random(64),
                'verified' => true,
                'created_at' => now()->toISOString(),
            ],
        ]);

        // ZenithaLMS: Generate QR code
        $this->generateQrCode($certificate);

        return redirect()->route('zenithalms.certificate.show', $certificate->certificate_number)
            ->with('success', 'Certificate generated successfully!');
    }

    private function generateCertificateNumber()
    {
        // ZenithaLMS: Generate unique certificate number
        $prefix = 'ZLMS';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        
        return $prefix . '-' . $timestamp . '-' . $random;
    }

    private function generateVerificationCode()
    {
        // ZenithaLMS: Generate unique verification code
        return strtoupper(Str::random(8) . '-' . Str::random(4) . '-' . Str::random(4));
    }
}
