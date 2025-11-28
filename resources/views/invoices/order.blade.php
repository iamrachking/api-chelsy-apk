<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-row {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .total-final {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $restaurant->name }}</h1>
        <p>{{ $restaurant->address }}</p>
        <p>Tél: {{ $restaurant->phone }} | Email: {{ $restaurant->email }}</p>
    </div>

    <div class="info-section">
        <h3>Informations de la Commande</h3>
        <div class="info-row">
            <span class="info-label">Numéro:</span>
            <span>{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type:</span>
            <span>{{ $order->type === 'delivery' ? 'Livraison' : 'À emporter' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut:</span>
            <span>{{ ucfirst($order->status) }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>Client</h3>
        <div class="info-row">
            <span class="info-label">Nom:</span>
            <span>{{ $user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span>{{ $user->email }}</span>
        </div>
        @if($user->phone)
        <div class="info-row">
            <span class="info-label">Téléphone:</span>
            <span>{{ $user->phone }}</span>
        </div>
        @endif
        @if($address)
        <div class="info-row">
            <span class="info-label">Adresse:</span>
            <span>{{ $address->street }}, {{ $address->city }}</span>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Plat</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->dish_name }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total:</span>
            <span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span>
        </div>
        @if($order->delivery_fee > 0)
        <div class="total-row">
            <span>Frais de livraison:</span>
            <span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if($order->discount_amount > 0)
        <div class="total-row">
            <span>Réduction:</span>
            <span>-{{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        <div class="total-row total-final">
            <span>TOTAL:</span>
            <span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>

    @if($promoCode)
    <div class="info-section">
        <p><strong>Code promo utilisé:</strong> {{ $promoCode->code }}</p>
    </div>
    @endif

    @if($payment)
    <div class="info-section">
        <h3>Paiement</h3>
        <div class="info-row">
            <span class="info-label">Méthode:</span>
            <span>
                @if($payment->method === 'card')
                    Carte bancaire
                @elseif($payment->method === 'mobile_money')
                    Mobile Money ({{ $payment->mobile_money_provider }})
                @else
                    Espèces
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut:</span>
            <span>{{ ucfirst($payment->status) }}</span>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Merci pour votre commande !</p>
        <p>{{ $restaurant->name }} - {{ date('Y') }}</p>
    </div>
</body>
</html>


