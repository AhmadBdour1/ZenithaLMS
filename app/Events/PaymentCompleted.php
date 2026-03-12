<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function __construct(
        public Payment $payment
    ) {
        //
    }
    
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.' . $this->payment->user_id),
            new Channel('payments'),
        ];
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'payment.completed';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'payment_id' => $this->payment->id,
            'user_id' => $this->payment->user_id,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'type' => $this->payment->type,
            'status' => $this->payment->status,
            'course_id' => $this->payment->course_id,
            'completed_at' => $this->payment->completed_at,
            'gateway' => $this->payment->paymentGateway->name ?? 'Unknown',
        ];
    }
}
