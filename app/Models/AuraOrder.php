<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuraOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_number',
        'status', // 'pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed'
        'currency',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status', // 'pending', 'paid', 'failed', 'refunded', 'partially_refunded'
        'payment_details',
        'transaction_id',
        'billing_address',
        'shipping_address',
        'customer_notes',
        'admin_notes',
        'items_count',
        'shipping_method',
        'tracking_number',
        'estimated_delivery',
        'actual_delivery',
        'coupon_code',
        'coupon_discount',
        'affiliate_id',
        'affiliate_commission',
        'ip_address',
        'user_agent',
        'meta_data',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'payment_details' => 'array',
        'items_count' => 'integer',
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'coupon_discount' => 'decimal:2',
        'affiliate_commission' => 'decimal:2',
        'meta_data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(AuraOrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(AuraProduct::class, 'aura_order_items')
                    ->withPivot(['quantity', 'price', 'total', 'meta_data'])
                    ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(AuraTransaction::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(AuraOrderStatusHistory::class);
    }

    public function refunds()
    {
        return $this->hasMany(AuraRefund::class);
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['shipped', 'delivered']);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    // Methods
    public function generateOrderNumber()
    {
        do {
            $orderNumber = 'AURA-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
        } while (static::where('order_number', $orderNumber)->exists());

        $this->order_number = $orderNumber;
        $this->save();

        return $orderNumber;
    }

    public function addItem(AuraProduct $product, $quantity = 1, $price = null, $metaData = [])
    {
        $itemPrice = $price ?: $product->getDisplayPrice();
        $itemTotal = $itemPrice * $quantity;

        $orderItem = $this->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $itemPrice,
            'total' => $itemTotal,
            'meta_data' => $metaData,
        ]);

        // Update order totals
        $this->recalculateTotals();

        // Update product stock
        if ($this->status !== 'cancelled' && $this->status !== 'refunded') {
            $product->decrementStock($quantity);
        }

        return $orderItem;
    }

    public function removeItem($itemId)
    {
        $item = $this->items()->findOrFail($itemId);
        
        // Restore product stock
        if ($this->status !== 'cancelled' && $this->status !== 'refunded') {
            $item->product->incrementStock($item->quantity);
        }

        $item->delete();

        // Update order totals
        $this->recalculateTotals();

        return true;
    }

    public function updateItemQuantity($itemId, $quantity)
    {
        $item = $this->items()->findOrFail($itemId);
        
        // Adjust stock
        if ($this->status !== 'cancelled' && $this->status !== 'refunded') {
            $difference = $quantity - $item->quantity;
            
            if ($difference > 0) {
                $item->product->decrementStock($difference);
            } else {
                $item->product->incrementStock(abs($difference));
            }
        }

        $item->update([
            'quantity' => $quantity,
            'total' => $item->price * $quantity,
        ]);

        // Update order totals
        $this->recalculateTotals();

        return $item;
    }

    public function recalculateTotals()
    {
        $subtotal = $this->items()->sum('total');
        
        $this->subtotal = $subtotal;
        $this->items_count = $this->items()->sum('quantity');
        
        // Calculate totals (tax, shipping, discounts would be calculated here)
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
        
        $this->save();
    }

    public function updateStatus($newStatus, $notes = null, $notifyCustomer = true)
    {
        $oldStatus = $this->status;
        
        $this->update([
            'status' => $newStatus,
            'admin_notes' => $notes,
        ]);

        // Record status change
        $this->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => auth()->id(),
        ]);

        // Handle stock adjustments based on status
        if ($newStatus === 'cancelled' || $newStatus === 'refunded') {
            $this->restoreStock();
        }

        // Send notifications if needed
        if ($notifyCustomer) {
            $this->sendStatusNotification($newStatus);
        }

        return true;
    }

    public function updatePaymentStatus($newStatus, $transactionId = null, $details = null)
    {
        $this->update([
            'payment_status' => $newStatus,
            'transaction_id' => $transactionId ?: $this->transaction_id,
            'payment_details' => $details ?: $this->payment_details,
        ]);

        // Create transaction record
        if ($newStatus === 'paid' && $transactionId) {
            $this->transactions()->create([
                'type' => 'payment',
                'amount' => $this->total_amount,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'details' => $details,
            ]);
        }

        // Process affiliate commission
        if ($newStatus === 'paid' && $this->affiliate_id) {
            $this->processAffiliateCommission();
        }

        return true;
    }

    public function processPayment($paymentMethod, $paymentDetails)
    {
        // This would integrate with payment gateways
        // For now, we'll simulate successful payment
        
        $this->updatePaymentStatus('paid', 'TXN-' . time(), $paymentDetails);
        $this->updateStatus('processing');

        // Generate licenses for downloadable products
        $this->generateLicenses();

        return true;
    }

    public function generateLicenses()
    {
        foreach ($this->items as $item) {
            $product = $item->product;
            
            if ($product->license_key_required) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $product->generateLicense($this->user_id);
                }
            }
        }
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?: $this->total_amount;
        
        if ($refundAmount > $this->total_amount) {
            return false;
        }

        // Create refund record
        $refund = $this->refunds()->create([
            'amount' => $refundAmount,
            'reason' => $reason,
            'status' => 'processing',
        ]);

        // Process actual refund through payment gateway
        // This would integrate with Stripe, PayPal, etc.

        // Update order status
        if ($refundAmount >= $this->total_amount) {
            $this->updateStatus('refunded', $reason);
            $this->updatePaymentStatus('refunded');
        } else {
            $this->updatePaymentStatus('partially_refunded');
        }

        $refund->update(['status' => 'completed']);

        return $refund;
    }

    public function restoreStock()
    {
        foreach ($this->items as $item) {
            $item->product->incrementStock($item->quantity);
        }
    }

    public function processAffiliateCommission()
    {
        if (!$this->affiliate_id) {
            return;
        }

        $commission = $this->affiliate_commission;
        
        if ($commission > 0) {
            $this->affiliate->createConversion('sale', $this->total_amount, $this->order_number, [
                'order_id' => $this->id,
                'product_count' => $this->items_count,
            ]);
        }
    }

    public function sendStatusNotification($status)
    {
        // Implementation depends on notification system
        // Send email/SMS/push notification to customer
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeRefunded()
    {
        return in_array($this->status, ['processing', 'shipped', 'delivered']) && 
               $this->payment_status === 'paid';
    }

    public function isCompleted()
    {
        return in_array($this->status, ['shipped', 'delivered']);
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function getFormattedTotal()
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    public function getBillingAddressString()
    {
        if (!is_array($this->billing_address)) {
            return '';
        }

        $address = $this->billing_address;
        
        return implode(', ', array_filter([
            $address['first_name'] . ' ' . $address['last_name'],
            $address['address_1'],
            $address['address_2'],
            $address['city'],
            $address['state'],
            $address['postcode'],
            $address['country'],
        ]));
    }

    public function getShippingAddressString()
    {
        if (!is_array($this->shipping_address)) {
            return '';
        }

        $address = $this->shipping_address;
        
        return implode(', ', array_filter([
            $address['first_name'] . ' ' . $address['last_name'],
            $address['address_1'],
            $address['address_2'],
            $address['city'],
            $address['state'],
            $address['postcode'],
            $address['country'],
        ]));
    }
}
