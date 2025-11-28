<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Générer une facture PDF pour une commande
     * 
     * @param Order $order
     * @return string Chemin du fichier PDF
     */
    public function generateInvoice(Order $order): string
    {
        $order->load(['user', 'restaurant', 'address', 'items.dish', 'payment', 'promoCode']);

        $data = [
            'order' => $order,
            'restaurant' => $order->restaurant,
            'user' => $order->user,
            'address' => $order->address,
            'items' => $order->items,
            'payment' => $order->payment,
            'promoCode' => $order->promoCode,
        ];

        $pdf = Pdf::loadView('invoices.order', $data);
        
        $filename = 'invoices/order_' . $order->order_number . '_' . time() . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Télécharger la facture PDF
     * 
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(Order $order)
    {
        $order->load(['user', 'restaurant', 'address', 'items.dish', 'payment', 'promoCode']);

        $data = [
            'order' => $order,
            'restaurant' => $order->restaurant,
            'user' => $order->user,
            'address' => $order->address,
            'items' => $order->items,
            'payment' => $order->payment,
            'promoCode' => $order->promoCode,
        ];

        $pdf = Pdf::loadView('invoices.order', $data);

        return $pdf->download('facture_' . $order->order_number . '.pdf');
    }

    /**
     * Obtenir le contenu PDF en base64
     * 
     * @param Order $order
     * @return string
     */
    public function getInvoiceBase64(Order $order): string
    {
        $order->load(['user', 'restaurant', 'address', 'items.dish', 'payment', 'promoCode']);

        $data = [
            'order' => $order,
            'restaurant' => $order->restaurant,
            'user' => $order->user,
            'address' => $order->address,
            'items' => $order->items,
            'payment' => $order->payment,
            'promoCode' => $order->promoCode,
        ];

        $pdf = Pdf::loadView('invoices.order', $data);

        return base64_encode($pdf->output());
    }
}


