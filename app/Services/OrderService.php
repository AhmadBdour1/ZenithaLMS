<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Course;
use App\Models\Ebook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Create a new order
     */
    public function createOrder(User $user, array $items, array $data = []): array
    {
        // Calculate totals
        $totalAmount = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $totalAmount += $itemTotal;
            
            $orderItems[] = [
                'item_type' => $item['type'],
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'item_price' => $item['price'],
                'quantity' => $item['quantity'],
                'total_price' => $itemTotal,
                'item_data' => $item['data'] ?? null,
            ];
        }

        // Apply discount if provided
        $discountAmount = 0;
        if (!empty($data['coupon_code'])) {
            // TODO: Apply coupon logic
            $discountAmount = 0;
        }

        // Calculate tax (10% for example)
        $taxAmount = ($totalAmount - $discountAmount) * 0.10;
        $finalAmount = $totalAmount - $discountAmount + $taxAmount;

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'final_amount' => $finalAmount,
            'currency' => $data['currency'] ?? 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_gateway_id' => $data['payment_gateway_id'] ?? null,
            'order_data' => $data,
            'order_date' => now(),
        ]);

        // Create order items
        foreach ($orderItems as $orderItem) {
            $order->items()->create(array_merge($orderItem, [
                'order_id' => $order->id,
            ]));
        }

        return [
            'success' => true,
            'order' => $order,
            'message' => 'Order created successfully',
        ];
    }

    /**
     * Get order details
     */
    public function getOrderDetails(int $orderId, User $user): ?Order
    {
        return Order::with(['items', 'paymentGateway'])
            ->where('user_id', $user->id)
            ->find($orderId);
    }

    /**
     * Get user's orders
     */
    public function getUserOrders(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->orders()->with(['items', 'paymentGateway'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        return $query->paginate($perPage);
    }

    /**
     * Process order payment
     */
    public function processPayment(int $orderId, string $transactionId = null): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        if ($order->isPaid()) {
            return [
                'success' => false,
                'message' => 'Order is already paid',
            ];
        }

        // Mark order as paid
        $order->markAsPaid($transactionId);

        // Grant access to items
        $this->grantAccessToOrderItems($order);

        return [
            'success' => true,
            'order' => $order,
            'message' => 'Payment processed successfully',
        ];
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, User $user): array
    {
        $order = $this->getOrderDetails($orderId, $user);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        if ($order->isPaid()) {
            return [
                'success' => false,
                'message' => 'Cannot cancel paid order',
            ];
        }

        $order->cancel();

        return [
            'success' => true,
            'order' => $order,
            'message' => 'Order cancelled successfully',
        ];
    }

    /**
     * Grant access to order items
     */
    private function grantAccessToOrderItems(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->item_type === 'course') {
                $this->grantCourseAccess($order->user_id, $item->item_id);
            } elseif ($item->item_type === 'ebook') {
                $this->grantEbookAccess($order->user_id, $item->item_id);
            }
        }
    }

    /**
     * Grant course access
     */
    private function grantCourseAccess(int $userId, int $courseId): void
    {
        $user = User::find($userId);
        $course = Course::find($courseId);

        if ($user && $course) {
            // Check if already enrolled
            if (!$user->enrollments()->where('course_id', $courseId)->exists()) {
                $user->enrollments()->create([
                    'course_id' => $courseId,
                    'status' => 'active',
                    'progress_percentage' => 0,
                ]);
            }
        }
    }

    /**
     * Grant ebook access
     */
    private function grantEbookAccess(int $userId, int $ebookId): void
    {
        $user = User::find($userId);
        $ebook = Ebook::find($ebookId);

        if ($user && $ebook) {
            // Check if already has access
            if (!$ebook->accesses()->where('user_id', $userId)->exists()) {
                $ebook->accesses()->create([
                    'user_id' => $userId,
                    'download_count' => 0,
                ]);
            }
        }
    }

    /**
     * Create order from cart items
     */
    public function createOrderFromCart(User $user, array $cartItems, array $data = []): array
    {
        $orderItems = [];

        foreach ($cartItems as $cartItem) {
            $item = null;
            $type = null;
            $price = 0;

            if ($cartItem['type'] === 'course') {
                $item = Course::find($cartItem['id']);
                $type = 'course';
                $price = $item->price ?? 0;
            } elseif ($cartItem['type'] === 'ebook') {
                $item = Ebook::find($cartItem['id']);
                $type = 'ebook';
                $price = $item->price ?? 0;
            }

            if ($item && (!$item->is_free || $price > 0)) {
                $orderItems[] = [
                    'type' => $type,
                    'id' => $item->id,
                    'name' => $item->title ?? $item->name,
                    'price' => $price,
                    'quantity' => 1,
                    'data' => [
                        'thumbnail' => $item->getThumbnailUrl() ?? null,
                    ],
                ];
            }
        }

        if (empty($orderItems)) {
            return [
                'success' => false,
                'message' => 'No valid items in cart',
            ];
        }

        return $this->createOrder($user, $orderItems, $data);
    }

    /**
     * Get order statistics
     */
    public function getOrderStats(User $user): array
    {
        $orders = $user->orders();

        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->pending()->count(),
            'completed_orders' => $orders->completed()->count(),
            'cancelled_orders' => $orders->cancelled()->count(),
            'total_spent' => $orders->paid()->sum('final_amount'),
            'last_order' => $orders->latest()->first(),
        ];
    }
}
