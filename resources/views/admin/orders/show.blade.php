@extends('admin.layout')

@section('title', 'Détails de la Commande')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Commande #{{ $order->id }}</h1>
        <a href="{{ route('admin.orders') }}" class="text-blue-600 hover:text-blue-800">← Retour</a>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Informations</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600">Client</p>
                <p class="font-semibold">{{ $order->user->firstname ?? 'N/A' }} {{ $order->user->lastname ?? '' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Statut</p>
                <p class="font-semibold">{{ $getStatusText($order->status) }}</p>
            </div>
            <div>
                <p class="text-gray-600">Type</p>
                <p class="font-semibold">{{ $order->type === 'delivery' ? 'Livraison' : 'À emporter' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Total</p>
                <p class="font-semibold">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-gray-600">Date</p>
                <p class="font-semibold">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @if($order->driver)
            <div>
                <p class="text-gray-600">Livreur</p>
                <p class="font-semibold">{{ $order->driver->firstname }} {{ $order->driver->lastname }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Articles</h2>
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">Plat</th>
                    <th class="px-4 py-2 text-left">Quantité</th>
                    <th class="px-4 py-2 text-left">Prix Unitaire</th>
                    <th class="px-4 py-2 text-left">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $item->dish_name }}</td>
                        <td class="px-4 py-2">{{ $item->quantity }}</td>
                        <td class="px-4 py-2">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-2">{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center text-gray-500">Aucun article</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4 text-right">
            <p class="text-gray-600">Sous-total: <span class="font-semibold">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></p>
            @if($order->delivery_fee > 0)
            <p class="text-gray-600">Frais de livraison: <span class="font-semibold">{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></p>
            @endif
            @if($order->discount_amount > 0)
            <p class="text-green-600">Réduction: <span class="font-semibold">-{{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</span></p>
            @endif
            <p class="text-lg font-bold mt-2">Total: {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Changer le statut</h2>
        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
            @csrf
            @method('PUT')
            <select name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-4">
                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>En préparation</option>
                <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Prête</option>
                
                @if($order->type === 'delivery')
                    <!-- Statuts pour LIVRAISON -->
                    <option value="out_for_delivery" {{ $order->status === 'out_for_delivery' ? 'selected' : '' }}>En livraison</option>
                    <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Livrée</option>
                @else
                    <!-- Statuts pour À EMPORTER -->
                    <option value="picked_up" {{ $order->status === 'picked_up' ? 'selected' : '' }}>Récupérée</option>
                @endif
                
                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Mettre à jour
            </button>
        </form>

        @if($errors->any())
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded text-red-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif
    </div>
</div>

@php
function getStatusText($status) {
    return match($status) {
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'ready' => 'Prête',
        'out_for_delivery' => 'En livraison',
        'delivered' => 'Livrée',
        'picked_up' => 'Récupérée',
        'cancelled' => 'Annulée',
        default => $status,
    };
}
@endphp
@endsection