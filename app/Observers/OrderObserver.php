<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\DeliveryAssignmentService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        // Quand la commande devient confirmée ou payée
        if (
            $order->type === 'delivery' &&
            in_array($order->status, ['confirmed', 'out_for_delivery']) &&
            !$order->driver_id
        ) {
            app(DeliveryAssignmentService::class)
                ->assignDriverToOrder($order);
        }
    }
}
