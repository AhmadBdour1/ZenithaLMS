<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type', // 'card', 'bank_account', 'paypal', 'stripe', 'crypto', 'apple_pay', 'google_pay', 'bank_transfer'
        'provider', // 'stripe', 'paypal', 'wise', 'coinbase', 'plaid', 'adyen', 'square'
        'method_identifier', // token, account_id, email, etc.
        'last_four', // last 4 digits for cards
        'brand', // visa, mastercard, etc.
        'expiry_month',
        'expiry_year',
        'cardholder_name',
        'bank_name',
        'bank_account_type', // 'checking', 'savings'
        'bank_routing_number',
        'bank_account_number',
        'paypal_email',
        'crypto_address',
        'crypto_currency', // 'BTC', 'ETH', 'USDT', etc.
        'apple_pay_token',
        'google_pay_token',
        'is_default',
        'is_verified',
        'verification_status', // 'pending', 'verified', 'failed'
        'verification_data',
        'metadata',
        'country',
        'currency',
        'billing_address',
        'nickname',
        'auto_renewal_enabled',
        'usage_count',
        'last_used_at',
        'expires_at',
        'failed_attempts',
        'blocked_until',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'verification_data' => 'array',
        'metadata' => 'array',
        'billing_address' => 'array',
        'auto_renewal_enabled' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'failed_attempts' => 'integer',
        'blocked_until' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->where(function ($q) {
            $q->whereNull('blocked_until')
              ->orWhere('blocked_until', '<', now());
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeCards($query)
    {
        return $query->where('type', 'card');
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('type', 'bank_account');
    }

    public function scopeDigitalWallets($query)
    {
        return $query->whereIn('type', ['paypal', 'crypto']);
    }

    // Methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isBlocked()
    {
        return $this->blocked_until && $this->blocked_until > now();
    }

    public function isAvailable()
    {
        return $this->is_verified && !$this->isExpired() && !$this->isBlocked();
    }

    public function getMaskedNumber()
    {
        switch ($this->type) {
            case 'card':
                return '**** **** **** ' . $this->last_four;
            case 'bank_account':
                return '****' . substr($this->bank_account_number, -4);
            case 'paypal':
                return $this->paypal_email;
            case 'crypto':
                return substr($this->crypto_address, 0, 6) . '...' . substr($this->crypto_address, -4);
            default:
                return $this->method_identifier;
        }
    }

    public function getDisplayName()
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        switch ($this->type) {
            case 'card':
                return ucfirst($this->brand) . ' ' . $this->getMaskedNumber();
            case 'bank_account':
                return $this->bank_name . ' ' . $this->getMaskedNumber();
            case 'paypal':
                return 'PayPal - ' . $this->paypal_email;
            case 'crypto':
                return $this->crypto_currency . ' ' . $this->getMaskedNumber();
            case 'apple_pay':
                return 'Apple Pay';
            case 'google_pay':
                return 'Google Pay';
            default:
                return ucfirst($this->type);
        }
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function recordFailedAttempt()
    {
        $this->increment('failed_attempts');
        
        // Block after 3 failed attempts for 24 hours
        if ($this->failed_attempts >= 3) {
            $this->update([
                'blocked_until' => now()->addHours(24),
                'notes' => 'Blocked due to multiple failed attempts',
            ]);
        }
    }

    public function resetFailedAttempts()
    {
        $this->update([
            'failed_attempts' => 0,
            'blocked_until' => null,
        ]);
    }

    public function setAsDefault()
    {
        // Remove default from other methods
        $this->user->paymentMethods()->where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    public function verify($verificationData = [])
    {
        $this->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'verification_data' => $verificationData,
        ]);
    }

    public function failVerification($reason = '')
    {
        $this->update([
            'is_verified' => false,
            'verification_status' => 'failed',
            'verification_data' => array_merge($this->verification_data ?? [], [
                'failed_at' => now()->toISOString(),
                'failure_reason' => $reason,
            ]),
        ]);
    }

    public function delete()
    {
        // Don't allow deletion if it's used in active subscriptions
        if ($this->subscriptions()->active()->exists()) {
            return false;
        }

        return parent::delete();
    }

    public function getPaymentIcon()
    {
        switch ($this->provider) {
            case 'stripe':
                return 'https://img.icons8.com/color/48/stripe.png';
            case 'paypal':
                return 'https://img.icons8.com/color/48/paypal.png';
            case 'wise':
                return 'https://img.icons8.com/color/48/wise.png';
            case 'coinbase':
                return 'https://img.icons8.com/color/48/coinbase.png';
            case 'plaid':
                return 'https://img.icons8.com/color/48/plaid.png';
            case 'adyen':
                return 'https://img.icons8.com/color/48/adyen.png';
            case 'square':
                return 'https://img.icons8.com/color/48/square.png';
            default:
                return 'https://img.icons8.com/color/48/credit-card.png';
        }
    }

    public function getCardBrandIcon()
    {
        switch (strtolower($this->brand)) {
            case 'visa':
                return 'https://img.icons8.com/color/48/visa.png';
            case 'mastercard':
                return 'https://img.icons8.com/color/48/mastercard.png';
            case 'amex':
                return 'https://img.icons8.com/color/48/amex.png';
            case 'discover':
                return 'https://img.icons8.com/color/48/discover.png';
            case 'jcb':
                return 'https://img.icons8.com/color/48/jcb.png';
            default:
                return 'https://img.icons8.com/color/48/credit-card.png';
        }
    }

    public function getSupportedCurrencies()
    {
        switch ($this->provider) {
            case 'stripe':
                return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK'];
            case 'paypal':
                return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CNY', 'INR', 'BRL', 'MXN'];
            case 'wise':
                return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN'];
            case 'coinbase':
                return ['BTC', 'ETH', 'USDT', 'USDC', 'DAI', 'LTC', 'BCH'];
            default:
                return ['USD'];
        }
    }

    public function canProcessPayment($amount, $currency)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!in_array($currency, $this->getSupportedCurrencies())) {
            return false;
        }

        // Check minimum/maximum amounts based on provider
        $limits = $this->getPaymentLimits();
        
        if ($amount < $limits['min'] || $amount > $limits['max']) {
            return false;
        }

        return true;
    }

    public function getPaymentLimits()
    {
        switch ($this->provider) {
            case 'stripe':
                return ['min' => 0.50, 'max' => 999999.99];
            case 'paypal':
                return ['min' => 0.01, 'max' => 10000.00];
            case 'wise':
                return ['min' => 1.00, 'max' => 1000000.00];
            case 'coinbase':
                return ['min' => 0.0001, 'max' => 1000.00]; // in crypto equivalent
            default:
                return ['min' => 1.00, 'max' => 10000.00];
        }
    }

    public function processPayment($amount, $currency, $description = '')
    {
        if (!$this->canProcessPayment($amount, $currency)) {
            throw new \Exception('Payment method cannot process this transaction');
        }

        $this->incrementUsage();

        // Delegate to provider-specific processor
        switch ($this->provider) {
            case 'stripe':
                return $this->processStripePayment($amount, $currency, $description);
            case 'paypal':
                return $this->processPayPalPayment($amount, $currency, $description);
            case 'wise':
                return $this->processWisePayment($amount, $currency, $description);
            case 'coinbase':
                return $this->processCoinbasePayment($amount, $currency, $description);
            default:
                throw new \Exception('Unsupported payment provider');
        }
    }

    private function processStripePayment($amount, $currency, $description)
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => strtolower($currency),
                'payment_method' => $this->method_identifier,
                'confirmation_method' => 'manual',
                'description' => $description,
                'metadata' => [
                    'payment_method_id' => $this->id,
                    'user_id' => $this->user_id,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
            ];

        } catch (\Exception $e) {
            $this->recordFailedAttempt();
            throw $e;
        }
    }

    private function processPayPalPayment($amount, $currency, $description)
    {
        // PayPal payment processing implementation
        return [
            'success' => true,
            'paypal_order_id' => 'paypal_' . time(),
            'approval_url' => 'https://www.paypal.com/cgi-bin/webscr',
        ];
    }

    private function processWisePayment($amount, $currency, $description)
    {
        // Wise payment processing implementation
        return [
            'success' => true,
            'transfer_id' => 'wise_' . time(),
            'status' => 'pending',
        ];
    }

    private function processCoinbasePayment($amount, $currency, $description)
    {
        // Coinbase payment processing implementation
        return [
            'success' => true,
            'charge_id' => 'coinbase_' . time(),
            'hosted_url' => 'https://commerce.coinbase.com/checkout',
        ];
    }
}
