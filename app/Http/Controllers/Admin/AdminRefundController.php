<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\StuffPurchase;
use App\Models\StuffReview;
use App\Models\AuraOrder;
use App\Models\AuraProduct;
use App\Models\AuraPage;
use App\Models\Certificate;
use App\Models\Refund;
use App\Models\Dispute;
use App\Models\Report;
use App\Models\Appeal;
use App\Models\Warning;
use App\Models\Ban;
use App\Models\Suspension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminRefundController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get refund requests dashboard
     */
    public function getRefundDashboard()
    {
        $stats = [
            'total_requests' => Refund::count(),
            'pending_requests' => Refund::where('status', 'pending')->count(),
            'approved_requests' => Refund::where('status', 'approved')->count(),
            'rejected_requests' => Refund::where('status', 'rejected')->count',
            'processing_requests' => Refund::where('status', 'processing')->count(),
            'completed_requests' => Refund::where('status', 'completed')->count(),
            'total_amount_refunded' => Refund::where('status', 'completed')->sum('refund_amount'),
            'this_month' => Refund::whereMonth('created_at', now()->month)->sum('refund_amount'),
            'this_week' => Refund::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('refund_amount'),
            'by_type' => [
                'course' => Refund::where('refundable_type', 'course')->count(),
                'subscription' => Refund::where('refundable_type', 'subscription')->count(),
                'stuff' => Refund::where('refundable_type', 'stuff')->count(),
                'marketplace' => Refund->where('refundable_type', 'marketplace')->count(),
                'pagebuilder' => Refund->where('refundable_type', 'pagebuilder')->count(),
                'certificate' => Refund->where('refundable_type', 'certificate')->count(),
            ],
            'by_status' => [
                'pending' => Refund::where('status', 'pending')->count(),
                'processing' => Refund::where('status', 'processing')->count(),
                'approved' => Refund::where('status', 'approved')->count(),
                'rejected' => Refund->where('status', 'rejected')->count(),
                'completed' => Refund->where('status', 'completed')->count(),
            ],
            'by_reason' => [
                'not_satisfied' => Refund::where('reason', 'not_satisfied')->count(),
                'technical_issue' => Refund::where('reason', 'technical_issue')->count(),
                'duplicate_purchase' => Refund->where('reason', 'duplicate_purchase')->count(),
                'accidental_purchase' => Refund->where('reason', 'accidental_purchase')->count(),
                'course_cancelled' => Refund->where('reason', 'course_cancelled')->count(),
                'subscription_cancelled' => Refund->where('reason', 'subscription_cancelled')->count(),
                'product_not_as_described' => Refund->where('reason', 'product_not_as_described')->count(),
                'other' => Refund->where('reason', 'other')->count(),
            ],
        ];

        $recentRequests = Refund::with(['user', 'refundable'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_requests' => $recentRequests,
            ],
        ]);
    }

    /**
     * Get all refund requests with filters
     */
    public function getRefundRequests(Request $request)
    {
        $query = Refund::query();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('refundable_type', $request->type);
        }

        if ($request->reason) {
            $query->where('reason', $request->reason);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->amount_min) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->amount_max) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('refund_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('refundable', function ($q) use ($request) {
                      $q->where('title', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $refunds = $query->with(['user', 'refundable', 'processor'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $refunds,
        ]);
    }

    /**
     * Create new refund request
     */
    public function createRefundRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refundable_type' => 'required|in:course,subscription,stuff,marketplace,pagebuilder,certificate',
            'refundable_id' => 'required|integer|min:1',
            'reason' => 'required|in:not_satisfied,technical_issue,duplicate_purchase,accidental_purchase,course_cancelled,subscription_cancelled,product_not_as_described,other',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'refund_type' => 'required|in:full,partial',
            'partial_amount' => 'nullable|required_if:refund_type,partial|numeric|min:0',
            'evidence' => 'nullable|array',
            'evidence.*' => 'string|max:255',
            'contact_preference' => 'nullable|in:email,phone,both',
            'contact_info' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $refundable = $this->getRefundableEntity($request->refundable_type, $request->refundable_id);

        if (!$refundable) {
            return response()->refundable([
                'success' => false,
                'message' => 'Refundable item not found',
            ], 404);
        }

        // Check if refund is eligible
        $eligibilityCheck = $this->checkRefundEligibility($refundable, $request->refund_type, $request->amount);
        
        if (!$eligibilityCheck['eligible']) {
            return response()->json([
                'success' => false,
                'message' => $eligibilityCheck['reason'],
            ], 400);
        }

        // Check for existing refund request
        $existingRefund = Refund::where('refundable_type', $request->refundable_type)
            ->where('refundable_id', $request->refundable_id)
            ->where('user_id', $request->user_id)
            ->whereIn('status', ['pending', 'processing', 'approved'])
            ->first();

        if ($existingRefund) {
            return response()->json([
                'success' => false,
                'message' => 'A refund request for this item already exists',
                'data' => $existingRefund,
            ], 400);
        }

        // Generate refund number
        $refundNumber = $this->generateRefundNumber();

        // Create refund request
        $refund = Refund::create([
            'refund_number' => $refundNumber,
            'user_id' => $user->id,
            'refundable_type' => $request->refundable_type,
            'refundable_id' => $request->refundable_id,
            'original_amount' => $refundable->price ?? $refundable->amount,
            'refund_amount' => $request->refund_type === 'full' ? $refundable->price ?? $refundable->amount : $request->partial_amount,
            'refund_type' => $request->refund_type,
            'reason' => $request->reason,
            'description' => $request->description,
            'evidence' => $request->evidence ?? [],
            'contact_preference' => $request->contact_preference ?? 'email',
            'contact_info' => $request->contact_info,
            'status' => 'pending',
            'processor_id' => auth()->id(),
            'refundable_data' => $refundable->toArray(),
        ]);

        // Send notification to user
        $this->sendRefundNotification($refund, 'created');

        // Send notification to vendor if applicable
        if ($refundable->vendor_id && $refundable->vendor_id !== $user->id) {
            $this->sendRefundNotification($refund, 'vendor_notification');
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request created successfully',
            'data' => $refund->fresh(['user', 'refundable']),
        ], 201);
    }

    /**
     * Update refund request
     */
    public function updateRefundRequest(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,approved,rejected,completed,cancelled',
            'processor_notes' => 'nullable|string|max:1000',
            'refund_amount' => 'nullable|numeric|min:0',
            'evidence' => 'nullable|array',
            'evidence.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $refund->status;
        $updateData = $request->only(['status', 'processor_notes', 'refund_amount', 'evidence']);

        // Add processor info
        $updateData['processor_id'] = auth()->id();
        $updateData['processed_at'] = now();

        $refund->update($updateData);

        // Handle status changes
        if ($oldStatus !== $request->status) {
            $this->handleStatusChange($refund, $oldStatus, $request->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request updated successfully',
            'data' => $refund->fresh(['user', 'refundable', 'processor']),
        ]);
    }

    /**
     * Process refund request
     */
    public function processRefund(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Refund request cannot be processed in current status',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'processor_notes' => 'required|string|max:1000',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $refund->status;
        $newStatus = $request->action === 'approve' ? 'approved' : 'rejected';
        
        $updateData = [
            'status' => $newStatus,
            'processor_id' => auth()->id(),
            'processor_notes' => $request->processor_notes,
            'processed_at' => now(),
        ];

        if ($request->has('refund_amount')) {
            $updateData['refund_amount'] = $request->refund_amount;
        }

        $refund->update($updateData);

        // Handle status changes
        $this->handleStatusChange($refund, $oldStatus, $newStatus);

        return response()->json([
            'success' => true,
            'message' => "Refund request {$request->action}d successfully",
            'data' => $refund->fresh(['user', 'refundable', 'processor']),
        ]);
    }

    /**
     * Complete refund
     */
    public function completeRefund($id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Refund must be approved before completion',
            ], 400);
        }

        // Process the actual refund (payment gateway integration)
        $refundResult = $this->processRefundPayment($refund);

        if (!$refundResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed: ' . $refundResult['message'],
            ], 500);
        }

        // Update refund status
        $refund->update([
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $refundResult['transaction_id'],
            'processor_id' => auth()->id(),
        ]);

        // Update original entity status
        $this->updateRefundableStatus($refund, 'refunded');

        // Send completion notifications
        $this->sendRefundNotification($refund, 'completed');

        return response()->json([
            'success' => true,
            'message' => 'Refund completed successfully',
            'data' => $refund->fresh(['user', 'refundable', 'processor']),
        ]);
    }

    /**
     * Cancel refund request
     */
    public function cancelRefund($id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed refund',
            ], 400);
        }

        $refund->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'processor_id' => auth()->id(),
        ]);

        // Update original entity status
        $this->updateRefundableStatus($refund, 'active');

        $this->sendRefundNotification($refund, 'cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Refund request cancelled',
            'data' => $refund->fresh(['user', 'refundable']),
        ]);
    }

    /**
     * Get refund details
     */
    public function getRefundDetails($id)
    {
        $refund = Refund::with(['user', 'refundable', 'processor'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }

    /**
     * Bulk actions on refund requests
     */
    public function bulkRefundAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refund_ids' => 'required|array',
            'refund_ids.*' => 'exists:refunds,id',
            'action' => 'required|in:approve,reject,cancel,delete',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $refunds = Refund::whereIn('id', $request->refund_ids)->get();
        $count = 0;

        foreach ($refunds as $refund) {
            switch ($request->action) {
                case 'approve':
                    if ($refund->status === 'pending') {
                        $refund->update([
                            'status' => 'approved',
                            'processor_id' => auth()->id(),
                            'processor_notes' => $request->reason ?? 'Bulk approved',
                            'processed_at' => now(),
                        ]);
                        $this->handleStatusChange($refund, 'pending', 'approved');
                        $count++;
                    }
                    break;

                case 'reject':
                    if ($refund->status === 'pending') {
                        $refund->update([
                            'status' => 'rejected',
                            'processor_id' => auth()->id(),
                            'processor_notes' => $request->reason ?? 'Bulk rejected',
                            'processed_at' => now(),
                        ]);
                        $this->handleStatusChange($refund, 'pending', 'rejected');
                        $count++;
                    }
                    break;

                case 'cancel':
                    if ($refund->status !== 'completed') {
                        $refund->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'processor_id' => auth()->id(),
                        ]);
                        $this->updateRefundableStatus($refund, 'active');
                        $count++;
                    }
                    break;

                case 'delete':
                    if ($refund->status === 'pending' || $refund->status === 'rejected' || $refund->status === 'cancelled') {
                        $this->updateRefundableStatus($refund, 'active');
                        $refund->delete();
                        $count++;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} refund requests affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Get refund statistics
     */
    public function getRefundStatistics(Request $request)
    {
        $period = $request->period ?? 'month';

        $query = Refund::query();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year());
                break;
            case 'year':
                $query->whereYear('created_at', now()->year());
                break;
        }

        $statistics = [
            'total_requests' => $query->count(),
            'total_amount' => $query->sum('refund_amount'),
            'by_status' => $query->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),
            'by_type' => $query->selectRaw('refundable_type, count(*) as count')
                ->groupBy('refundable_type')
                ->get()
                ->pluck('count', 'refundable_type')
                ->toArray(),
            'by_reason' => $query->selectRaw('reason, count(*) as count')
                ->groupBy('reason')
                ->get()
                ->pluck('count', 'reason')
                ->toArray(),
            'trends' => $this->getRefundTrends($period),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get refund policies
     */
    public function getRefundPolicies()
    {
        $policies = [
            'course' => [
                'name' => 'Course Refund Policy',
                'description' => 'Policy for refunding course purchases',
                'eligible_days' => 30,
                'refund_percentage' => 100,
                'conditions' => [
                    'Course must not be completed more than 30%',
                    'No certificates issued for the course',
                    'No more than 30 days since purchase',
                    'Valid reason provided',
                    'No violation of terms of service',
                ],
                'process_time' => '5-7 business days',
                'method' => 'Original payment method',
            ],
            'subscription' => [
                'name' => 'Subscription Refund Policy',
                'description' => 'Policy for refunding subscription charges',
                'eligible_days' => 14,
                'refund_percentage' => 100,
                'conditions' => [
                    'Within 14 days of billing cycle',
                    'No usage of service in current cycle',
                    'Valid reason provided',
                    'No violation of terms of service',
                ],
                'process_time' => '3-5 business days',
                'method' => 'Original payment method',
            ],
            'stuff' => [
                'name' => 'Stuff Refund Policy',
                'description' => 'Policy for refunding digital product purchases',
                'eligible_days' => 14,
                'refund_percentage' => 100,
                'conditions' => [
                    'Within 14 days of purchase',
                    'No downloads or usage of product',
                    'Valid reason provided',
                    'Product not as described',
                    'No violation of terms of service',
                ],
                'process_time' => '3-5 business days',
                'method' => 'Original payment method',
            ],
            'marketplace' => [
                'name' => 'Marketplace Refund Policy',
                'description' => 'Policy for refunding marketplace purchases',
                'eligible_days' => 7,
                'refund_percentage' => 100,
                'conditions' => [
                    'Within 7 days of purchase',
                    'Product not yet shipped or delivered',
                    'Valid reason provided',
                    'No violation of terms of service',
                ],
                'process_time' => '2-4 business days',
                'method' => 'Original payment method',
            ],
            'pagebuilder' => [
                'name' => 'Page Builder Refund Policy',
                'description' => 'Policy for refunding page builder purchases',
                'eligible_days' => 14,
                'refund_percentage' => 100,
                'conditions' => [
                    'Within 14 days of purchase',
                    'No pages created with purchased templates',
                    'Valid reason provided',
                    'No violation of terms of service',
                ],
                'process_time' => '3-5 business days',
                'method' => 'Original payment method',
            ],
            'certificate' => [
                'name' => 'Certificate Refund Policy',
                'description' => 'Policy for refunding certificate purchases',
                'eligible_days' => 7,
                'refund_percentage' => 100,
                'conditions' => [
                    'Within 7 days of purchase',
                    'Certificate not yet issued',
                    'Valid reason provided',
                    'No violation of terms of service',
                ],
                'process_time' => '2-3 business days',
                'method' => 'Original payment method',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    // Helper methods
    private function getRefundableEntity($type, $id)
    {
        switch ($type) {
            case 'course':
                return Course::find($id);
            case 'subscription':
                return Subscription::find($id);
            case 'stuff':
                return StuffPurchase::find($id);
            case 'marketplace':
                return AuraOrder::find($id);
            case 'pagebuilder':
                return AuraPage::find($id);
            case 'certificate':
                return Certificate::find($id);
            default:
                return null;
        }
    }

    private function checkRefundEligibility($refundable, $refundType, $amount)
    {
        $eligible = true;
        $reason = '';

        // Check refund policies
        $policy = $this->getRefundPolicy($refundable->getTable());
        
        if (!$policy) {
            return ['eligible' => false, 'reason' => 'No refund policy found for this item type'];
        }

        // Check time eligibility
        $daysSincePurchase = now()->diffInDays($refundable->created_at ?? $refundable->purchase_date ?? now());
        if ($daysSincePurchase > $policy['eligible_days']) {
            return ['eligible' => false, 'reason' => "Refund period of {$policy['eligible_days']} days has expired"];
        }

        // Check amount eligibility
        if ($refundType === 'partial' && $amount > ($refundable->price ?? $refundable->amount)) {
            return ['eligible' => false, 'reason' => 'Partial refund amount exceeds original amount'];
        }

        // Check if item is eligible for refund
        switch ($refundable->getTable()) {
            case 'courses':
                if ($refundable->progress > 30) {
                    $eligible = false;
                    $reason = 'Course progress exceeds 30%';
                }
                if ($refundable->certificates()->count() > 0) {
                    $eligible = false;
                    $reason = 'Certificate already issued for this course';
                }
                break;
            case 'subscriptions':
                if ($refundable->status === 'active') {
                    $eligible = false;
                    $reason = 'Active subscription cannot be refunded';
                }
                break;
            case 'stuff_purchases':
                if ($refundable->downloads > 0) {
                    $eligible = false;
                    $reason = 'Product has been downloaded';
                }
                break;
            case 'aura_orders':
                if (in_array($refundable->status, ['shipped', 'delivered'])) {
                    $eligible = false;
                    $reason = 'Product has been shipped/delivered';
                }
                break;
        }

        return ['eligible' => $eligible, 'reason' => $reason];
    }

    private function generateRefundNumber()
    {
        $prefix = 'REF';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    private function getRefundPolicy($entityType)
    {
        $policies = [
            'courses' => [
                'eligible_days' => 30,
                'refund_percentage' => 100,
            ],
            'subscriptions' => [
                'eligible_days' => 14,
                'refund_percentage' => 100,
            ],
            'stuff_purchases' => [
                'eligible_days' => 14,
                'refund_percentage' => 100,
            ],
            'aura_orders' => [
                'eligible_days' => 7,
                'refund_percentage' => 100,
            ],
            'aura_pages' => [
                'eligible_days' => 14,
                'refund_percentage' => 0, // No refunds for digital products
            ],
            'certificates' => [
                'eligible_days' => 7,
                'refund_percentage' => 100,
            ],
        ];

        return $policies[$entityType] ?? null;
    }

    private function handleStatusChange($refund, $oldStatus, $newStatus)
    {
        // Send notifications based on status change
        switch ($newStatus) {
            case 'approved':
                $this->sendRefundNotification($refund, 'approved');
                break;
            case 'rejected':
                $this->sendRefundNotification($refund, 'rejected');
                break;
            case 'completed':
                $this->sendRefundNotification($refund, 'completed');
                break;
            case 'cancelled':
                $this->sendRefundNotification($refund, 'cancelled');
                break;
        }
    }

    private function sendRefundNotification($refund, $type)
    {
        $user = $refund->user;
        $refundable = $refund->refundable;

        switch ($type) {
            case 'created':
                $subject = 'Refund Request Created';
                $message = "Your refund request #{$refund->refund_number} for {$refundable->title ?? 'item'} has been created and is now being reviewed.";
                break;
            case 'approved':
                $subject = 'Refund Request Approved';
                $message = "Your refund request #{$refund->refund_number} for {$refundable->title ?? 'item'} has been approved. Refund of \${$refund->refund_amount} will be processed within 5-7 business days.";
                break;
            case 'rejected':
                $subject = 'Refund Request Rejected';
                $message = "Your refund request #{$refund->refund_number} for {$refundable->title ?? 'item'} has been rejected. Reason: {$refund->processor_notes}";
                break;
            case 'completed':
                $subject = 'Refund Completed';
                $message = "Your refund request #{$refund->refund_number} for {$refundable->title ?? 'item'} has been completed. Refund of \${$refund->refund_amount} has been processed.";
                break;
            case 'cancelled':
                $subject = 'Refund Request Cancelled';
                $message = "Your refund request #{$refund->refund_number} for {$refundable->title ?? 'item'} has been cancelled.";
                break;
            case 'vendor_notification':
                $subject = 'New Refund Request';
                $message = "A new refund request #{$refund->refund_number} has been created for {$refundable->title ?? 'item'} by {$user->name}.";
                break;
            default:
                $subject = 'Refund Request Update';
                $message = "Your refund request #{$refund->refund_number} status has been updated to {$refund->status}.";
                break;
        }

        // Send email notification
        try {
            Mail::to($user->email)->send(new \App\Mail\RefundNotification($refund, $subject, $message));
        } catch (\Exception $e) {
            \Log::error('Failed to send refund notification: ' . $e->getMessage());
        }
    }

    private function processRefundPayment($refund)
    {
        // This would integrate with payment gateway
        // For now, return success
        return [
            'success' => true,
            'transaction_id' => 'txn_' . Str::random(10),
            'message' => 'Refund processed successfully',
        ];
    }

    private function updateRefundableStatus($refund, $status)
    {
        $refundable = $refund->refundable;

        switch ($refund->getTable()) {
            case 'courses':
                if ($status === 'refunded') {
                    $refundable->update(['status' => 'refunded']);
                }
                break;
            case 'subscriptions':
                if ($status === 'refunded') {
                    $refundable->update(['status' => 'cancelled']);
                }
                break;
            case 'stuff_purchases':
                if ($status === 'refunded') {
                    $refundable->update(['status' => 'refunded']);
                }
                break;
            case 'aura_orders':
                if ($status === 'refunded') {
                    $refundable->update(['status' => 'refunded']);
                }
                break;
            case 'aura_pages':
                // Pages don't change status
                break;
            case 'certificates':
                if ($status === 'refunded') {
                    $refundable->update(['status' => 'cancelled']);
                }
                break;
        }
    }

    private function getRefundTrends($period)
    {
        $query = Refund::query();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            'month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year());
                break;
            'year':
                $query->whereYear('created_at', now()->year());
                break;
        }

        return [
            'daily' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray(),
            'weekly' => $query->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as count')
                ->groupBy('week')
                ->orderBy('week')
                ->get()
                ->pluck('count', 'week')
                ->toArray(),
            'monthly' => $query->selectRaw('YEAR(created_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count', 'month')
                ->toArray(),
        ];
    }
}
