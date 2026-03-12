<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PaymentController;

// Payment Methods and Payouts Routes
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    // Payment Methods
    Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('/methods', [PaymentController::class, 'addPaymentMethod']);
    Route::put('/methods/{id}', [PaymentController::class, 'updatePaymentMethod']);
    Route::delete('/methods/{id}', [PaymentController::class, 'deletePaymentMethod']);
    Route::post('/methods/{id}/default', [PaymentController::class, 'setDefaultPaymentMethod']);
    Route::post('/methods/{id}/verify', [PaymentController::class, 'verifyPaymentMethod']);
    
    // Payouts
    Route::get('/payouts', [PaymentController::class, 'getPayouts']);
    Route::post('/payouts', [PaymentController::class, 'requestPayout']);
    Route::post('/payouts/{id}/cancel', [PaymentController::class, 'cancelPayout']);
    Route::get('/payouts/{id}', [PaymentController::class, 'getPayoutDetails']);
    Route::get('/payouts/summary', [PaymentController::class, 'getPayoutSummary']);
    Route::get('/payouts/methods', [PaymentController::class, 'getPayoutMethods']);
});

// Webhook Routes for Payment Processors
Route::prefix('webhooks')->group(function () {
    // Stripe Webhooks
    Route::post('/stripe', function (Request $request) {
        // Handle Stripe webhooks
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Handle different webhook events
        switch ($event->type) {
            case 'payment_intent.succeeded':
                // Handle successful payment
                break;
            case 'payment_intent.payment_failed':
                // Handle failed payment
                break;
            case 'payment_method.attached':
                // Handle payment method attached
                break;
            case 'payout.created':
                // Handle payout created
                break;
            case 'payout.failed':
                // Handle payout failed
                break;
        }

        return response()->json(['status' => 'success']);
    });

    // PayPal Webhooks
    Route::post('/paypal', function (Request $request) {
        // Handle PayPal webhooks
        $payload = $request->getContent();
        
        // Parse PayPal webhook
        $webhookData = json_decode($payload, true);
        
        // Handle different webhook events
        if (isset($webhookData['event_type'])) {
            switch ($webhookData['event_type']) {
                case 'PAYMENT.SALE.COMPLETED':
                    // Handle completed payment
                    break;
                case 'PAYMENT.SALE.DENIED':
                    // Handle denied payment
                    break;
                case 'MASS_PAY.PAYOUT.CREATED':
                    // Handle payout created
                    break;
            }
        }

        return response()->json(['status' => 'success']);
    });

    // Wise Webhooks
    Route::post('/wise', function (Request $request) {
        // Handle Wise webhooks
        $payload = $request->getContent();
        
        // Parse Wise webhook
        $webhookData = json_decode($payload, true);
        
        // Handle different webhook events
        if (isset($webhookData['event_type'])) {
            switch ($webhookData['event_type']) {
                case 'transfer.state_changed':
                    // Handle transfer state change
                    break;
                case 'payout.item.created':
                    // Handle payout item created
                    break;
            }
        }

        return response()->json(['status' => 'success']);
    });
});
